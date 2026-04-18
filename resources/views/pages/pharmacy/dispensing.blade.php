<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Bus;
use App\Jobs\SyncMedicationDispenseToSatuSehat;
use App\Jobs\FinalizeVisitJob;
use App\Models\Invoice;
use App\Models\OutpatientVisit;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public $visit;
    public $showConfirmModal = false;
    public $message = '';
    public $currentRoute;
    public $patient_name,
        $patient_phone,
        $doctor_name,
        $medicines = [];

    public function mount()
    {
        // Simpan nama route saat halaman pertama kali dibuka
        $this->currentRoute = request()->route()->getName();
    }

    public function confirmDispense($visitId)
    {
        $this->visit = OutpatientVisit::with('patient')->findOrFail($visitId);
        $this->patient_name = $this->visit->patient->name;
        $this->showConfirmModal = true;
        $this->message = "Apakah Anda yakin ingin memproses penyerahan obat untuk pasien <b>{$this->patient_name}</b>?";
    }

    public function processDispense()
    {
        $this->showConfirmModal = false;
        $this->sendMedicationDispense($this->visit->id);
    }

    public function sendMedicationDispense($visitId)
    {
        $this->visit = OutpatientVisit::with('prescriptions.medicine')->findOrFail($visitId);
        Bus::chain([new SyncMedicationDispenseToSatuSehat($this->visit), new FinalizeVisitJob($this->visit)])->dispatch();

        $this->visit->prescriptions()->update([
            'status' => 'dispensed',
            'dispensed_at' => now(),
        ]);

        $this->visit->update([
            'internal_status' => 'finished',
            'dispensed_at' => now(),
        ]);

        $this->dispatch('toast', text: 'Obat berhasil disinkronkan ke SatuSehat.', type: 'success');
    }

    public function render()
    {
        $pharmacies = OutpatientVisit::has('prescriptions') // Hanya ambil yang ada resepnya
            ->with(['patient', 'prescriptions.medicine'])
            ->where('internal_status', 'paid')
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(25);

        return $this->view(['pharmacies' => $pharmacies]);
    }
};
?>

<div>
    @include('pages.pharmacy.route')

    <x-input wire:model.live.debounce.100ms="search" name="search" placeholder="Cari pasien..." type="search"
        class="mb-4 md:max-w-lg w-full" />

    @foreach ($pharmacies as $visit)
        @php
            $statuses = $visit->prescriptions->pluck('status');
            $statusBorder = 'border-l-gray-300';

            if ($statuses->contains('paid')) {
                $statusBorder = 'border-l-orange-500';
            } elseif ($statuses->contains('pharmacy_processing') || $statuses->contains('sent-for-payment')) {
                $statusBorder = 'border-l-yellow-400';
            } elseif ($statuses->isNotEmpty() && $statuses->every(fn($s) => $s === 'dispensed')) {
                $statusBorder = 'border-l-emerald-500';
            }
        @endphp
        <div class="card mb-4 border-l-8 {{ $statusBorder }} shadow-sm">
            <div class="card-header bg-slate-50 flex justify-between items-center p-4">
                <div>
                    <h3 class="font-bold text-slate-800">{{ $visit->patient->name }}</h3>
                    <p class="text-xs text-slate-500">Kunjungan: {{ $visit->arrived_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="flex items-center">
                    @if ($visit->prescriptions->every('paid_at'))
                        <x-button wire:click="confirmDispense({{ $visit->id }})" class="ml-4 text-sm"
                            variant="green">
                            PROSES PENYERAHAN OBAT
                        </x-button>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-400 uppercase bg-slate-100 table-fixed">
                        <tr>
                            <th class="px-4 py-2">Nama Obat</th>
                            <th class="w-30 px-4 py-2 text-center">Jumlah</th>
                            <th class="w-60 px-4 py-2">Aturan Pakai</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Loop Kedua: Mengambil obat dari relasi prescriptions --}}
                        @foreach ($visit->prescriptions as $item)
                            <tr class="border-b">
                                <td class="px-4 py-3 font-medium">{{ $item->medicine->name }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->qty_ordered }}
                                    {{ $item->medicine->unit }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 italic">{{ $item->instruction }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- Pagination Links --}}
    <div class="mt-4">
        {{ $pharmacies->links() }}
    </div>

    <x-confirm wire:model="showConfirmModal" title="Konfirmasi Penyerahan Obat" :message="$message"
        confirmText="Ya, Sudah diserahkan" cancelText="Batal" action="processDispense" />

</div>

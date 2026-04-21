<?php

use Livewire\Component;
use App\Models\Prescription;
use App\Models\OutpatientVisit;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Bus;
use App\Jobs\SyncMedicationDispenseToSatuSehat;
use App\Jobs\FinalizeVisitJob;
use App\Jobs\SyncConditionToSatuSehat;
use App\Jobs\SyncEncounterToSatuSehat;
use App\Jobs\SyncMedicationRequestToSatuSehat;
use App\Models\Invoice;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public $visit;
    public $showModal = false;
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function tebusLuarSemua()
    {
        // Mengubah semua qty_dispensed menjadi 0 secara instan
        foreach ($this->medicines as $index => $item) {
            $this->medicines[$index]['qty_dispensed'] = 0;
            $this->medicines[$index]['status'] = 'external';
        }
    }

    public function processAll($visitId)
    {
        $this->visit = OutpatientVisit::with('patient', 'practitioner', 'prescriptions')->find($visitId);
        $this->patient_name = $this->visit->patient->name;
        $this->patient_phone = $this->visit->patient->phone_number;
        $this->doctor_name = $this->visit->practitioner->name;
        $this->medicines = $this->visit->prescriptions->toArray();
        $this->showModal = true;
    }

    public function save()
    {
        DB::transaction(function () {
            $totalBiayaObatBaru = 0;
            foreach ($this->medicines as $item) {
                // Tentukan status berdasarkan qty_dispensed
                $status = 'sent_for_payment'; // Default status jika ada qty_dispensed > 0
                $external_at = null;

                if ($item['qty_dispensed'] == 0) {
                    $status = 'external'; // Pasien beli di luar
                    $external_at = now();
                } elseif ($item['qty_dispensed'] !== 0) {
                    $status = 'sent_for_payment';
                }

                // 2. Ambil data resep dan harga dari tabel medicines
                $prescription = Prescription::with('medicine')->find($item['id']);

                // Ambil harga dari tabel medicine (asumsi nama kolom adalah 'price')
                $hargaSatuan = $prescription->medicine->het_price ?? 0;

                // 3. Update data resep
                $prescription->update([
                    'qty_dispensed' => $item['qty_dispensed'],
                    'status' => $status,
                    'sent_for_payment_at' => now(),
                    'external_at' => $external_at,
                ]);

                $this->visit->update([
                    'internal_status' => 'sent_for_payment',
                    'sent_for_payment_at' => now(),
                ]);

                // 4. Akumulasi total biaya (qty_dispensed * harga dari tabel medicine)
                $totalBiayaObatBaru += $item['qty_dispensed'] * $hargaSatuan;
            }

            $invoice = Invoice::where('outpatient_visit_id', $this->visit->id)->first();
            if ($invoice) {
                $invoice->update([
                    'medicine_total' => $totalBiayaObatBaru,
                    'grand_total' => $invoice->registration_fee + $invoice->practitioner_fee + $totalBiayaObatBaru, // Asumsikan ada consultation_fee
                ]);
            }
        });

        $this->showModal = false;

        $this->dispatch('toast', text: 'Resep mulai diproses, menunggu konfirmasi pembayaran.', type: 'info');
    }

    public function render()
    {
        $pharmacies = OutpatientVisit::has('prescriptions') // Hanya ambil yang ada resepnya
            ->with(['patient', 'prescriptions.medicine'])
            ->where('internal_status', 'sent_to_pharmacy')
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

    @foreach ($pharmacies as $visit)
        @php
            $statuses = $visit->prescriptions->pluck('status');
            $statusBorder = 'border-l-gray-300';

            if ($statuses->contains('sent_to_pharmacy')) {
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
                    @if ($visit->prescriptions->every('sent_to_pharmacy_at'))
                        <x-button wire:click="processAll({{ $visit->id }})" class="ml-4 text-sm" variant="green">
                            PROSES DAN KONFIRMASI KE PASIEN
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
    <div x-data="{ open: @entangle('showModal') }" x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-black opacity-50" @click="open = false"></div>

        <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full p-6 dark:bg-gray-800">
            <div class="mb-5 flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Konfirmasi Penyiapan Obat</h3>
                    <p class="text-sm text-gray-500">Sesuaikan jumlah obat yang ditebus atau pilih Tebus Luar.</p>
                </div>
                <button wire:click="tebusLuarSemua"
                    class="px-4 py-2 text-sm font-semibold text-orange-700 bg-orange-100 border border-orange-200 rounded-md hover:bg-orange-200 transition">
                    🚀 Tebus Luar Semua
                </button>
            </div>

            <flux:separator />

            <div class="space-y-6 mt-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="patient_name" label="Nama Pasien" :disabled="true" name="patient_name" />
                    <x-input wire:model="doctor_name" label="Dokter" :disabled="true" name="doctor_name" />
                </div>

                <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Obat
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Resep
                                    (Qty)
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-32">
                                    Diberikan (Dispense)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($medicines as $index => $item)
                                <tr wire:key="med-{{ $index }}">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="font-medium">{{ $item['medicine_name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $item['instruction'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-400">
                                        {{ number_format($item['qty_ordered'], 0, ',', ',') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <x-input type="number"
                                            wire:model="medicines.{{ $index }}.qty_dispensed" min="0"
                                            max="{{ $item['qty_ordered'] }}" name="dispense_{{ $index }}" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm text-center text-gray-500 italic">
                                        Data
                                        resep tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 flex justify-between">
                    <button @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Batal
                    </button>
                    <div class="flex space-x-3">
                        <button wire:click="save"
                            class="px-6 py-2 text-sm font-bold text-white bg-blue-600 rounded-md hover:bg-blue-700 shadow-sm">
                            Simpan & Selesaikan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

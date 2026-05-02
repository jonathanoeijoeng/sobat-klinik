<?php

use Livewire\Component;
use App\Models\OutpatientVisit;
use App\Models\Patient;
use App\Models\Practitioner;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Transaction;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncEncounterToSatuSehat;
use App\Services\SatuSehatService;

new class extends Component {
    use WithPagination;

    public function startConsultation($visitId)
    {
        $visit = OutpatientVisit::findOrFail($visitId);
        $service = app(SatuSehatService::class);

        // 1. Update jam mulai hanya jika masih kosong (biar tidak tertimpa kalau ke-refresh)
        if (!$visit->in_progress_at) {
            $visit->update([
                'in_progress_at' => now(),
                'status' => 'in-progress', // Opsional: jika kamu pakai kolom status
                'internal_status' => 'at_practitioner', // Untuk tracking internal
                'at_practitioner_at' => now(), // Timestamp untuk tracking internal
            ]);
        }

        $service->updateEncounterStatusAndDiagnosis($visit, 'in-progress');

        // 2. Redirect ke halaman diagnosis
        return redirect()->route('practitioner.diagnosis', $visit->id);
    }

    public function render()
    {
        $patients = OutpatientVisit::query()
            ->with('patient', 'vitalSign')
            ->whereIn('internal_status', ['arrived', 'at_practitioner'])
            ->latest()
            ->paginate(25);

        $practitioners = Practitioner::all();

        return $this->view([
            'patients' => $patients,
            'practitioners' => $practitioners,
        ]);
    }
};
?>

<div>
    <x-header header="Dokter"
        description="Dashboard utama bagi praktisi medis untuk memproses kunjungan pasien, meninjau hasil pemeriksaan tanda-tanda vital, dan memberikan instruksi klinis. <br>Pantau status sinkronisasi setiap <b>Resource ID</b> untuk menjamin kelengkapan dokumentasi medis pada setiap episode perawatan." />
    <div class="hidden md:block">
        <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 md:mx-0 md:px-0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-brand-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                            Nama
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            L/P</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Tekanan darah</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Tinggi/Berat badan</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Keluhan</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Status</th>
                        <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($patients as $visit)
                        <tr>
                            <td class=" px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $visit->patient->name }}</div>
                            </td>
                            <td class=" px-6 py-4 text-center text-sm font-medium">
                                {{ $visit->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                            <td class=" px-6 py-4 text-center text-sm font-medium">
                                {{ $visit->vitalSign->systole }}/{{ $visit->vitalSign->diastole }} mmHg </td>
                            <td class=" px-6 py-4 text-center text-sm font-medium">
                                {{ $visit->vitalSign->height }} cm /
                                {{ number_format($visit->vitalSign->weight, 1, '.', ',') }} kg </td>
                            <td class="px-6 py-4 text-center text-sm font-medium">
                                {{ $visit->complaint }} </td>
                            <td class=" px-6 py-4 text-center text-sm font-medium capitalize">
                                {{ str($visit->internal_status)->headline() }}</td>
                            <td class="px-12 py-4 text-right text-sm font-medium">
                                <a class="{{ $visit->internal_status === 'at_practitioner' ? 'text-orange-500 hover:text-orange-700' : 'text-green-500 hover:text-green-700' }} cursor-pointer"
                                    {{ $visit->status === 'finished' ? 'disabled' : '' }}
                                    wire:click="startConsultation({{ $visit->id }})">{{ $visit->internal_status === 'at_practitioner' ? 'Lanjutkan Diagnosa' : 'Mulai Diagnosa' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm font-medium text-gray-900">
                                <x-nodatafound />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="md:block hidden mt-4">
            {{ $patients->links() }}
        </div>
    </div>

    {{-- Mobile version --}}
    <div class="md:hidden space-y-4 pb-4 mt-6">
        @foreach ($patients as $visit)
            <div @php
$statusColors = [
                'arrived'   => 'border-l-green-500',
                'completed' => 'border-l-green-500',
                ];

                // Ambil warna berdasarkan status, jika tidak ada di list maka default ke orange
                $borderColor = $statusColors[$visit->internal_status] ?? 'border-orange-500'; @endphp
                class="bg-white dark:bg-zinc-800 rounded-2xl p-2 shadow-sm border border-l-8 {{ $borderColor }}">

                {{-- Top Section --}}
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="font-semibold text-base leading-tight flex items-center gap-2">
                            {{ $visit->patient->name }}
                        </span>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ \Carbon\Carbon::parse($visit->date)->format('d M Y H:i') }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-lg font-semibold">
                            {{-- IDR {{ number_format($visit->invoice->grand_total) }} --}}
                        </p>
                    </div>
                </div>

                {{-- Middle Section --}}
                <div
                    class="text-sm text-gray-700 font-semibold dark:text-gray-300 space-y-1 flex justify-between align-center mb-3">
                    <div>
                        <p>
                            <span class="text-gray-400 font-normal">Dokter:</span>
                            {{ $visit->practitioner->name ?? '-' }}
                        </p>
                        <p>
                            <span class="text-gray-400 font-normal">No Kunjungan:</span>
                            {{ $visit->visit_number ?? '-' }}
                        </p>
                    </div>
                    </p>
                </div>

                {{-- Bottom Section --}}
                <div class="flex justify-between items-center">
                    <p class="text-xs {{ $visit->satusehat_encounter_id ? 'text-green-500' : 'text-slate-500' }} mt-1">
                        {{ $visit->satusehat_encounter_id ? 'Sinkron dengan SATUSEHAT' : 'Belum tersinkron dengan SATUSEHAT' }}
                    </p>
                    <p class="text-sm text-gray-800 capitalize">
                        <span
                            class="px-2 py-1 {{ $visit->internal_status === 'arrived' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}  text-xs rounded-full font-bold">
                            {{ $visit->internal_status === 'arrived' ? 'Mulai diagnosa' : 'Lanjutkan diagnosa' }}
                        </span>
                    </p>

                    {{-- Actions --}}
                    {{-- <div class="flex gap-4 text-sm">

                            <a href="" class="text-yellow-500 active:scale-95 transition">
                                Edit
                            </a>

                            <button wire:click="" class="text-red-600 active:scale-95 transition">
                                Delete
                            </button>
                        </div> --}}
                </div>
            </div>
        @endforeach
        <x-pagination-compact :paginator="$patients" />
    </div>
</div>

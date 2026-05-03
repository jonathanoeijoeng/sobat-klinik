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
                                <div class=" text-gray-900">{{ $visit->patient->name }}</div>
                            </td>
                            <td class=" px-6 py-4 text-center text-sm ">
                                {{ $visit->patient->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                            <td class=" px-6 py-4 text-center text-sm ">
                                {{ $visit->vitalSign->systole }}/{{ $visit->vitalSign->diastole }} mmHg </td>
                            <td class=" px-6 py-4 text-center text-sm ">
                                {{ $visit->vitalSign->height }} cm /
                                {{ number_format($visit->vitalSign->weight, 1, '.', ',') }} kg </td>
                            <td class="px-6 py-4 text-center text-sm ">
                                {{ $visit->complaint }} </td>
                            <td class=" px-6 py-4 text-center text-sm  capitalize">
                                {{ str($visit->internal_status)->headline() }}</td>
                            <td class="px-12 py-4 text-right text-sm ">
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
                'finished' => 'border-l-green-500',
                ];

                // Ambil warna berdasarkan status, jika tidak ada di list maka default ke orange
                $borderColor = $statusColors[$visit->internal_status] ?? 'border-orange-500'; @endphp
                class="cursor-pointer bg-white dark:bg-zinc-800 rounded-2xl p-4 shadow-sm border border-l-8 {{ $borderColor }}"
                wire:click="startConsultation({{ $visit->id }})">

                {{-- Top Section --}}
                <div class="flex
                justify-between items-start mb-3">
                    <div>
                        <span class="font-semibold text-base leading-tight flex items-center gap-2">
                            {{ $visit->patient->name }}
                        </span>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ \Carbon\Carbon::parse($visit->arrived_at)->format('d M Y H:i') }}
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
                    @if ($visit->satusehat_encounter_id)
                        <div class="flex gap-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="green" class="w-6 h-6">
                                <path
                                    d="M12 2C15.866 2 19 5.13401 19 9C19 9.11351 18.9973 9.22639 18.992 9.33857C21.3265 10.16 23 12.3846 23 15C23 18.3137 20.3137 21 17 21H7C3.68629 21 1 18.3137 1 15C1 12.3846 2.67346 10.16 5.00804 9.33857C5.0027 9.22639 5 9.11351 5 9C5 5.13401 8.13401 2 12 2ZM12 4C9.23858 4 7 6.23858 7 9C7 9.08147 7.00193 9.16263 7.00578 9.24344L7.07662 10.7309L5.67183 11.2252C4.0844 11.7837 3 13.2889 3 15C3 17.2091 4.79086 19 7 19H17C19.2091 19 21 17.2091 21 15C21 12.79 19.21 11 17 11C15.233 11 13.7337 12.1457 13.2042 13.7347L11.3064 13.1021C12.1005 10.7185 14.35 9 17 9C17 6.23858 14.7614 4 12 4Z">
                                </path>
                            </svg>
                            <span class="font-bold text-sm text-green-800">SATUSEHAT</span>
                        </div>
                    @else
                        <div class="flex gap-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="red" class="w-6 h-6">
                                <path d="M3.51472
                                        2.10051L22.6066 21.1924L21.1924 22.6066L19.1782 20.5924C18.503 20.8556 17.7684
                                        21 17 21H7C3.68629 21 1 18.3137 1 15C1 12.3846 2.67346 10.16 5.00804
                                        9.33857C5.0027 9.22639 5 9.11351 5 9C5 8.22228 5.12683 7.47418 5.36094
                                        6.77527L2.10051 3.51472L3.51472 2.10051ZM7 9C7 9.08147 7.00193 9.16263 7.00578
                                        9.24344L7.07662 10.7309L5.67183 11.2252C4.0844 11.7837 3 13.2889 3 15C3 17.2091
                                        4.79086 19 7 19H17C17.1858 19 17.3687 18.9873 17.5478 18.9628L7.03043
                                        8.44519C7.01032 8.62736 7 8.81247 7 9ZM12 2C15.866 2 19 5.13401 19 9C19 9.11351
                                        18.9973 9.22639 18.992 9.33857C21.3265 10.16 23 12.3846 23 15C23 16.0883 22.7103
                                        17.1089 22.2037 17.9889L20.7111 16.4955C20.8974 16.0335 21 15.5287 21 15C21
                                        12.79 19.21 11 17 11C16.4711 11 15.9661 11.1027 15.5039 11.2892L14.0111
                                        9.7964C14.8912 9.28978 15.9118 9 17 9C17 6.23858 14.7614 4 12 4C10.9295 4
                                        9.93766 4.33639 9.12428 4.90922L7.69418 3.48056C8.88169 2.55284 10.3763 2 12
                                        2Z">
                                </path>
                            </svg>
                            <span class="font-bold text-sm text-red-500">SATUSEHAT</span>
                        </div>
                    @endif
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

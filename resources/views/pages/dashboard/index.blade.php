<?php

use Livewire\Component;
use App\Models\OutpatientVisit;
use App\Models\Prescription;
use App\Models\OutPatientDiagnosis;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;
    public string $search = '';
    public $stats = [];
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->refreshStats();
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function paginationView()
    {
        return 'vendor.pagination.tailwind';
    }

    public function resetFilters()
    {
        $this->reset(['search']);
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function refreshStats()
    {
        $this->stats = [
            // Encounter
            'encounter_total' => OutpatientVisit::count(),
            'encounter_success' => OutpatientVisit::whereNotNull('satusehat_encounter_id')->count(),

            // Condition (Diagnosa)
            // Kita hitung dari tabel diagnosa/condition langsung
            'condition_total' => OutPatientDiagnosis::count(),
            'condition_success' => OutPatientDiagnosis::whereNotNull('satusehat_condition_id')->count(),

            // Medication Request (Resep)
            'prescription_total' => Prescription::count(),
            'prescription_success' => Prescription::whereNotNull('satusehat_medication_request_id')->count(),

            // Medication Dispense (Penyerahan Obat)
            'dispense_total' => Prescription::count(),
            'dispense_success' => Prescription::whereNotNull('satusehat_medication_dispense_id')->count(),
        ];
    }

    public function render()
    {
        $query = OutpatientVisit::with(['patient', 'invoice'])
            // ->whereBetween('arrived_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('arrived_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()]);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%');
                });
            });
        // dd($todayVisits);

        // Hitung stats dari koleksi $todayVisits menggunakan method isSynced()
        $total = (clone $query)->count();
        $synced = (clone $query)->whereNotNull('satusehat_encounter_id')->count();
        $pending = $total - $synced;
        $visits = $query->orderBy('arrived_at', 'desc')->paginate(25);

        return $this->view([
            'visits' => $visits,
            'total' => $total,
            'synced' => $synced,
            'pending' => $pending,
        ]);
    }
};
?>

<div>
    <x-header header="Dashboard"
        description="Visualisasi real-time performa klinik, mulai dari volume kunjungan pasien, status antrean farmasi, hingga kesehatan <b>integrasi API SatuSehat</b>. <br>Pantau data transaksi harian dan distribusi diagnosa penyakit secara akurat untuk mendukung pengambilan keputusan klinis dan operasional." />
    <div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-blue-100 p-4 rounded-lg shadow">
                <div class="text-blue-600 text-sm font-semibold">Total Pasien</div>
                <div class="text-3xl font-bold">{{ $total }}</div>
            </div>
            <div class="bg-green-100 p-4 rounded-lg shadow">
                <div class="text-green-600 text-sm font-semibold">Berhasil Sinkron SATUSEHAT</div>
                <div class="text-3xl font-bold">{{ $synced }}</div>
            </div>
            <div class="bg-yellow-100 p-4 rounded-lg shadow">
                <div class="text-yellow-600 text-sm font-semibold">Menunggu Antrean Job</div>
                <div class="text-3xl font-bold">{{ $pending }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <div
                class="p-5 {{ $stats['encounter_total'] - $stats['encounter_success'] === 0 ? 'bg-orange-100' : 'bg-red-200' }} shadow rounded-xl border border-gray-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Status Encounter</h3>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-bold">
                        {{ number_format($stats['encounter_success']) }}/{{ number_format($stats['encounter_total']) }}
                    </span>
                </div>

                @php
                    $condPercent =
                        $stats['encounter_total'] > 0
                            ? ($stats['encounter_success'] / $stats['encounter_total']) * 100
                            : 0;
                @endphp

                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $condPercent }}%"></div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    {{ number_format($stats['encounter_total'] - $stats['encounter_success']) }} encounter belum
                    tersinkron
                </p>
            </div>

            <div
                class="p-5 {{ $stats['condition_total'] - $stats['condition_success'] === 0 ? 'bg-pink-100' : 'bg-red-200' }} shadow rounded-xl border border-gray-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Status Condition</h3>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-bold">
                        {{ number_format($stats['condition_success']) }}/{{ number_format($stats['condition_total']) }}
                    </span>
                </div>

                @php
                    $condPercent =
                        $stats['condition_total'] > 0
                            ? ($stats['condition_success'] / $stats['condition_total']) * 100
                            : 0;
                @endphp

                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $condPercent }}%"></div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    {{ number_format($stats['condition_total'] - $stats['condition_success']) }} diagnosa belum
                    tersinkron
                </p>
            </div>
            <div
                class="p-5 {{ $stats['prescription_total'] - $stats['prescription_success'] === 0 ? 'bg-green-100' : 'bg-red-200' }} shadow rounded-xl border border-gray-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Status Medication Request
                    </h3>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-bold">
                        {{ number_format($stats['prescription_success']) }}/{{ number_format($stats['prescription_total']) }}
                    </span>
                </div>

                @php
                    $condPercent =
                        $stats['prescription_total'] > 0
                            ? ($stats['prescription_success'] / $stats['prescription_total']) * 100
                            : 0;
                @endphp

                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $condPercent }}%"></div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    {{ number_format($stats['prescription_total'] - $stats['prescription_success']) }} medication
                    request belum
                    tersinkron
                </p>
            </div>
            <div
                class="p-5 {{ $stats['dispense_total'] - $stats['dispense_success'] === 0 ? 'bg-sky-100' : 'bg-red-200' }} shadow rounded-xl border border-gray-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Status Medication Dispense
                    </h3>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-bold">
                        {{ number_format($stats['dispense_success']) }}/{{ number_format($stats['dispense_total']) }}
                    </span>
                </div>

                @php
                    $condPercent =
                        $stats['dispense_total'] > 0
                            ? ($stats['dispense_success'] / $stats['dispense_total']) * 100
                            : 0;
                @endphp

                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $condPercent }}%"></div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    {{ number_format($stats['dispense_total'] - $stats['dispense_success']) }} medication dispense
                    belum
                    tersinkron
                </p>
            </div>

        </div>

        <div class="block md:flex justify-between items-center mt-12 mb-3">
            <div class="flex gap-3 items-center">
                <h3 class="text-gray-800 font-bold uppercase tracking-wider">Daftar Kunjungan Pasien</h3>
            </div>
            <div class="block md:flex gap-3 items-center">
                <x-input wire:model.live.debounce.300ms="search" icon="search" placeholder="Cari nama pasien..."
                    name="search" type="search" class="py-0 my-3 md:my-0" />
                <div
                    class="flex justify-between pr-2 gap-2 items-center bg-white rounded-lg border border-gray-200 w-full md:w-fit">
                    <div class="flex items-center gap-2 justify-center">
                        <x-input type="date" wire:model.live="startDate" class="border-none focus:ring-0"
                            name="start_date" />
                        <span class="text-gray-400">s/d</span>
                        <x-input type="date" wire:model.live="endDate" class="border-none focus:ring-0"
                            name="end_date" />
                    </div>
                    @if ($startDate || $endDate)
                        <button wire:click="resetFilters" class="text-red-500 hover:text-red-700 p-1">
                            <x-icon name="x-circle" class="w-5 h-5" />
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="hidden md:block">
            <div class="bg-white rounded-lg shadow overflow-hidden mt-4 border border-zinc-300">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-5 py-3 text-left text-sm font-black text-gray-800 uppercase">Waktu</th>
                            <th class="px-5 py-3 text-left text-sm font-black text-gray-800 uppercase">No. Kunjungan
                            </th>
                            <th class="px-5 py-3 text-left text-sm font-black text-gray-800 uppercase">Pasien</th>
                            <th class="px-5 py-3 text-left text-sm font-black text-gray-800 uppercase">Dokter
                            </th>
                            <th class="px-5 py-3 text-left text-sm font-black text-gray-800 uppercase">Status</th>
                            <th class="px-5 py-3 text-center text-sm font-black text-gray-800 uppercase">SatuSehat
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visits as $visit)
                            <tr class="border-b">
                                <td class="px-5 py-4 text-sm">{{ $visit->arrived_at->format('d-M-Y H:i') }}</td>
                                <td class="px-5 py-4 text-sm">{{ $visit->visit_number }}</td>
                                <td class="px-5 py-4 text-sm">{{ $visit->patient->name }}</td>
                                <td class="px-5 py-4 text-sm">
                                    {{ $visit->practitioner->name ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm capitalize">{{ str($visit->internal_status)->headline() }}
                                </td>
                                <td class="px-4 py-3 w-75">
                                    @php
                                        $sync = $visit->getSatuSehatSyncStatus();
                                        $internalStatus = strtolower($visit->internal_status);

                                    @endphp

                                    <div class="flex flex-wrap gap-2 justify-left">
                                        <span @class([
                                            'px-3 py-1 text-[10px] font-bold uppercase rounded-full border transition-all',
                                            'bg-green-100 text-green-700 border-green-200' =>
                                                $visit->satusehat_encounter_id,
                                            'bg-gray-100 text-gray-400 border-gray-200 opacity-50' => !$visit->satusehat_encounter_id,
                                        ]) title="Encounter">
                                            ENC
                                        </span>

                                        @php
                                            $obsSynced =
                                                $visit->vitalsign &&
                                                $visit->vitalsign->satusehat_observation_blood_pressure_id &&
                                                $visit->vitalsign->satusehat_observation_weight_id &&
                                                $visit->vitalsign->satusehat_observation_height_id &&
                                                $visit->vitalsign->satusehat_observation_temperature_id;
                                        @endphp
                                        <span @class([
                                            'px-3 py-1 text-[10px] font-bold uppercase rounded-full border transition-all',
                                            'bg-orange-100 text-orange-700 border-orange-200' => $obsSynced,
                                            'bg-gray-100 text-gray-400 border-gray-200 opacity-50' => !$obsSynced,
                                        ]) title="Observation (Vital Sign)">
                                            OBS
                                        </span>

                                        @if (in_array($internalStatus, ['sent_to_pharmacy', 'finished', 'dispensed']))
                                            @php
                                                $diagSynced = $visit
                                                    ->diagnoses()
                                                    ->whereNotNull('satusehat_condition_id')
                                                    ->exists();
                                            @endphp
                                            <span @class([
                                                'px-3 py-1 text-[10px] font-bold uppercase rounded-full border transition-all',
                                                'bg-blue-100 text-blue-700 border-blue-200' => $diagSynced,
                                                'bg-gray-100 text-gray-400 border-gray-200 opacity-50' => !$diagSynced,
                                            ]) title="Condition (Diagnosis)">
                                                DIAG
                                            </span>
                                        @endif

                                        @php
                                            // Ambil data pertama dari relasi hasMany
                                            $prescription = $visit->prescriptions->first();
                                        @endphp

                                        @if ($prescription)
                                            @php
                                                $reqSynced = $prescription->satusehat_medication_request_id;
                                                // Cek apakah statusnya 'external'
                                                $isExternal = strtolower($prescription->status) === 'external';
                                            @endphp

                                            <span @class([
                                                'px-3 py-1 text-[10px] font-bold uppercase rounded-full border transition-all',
                                                'bg-purple-100 text-purple-700 border-purple-200' => $reqSynced,
                                                'bg-gray-100 text-gray-400 border-gray-200 opacity-50' => !$reqSynced,
                                            ]) title="Medication Request">
                                                RX
                                            </span>

                                            {{-- Tampilkan DISP hanya jika statusnya BUKAN external dan sudah dispensed --}}
                                            @if (!$isExternal && $internalStatus === 'finished')
                                                @php
                                                    $dispSynced = $prescription->satusehat_medication_dispense_id;
                                                @endphp
                                                <span @class([
                                                    'px-3 py-1 text-[10px] font-bold uppercase rounded-full border transition-all',
                                                    'bg-pink-100 text-pink-700 border-pink-200' => $dispSynced,
                                                    'bg-gray-100 text-gray-400 border-gray-200 opacity-50' => !$dispSynced,
                                                ]) title="Medication Dispense">
                                                    DISP
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="md:block hidden mt-4">
                {{ $visits->links() }}
            </div>
        </div>
        <div class="md:hidden space-y-4 pb-4 mt-6">
            @foreach ($visits as $visit)
                <div @php
$statusColors = [
                'arrived'   => 'border-l-green-500',
                'finished' => 'border-l-green-500',
                ];

                // Ambil warna berdasarkan status, jika tidak ada di list maka default ke orange
                $borderColor = $statusColors[$visit->internal_status] ?? 'border-orange-500'; @endphp
                    @endphp
                    class="bg-white dark:bg-zinc-800 rounded-2xl p-4 shadow-sm border border-l-8 {{ $borderColor }}
                        ">

                    {{-- Top Section --}}
                    <div class="flex justify-between items-start mb-3">
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
                                IDR {{ number_format($visit->invoice->grand_total) }}
                            </p>
                            <p
                                class="text-xs mt-1 font-medium
                                    {{ $visit->invoice->payment_status === 'paid' ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $visit->invoice->payment_status === 'paid' ? 'Paid' : 'Unpaid' }}
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
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="green"
                                    class="w-6 h-6">
                                    <path
                                        d="M12 2C15.866 2 19 5.13401 19 9C19 9.11351 18.9973 9.22639 18.992 9.33857C21.3265 10.16 23 12.3846 23 15C23 18.3137 20.3137 21 17 21H7C3.68629 21 1 18.3137 1 15C1 12.3846 2.67346 10.16 5.00804 9.33857C5.0027 9.22639 5 9.11351 5 9C5 5.13401 8.13401 2 12 2ZM12 4C9.23858 4 7 6.23858 7 9C7 9.08147 7.00193 9.16263 7.00578 9.24344L7.07662 10.7309L5.67183 11.2252C4.0844 11.7837 3 13.2889 3 15C3 17.2091 4.79086 19 7 19H17C19.2091 19 21 17.2091 21 15C21 12.79 19.21 11 17 11C15.233 11 13.7337 12.1457 13.2042 13.7347L11.3064 13.1021C12.1005 10.7185 14.35 9 17 9C17 6.23858 14.7614 4 12 4Z">
                                    </path>
                                </svg>
                                <span class="font-bold text-sm text-green-800">SATUSEHAT</span>
                            </div>
                        @else
                            <div class="flex gap-2 items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="red"
                                    class="w-6 h-6">
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
                            <span class="px-2 py-1 bg-brand-50 text-brand-800 text-xs rounded-full font-bold">
                                {{ str($visit->internal_status)->headline() }}
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
            <x-pagination-compact :paginator="$visits" />
        </div>
    </div>
</div>

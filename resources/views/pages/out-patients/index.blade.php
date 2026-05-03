<?php

use Livewire\Component;
use App\Models\OutpatientVisit;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Location;
use App\Models\Practitioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Transaction;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncEncounterToSatuSehat;
use App\Services\SatuSehatService;

new class extends Component {
    public $patient_id;
    public $practitioner_id = 1;
    public $location_id = 1;
    public $complaint = 'Batuk';
    public $systole = 120;
    public $diastole = 80;
    public $weight = 60;
    public $height = 170;
    public $temperature = 36.5;
    public $registration_fee = 50000;
    public $age = '';
    public $visitHistory;

    public $showModal = false;

    public function openModal()
    {
        $this->reset(['patient_id', 'practitioner_id', 'location_id', 'registration_fee']);
        $this->showModal = true;
    }

    public function updatedPatientId($value)
    {
        if ($value) {
            $patient = Patient::find($value);
            // Mengambil umur dari accessor yang kamu buat di model
            $this->age = $patient->age_string;
            $this->visitHistory = OutpatientVisit::with('patient', 'practitioner', 'invoice')->where('patient_id', $value)->orderBy('created_at', 'desc')->take(3)->get();
        } else {
            $this->currentAge = null;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function save()
    {
        $validated = $this->validate([
            'patient_id' => 'required',
            'practitioner_id' => 'required',
            'location_id' => 'required',
            'registration_fee' => 'required|numeric',
            'complaint' => 'required|string',
            'systole' => 'required|numeric|max:300',
            'diastole' => 'required|numeric|max:200',
            'weight' => 'required|numeric|max:500',
        ]);

        $initial = Auth::user()->clinic->initial;
        // 1. Logic Generate Visit Number (KS-yymmdd-5char)
        $prefix = 'KS-' . now()->format('ymd') . '-';

        // Loop untuk memastikan tidak ada duplikasi di database
        do {
            $randomStr = strtoupper(Str::random(5));
            $visitNumber = $prefix . $randomStr;
        } while (OutpatientVisit::where('visit_number', $visitNumber)->exists());

        // 2. Simpan Kunjungan (Mulai TAT: arrived_at)
        try {
            // 2. Mulai Transaksi
            $visit = DB::transaction(function () use ($validated, $visitNumber) {
                // Simpan Data Kunjungan
                $visit = OutpatientVisit::create([
                    'clinic_id' => Auth::user()->clinic_id,
                    'visit_number' => $visitNumber,
                    'patient_id' => $this->patient_id,
                    'practitioner_id' => $this->practitioner_id,
                    'location_id' => $this->location_id,
                    'status' => 'arrived',
                    'internal_status' => 'arrived',
                    'arrived_at' => now(),
                    'complaint' => $this->complaint,
                ]);

                // Simpan Data Pemeriksaan Awal (Tanda Vital)
                $visit->vitalSign()->create([
                    'clinic_id' => Auth::user()->clinic_id,
                    'systole' => $this->systole,
                    'diastole' => $this->diastole,
                    'weight' => $this->weight,
                    'height' => $this->height,
                    'temperature' => $this->temperature,
                ]);

                // Simpan Invoice
                $visit->invoice()->create([
                    'clinic_id' => Auth::user()->clinic_id,
                    'invoice_number' => 'INV-' . $visitNumber,
                    'registration_fee' => (float) str_replace(',', '', $this->registration_fee),
                    'grand_total' => (float) str_replace(',', '', $this->registration_fee),
                    'payment_status' => 'unpaid',
                ]);

                return $visit; // Kembalikan objek visit agar bisa dipakai di luar closure
            });

            if ($visit) {
                SyncEncounterToSatuSehat::dispatch($visit);
            }

            $this->dispatch('toast', type: 'success', text: 'Order sudah dibayar');
            $this->closeModal();
        } catch (\Exception $e) {
            // Jika ada yang error, transaksi batal otomatis
            Log::error('Gagal Registrasi: ' . $e->getMessage());
            $this->addError('save_error', 'Terjadi kesalahan sistem, silakan coba lagi.');
            $this->dispatch('toast', type: 'error', text: 'Order sudah dibayar');
        }

        // 4. Feedback & Reset
        $this->closeModal();

        // Opsional: Notifikasi sukses ala Flux/Filament
        // Flux::toast(variant: 'success', text: __('Pasien berhasil didaftarkan dengan nomor ' . $visitNumber));
    }

    public function render()
    {
        $patients = Patient::all();
        $locations = Location::all();
        $practitioners = Practitioner::all();
        $outpatientVisits = OutpatientVisit::query()->where('internal_status', 'arrived')->orderBy('created_at', 'desc')->paginate(25);

        return $this->view([
            'patients' => $patients,
            'locations' => $locations,
            'practitioners' => $practitioners,
            'outpatientVisits' => $outpatientVisits,
        ]);
    }
};
?>

<div>
    <x-header header="Rawat Jalan"
        description="Pusat kendali aktivitas klinis mulai dari pemeriksaan tanda-tanda vital <b>(Observation)</b>, penegakan diagnosa <b>(Condition)</b>, hingga pemberian tindakan medis <b>(Procedure)</b>. <br>Seluruh data medis dipetakan menggunakan standar kodifikasi <b>ICD-10 dan ICD-9-CM</b> untuk sinkronisasi otomatis ke <b>Resume Medis SatuSehat.</b>" />

    <x-button wire:click="openModal" class="my-4" color="brand">Registrasi Baru</x-button>

    <div class="hidden md:block">
        <div class="border rounded-lg overflow-x-auto shadow-sm md:mx-0 md:px-0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-brand-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                            Nama
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Status</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            L/P</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Tekanan darah</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Tinggi/Berat badan</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                            Keluhan</th>
                        {{-- <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th> --}}
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($outpatientVisits as $visit)
                        <tr>
                            <td class=" px-6 py-4">
                                <div class=" text-gray-900">{{ $visit->patient->name }}</div>
                            </td>
                            <td class=" px-6 py-4 text-center text-sm  capitalize">
                                {{ $visit->status }}</td>
                            <td class=" px-6 py-4 text-center text-sm ">
                                {{ $visit->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                            <td class=" px-6 py-4 text-center text-sm ">
                                {{ $visit->vitalSign->systole }}/{{ $visit->vitalSign->diastole }} mmHg </td>
                            <td class=" px-6 py-4 text-center text-sm ">
                                {{ $visit->vitalSign->height }} cm /
                                {{ number_format($visit->vitalSign->weight, 1, '.', ',') }} kg </td>
                            <td class="px-6 py-4 text-center text-sm ">
                                {{ $visit->complaint }} </td>
                            {{-- <td class="px-12 py-4 text-right text-sm ">
                                <a class="text-blue-600 hover:text-blue-900 cursor-pointer"
                                    {{ $visit->status === 'finished' ? 'disabled' : '' }}
                                    wire:click="startConsultation({{ $visit->id }})">{{ $visit->status === 'finished' ? 'Finished' : 'Input Diagnosa' }}</a>
                            </td> --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm ">
                                <x-nodatafound />
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="md:block hidden mt-4">
            {{ $outpatientVisits->links() }}
        </div>
    </div>

    <div x-data="{ open: @entangle('showModal') }" x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 dark:bg-gray-800">
            <div class="mb-5">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Registrasi Rawat Jalan Baru</h3>
                <p class="text-sm text-gray-500">Input data kunjungan pasien baru</p>
            </div>

            <flux:separator />

            <div class="space-y-4 mt-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <x-select wire:model.live="patient_id" name="patient_id" label="Nama Pasien">
                            <option value="">-- Pilih Pasien --</option>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-input wire:model="age" name="age" label="Usia" class="mb-4" :disabled="true" />
                </div>


                <div class="grid grid-cols-2 gap-4">
                    <x-select wire:model="location_id" name="location_id" label="Nama Poliklinik">
                        <option value="">-- Pilih Poli --</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model="practitioner_id" name="practitioner_id" label="Nama Dokter">
                        <option value="">-- Pilih Dokter --</option>
                        @foreach ($practitioners as $dr)
                            <option value="{{ $dr->id }}">{{ $dr->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                <x-input wire:model="registration_fee" name="registration_fee" label="Biaya Pendaftaran"
                    class="mb-4" />

                <flux:separator />

                <div class="grid grid-cols-1 gap-4 pt-4 mt-4">
                    <h4 class="font-semibold text-gray-700">Pemeriksaan Awal (Tanda Vital)</h4>

                    <div class="grid grid-cols-3 gap-3">
                        <x-input wire:model="systole" name="systole" label="Systole (mmHg)" />
                        <x-input wire:model="diastole" name="diastole" label="Diastole (mmHg)" />
                        <x-input wire:model="weight" name="weight" label="Weight (Kg)" />
                        <x-input wire:model="height" name="height" label="Height (cm)" />
                        <x-input wire:model="temperature" name="temperature" label="Temp (°C)" />
                    </div>
                    <x-textarea wire:model="complaint" label="Keluhan" name="complaint" rows="3"
                        placeholder="Contoh: Pusing sejak 2 hari lalu..." />
                </div>
            </div>

            @if (isset($visitHistory) && $visitHistory->count() > 0)
                <div class="mt-4 border-t pt-4">
                    <h4 class="font-semibold text-gray-700">Riwayat Kunjungan</h4>
                    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0 mt-2">
                        <table class="w-full text-xs text-left divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-gray-600 uppercase">Tanggal</th>
                                    <th class="px-4 py-2 text-gray-600 uppercase">Dokter</th>
                                    <th class="px-4 py-2 text-gray-600 uppercase">Keluhan</th>
                                    <th class="px-4 py-2 text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-2 text-gray-600 uppercase">Invoice status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($visitHistory as $visit)
                                    <tr>
                                        <td class="px-4 py-2">{{ $visit->arrived_at->format('d M Y') }}</td>
                                        <td class="px-4 py-2">{{ $visit->practitioner->name }}</td>
                                        <td class="px-4 py-2">{{ $visit->complaint }}</td>
                                        <td class="px-4 py-2">{{ str($visit->status)->headline() }}</td>
                                        <td class="px-4 py-2 capitalize">{{ $visit->invoice->payment_status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="open = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Batal
                </button>
                <button wire:click="save"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Daftarkan Pasien
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile version --}}
    <div class="md:hidden space-y-4 pb-4 mt-2">
        @foreach ($outpatientVisits as $visit)
            <div @php
$statusColors = [
                'arrived'   => 'border-l-green-500',
                'completed' => 'border-l-green-500',
                ];

                // Ambil warna berdasarkan status, jika tidak ada di list maka default ke orange
                $borderColor = $statusColors[$visit->internal_status] ?? 'border-orange-500'; @endphp
                class="bg-white dark:bg-zinc-800 rounded-2xl p-4 shadow-sm border border-l-8 {{ $borderColor }}">

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
        <x-pagination-compact :paginator="$outpatientVisits" />
    </div>
</div>

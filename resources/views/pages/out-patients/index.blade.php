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

    <x-button wire:click="openModal" class="mb-4" color="brand">Registrasi Baru</x-button>

    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0">
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
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($outpatientVisits as $visit)
                    <tr>
                        <td class=" px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $visit->patient->name }}</div>
                        </td>
                        <td class=" px-6 py-4 text-center text-sm font-medium capitalize">
                            {{ $visit->status }}</td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->vitalSign->systole }}/{{ $visit->vitalSign->diastole }} mmHg </td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->vitalSign->height }} cm /
                            {{ number_format($visit->vitalSign->weight, 1, '.', ',') }} kg </td>
                        <td class="px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->complaint }} </td>
                        <td class="px-12 py-4 text-right text-sm font-medium">
                            <a class="text-blue-600 hover:text-blue-900 cursor-pointer"
                                {{ $visit->status === 'finished' ? 'disabled' : '' }}
                                wire:click="startConsultation({{ $visit->id }})">{{ $visit->status === 'finished' ? 'Finished' : 'Input Diagnosa' }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $outpatientVisits->links() }}
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

</div>

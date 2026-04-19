<?php

namespace App\Livewire\Pages\Diagnosis;

use Livewire\Component;
use App\Models\OutpatientVisit;
use App\Models\Icd10;
use App\Models\OutPatientDiagnosis;
use App\Models\Medicine;
use App\Services\SatuSehatService;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Jobs\SyncEncounterToSatuSehat;
use App\Jobs\SyncConditionToSatuSehat;
use App\Jobs\SyncMedicationRequestToSatuSehat;
use App\Jobs\FinalizeVisitJob;
use Illuminate\Support\Facades\Bus;

new class extends Component {
    public OutpatientVisit $visit;
    public $search = '';
    public $medicineSearch = '';
    public $selectedMedicineId;
    public $selectedMedicineData = [];
    public $qty;
    public $instruction = '';
    public $isPrimary = false;
    public $selectedIcd10 = null;
    public $kfaResults = [];
    public $confirmDeletePrescription = false;
    public $confirmDeleteDiagnosa = false;
    public $diagnosaId;
    public $prescriptionId;

    public function mount(OutpatientVisit $visit)
    {
        // Load relasi agar tidak N+1
        $this->visit = $visit->load([
            'patient',
            'vitalSign',
            'diagnoses' => function ($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('created_at', 'asc');
            },
            'prescriptions',
        ]);
    }

    public function updatedSearch()
    {
        // Jika user mengetik sesuatu, kita reset pilihan ICD agar dropdown muncul lagi
        $this->selectedIcd10 = null;
    }

    public function selectIcd10($id)
    {
        $this->selectedIcd10 = Icd10::find($id);
        $this->search = '[' . $this->selectedIcd10->code . '] ' . $this->selectedIcd10->name_en;
    }

    // app/Livewire/Pages/Diagnosis/Index.php

    public function addDiagnosis()
    {
        $this->validate([
            'selectedIcd10' => 'required',
        ]);

        // JIKA inputan baru ini ditandai sebagai Primary
        if ($this->isPrimary) {
            // Reset semua diagnosa lain di kunjungan ini agar tidak ada yang Primary
            OutpatientDiagnosis::where('outpatient_visit_id', $this->visit->id)->update(['is_primary' => false]);
        }

        // Baru kemudian insert data yang baru
        OutpatientDiagnosis::create([
            'outpatient_visit_id' => $this->visit->id,
            'icd10_code' => $this->selectedIcd10->code,
            'icd10_display' => $this->selectedIcd10->name_en,
            'is_primary' => $this->isPrimary, // Akan jadi true dan satu-satunya
        ]);

        $this->reset(['search', 'selectedIcd10', 'isPrimary']);
        $this->visit->load('diagnoses');
    }

    public function confirmingDeleteDiagnosis($id)
    {
        $this->confirmDeleteDiagnosa = true;
        $this->diagnosaId = $id;
    }

    public function deleteDiagnosis()
    {
        $diag = OutpatientDiagnosis::find($this->diagnosaId);

        // Jangan izinkan hapus jika sudah sinkron ke SatuSehat
        if ($diag->satusehat_condition_id) {
            $this->dispatch('toast', type: 'error', message: 'Data yang sudah sinkron tidak bisa dihapus!');
            return;
        }

        $diag->delete();
        $this->confirmDeleteDiagnosa = false;
        $this->visit->load('diagnoses'); // Refresh list
    }

    public function setAsPrimary($id)
    {
        // 1. Set semua diagnosa di kunjungan ini menjadi non-primary dulu
        OutpatientDiagnosis::where('outpatient_visit_id', $this->visit->id)->update(['is_primary' => false]);

        // 2. Set diagnosa yang dipilih menjadi primary
        $diag = OutpatientDiagnosis::find($id);
        $diag->update(['is_primary' => true]);

        $this->visit->load([
            'diagnoses' => function ($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('created_at', 'asc');
            },
        ]);
    }

    public function selectMedicineFromKfa($kfaCode)
    {
        // 1. Cek apakah obat sudah ada di database lokal dan sudah tersinkron ke SatuSehat
        $medicine = Medicine::where('kfa_code', $kfaCode)->first();

        if ($medicine && $medicine->satusehat_medication_id) {
            $this->selectedMedicineId = $medicine->id;
            $this->medicineSearch = $medicine->display_name;
            $this->selectedMedicineData = [
                'kfa_code' => $medicine->kfa_code,
                'name' => $medicine->name,
                'manufacturer' => $medicine->manufacturer,
            ];
            $this->kfaResults = [];
            return;
        }

        // 2. Jika belum ada di lokal atau belum tersinkron, ambil data dari list pencarian
        $kfaData = collect($this->kfaResults)->firstWhere('kfa_code', $kfaCode);

        if (!$kfaData) {
            return;
        }

        // 3. Simpan atau Update data obat di database lokal
        $medicine = Medicine::updateOrCreate(
            ['kfa_code' => $kfaCode],
            [
                'name' => $kfaData['name'],
                'display_name' => $kfaData['display'] ?? $kfaData['name'],
                'form_type' => $kfaData['form'] ?? 'Obat',
                'manufacturer' => $kfaData['manufacturer'] ?? null,
                'fix_price' => $kfaData['fix_price'] ?? null,
            ],
        );

        // 4. Sinkronisasi ke SatuSehat hanya jika ID medication masih kosong
        if (!$medicine->satusehat_medication_id) {
            try {
                $service = app(SatuSehatService::class);

                // Daftarkan obat ke SatuSehat
                $res = $service->createMedication($medicine);

                if (isset($res['id'])) {
                    // Simpan UUID yang didapat ke database Intel NUC kamu
                    $medicine->update([
                        'satusehat_medication_id' => $res['id'],
                        'last_synced_at' => now(),
                    ]);

                    $this->dispatch('toast', text: 'Obat berhasil disinkronkan ke SatuSehat.', type: 'success');
                } else {
                    // Log error jika API SatuSehat memberikan issue
                    \Log::error('SatuSehat Medication Error:', $res);
                    $this->dispatch('toast', text: 'Obat disimpan lokal, tapi gagal sync SatuSehat.', type: 'warning');
                }
            } catch (\Exception $e) {
                \Log::error('Gagal akses API SatuSehat: ' . $e->getMessage());
            }
        }

        // Isi state untuk form input
        $this->selectedMedicineId = $medicine->id;
        $this->medicineSearch = $medicine->name; // Nama obat muncul di search field
        $this->selectedMedicineData = [
            'kfa_code' => $medicine->kfa_code,
            'name' => $medicine->name,
            'manufacturer' => $medicine->manufacturer,
        ];

        // Tutup dropdown hasil KFA
        $this->kfaResults = [];
    }

    public function searchKfaAction()
    {
        $results = app(SatuSehatService::class)->searchKfa($this->medicineSearch);

        $data = $results['items']['data'] ?? [];
        $this->kfaResults = collect($data)
            ->filter(function ($item) {
                // HANYA ambil yang active-nya true DAN state-nya 'valid'
                return ($item['active'] ?? false) === true && ($item['state'] ?? '') === 'valid';
            })
            ->map(function ($item) {
                $name = $item['name'] ?? '';
                $search = strtolower($this->medicineSearch);

                // Hitung posisi kata kunci dalam nama obat
                // Jika ada di depan, skor tinggi. Jika tidak ada, skor rendah.
                $pos = strpos(strtolower($name), $search);
                $score = $pos === false ? 1000 : $pos;

                return [
                    'score' => $score,
                    'kfa_code' => $item['kfa_code'],
                    'name' => $name,
                    'display' => $item['nama_dagang'] ?? $name,
                    'form' => $item['dosage_form']['name'] ?? 'Obat',
                    'manufacturer' => $item['manufacturer'] ?? null,
                    'fix_price' => $item['fix_price'] ?? 0,
                ];
            })
            ->sortBy('score') // Semakin kecil posisi (0 = di depan), semakin atas
            ->take(10)
            ->toArray();
    }

    public function updatedMedicineSearch($value)
    {
        // Jika user merubah teks setelah memilih, kita reset ID-nya agar bisa cari ulang
        if ($this->selectedMedicineId) {
            $medicine = Medicine::find($this->selectedMedicineId);
            if ($medicine && $value !== $medicine->display_name) {
                $this->selectedMedicineId = null;
                $this->selectedMedicineData = [];
            } else {
                // Jika nilai sama dengan yang dipilih (karena programmatically set), jangan cari lagi
                return;
            }
        }

        if (empty($value)) {
            $this->kfaResults = [];
            $this->selectedMedicineData = [];
            return;
        }

        if (strlen($value) < 3) {
            $this->kfaResults = [];
            return;
        }

        // 1. Cek Database Lokal dulu (Cari berdasarkan nama atau KFA Code)
        // Gunakan limit agar tidak memberatkan render
        $localMedicines = Medicine::where('name', 'like', '%' . $value . '%')
            ->orWhere('display_name', 'like', '%' . $value . '%')
            ->orWhere('kfa_code', 'like', '%' . $value . '%')
            ->limit(5)
            ->get();

        if ($localMedicines->isNotEmpty()) {
            // Transform data lokal agar formatnya sama dengan hasil KFA
            $this->kfaResults = $localMedicines
                ->map(function ($med) {
                    return [
                        'kfa_code' => $med->kfa_code,
                        'name' => $med->name,
                        'display' => $med->display_name,
                        'is_local' => true, // Penanda bahwa ini data dari NUC
                    ];
                })
                ->toArray();
            // Jika sudah ada hasil lokal yang cukup akurat, kita bisa STOP di sini
            // atau tetap lanjut cari ke KFA jika user ingin variasi lain
            return;
        }

        // 2. Jika di lokal tidak ada, baru tembak API SatuSehat (KFA)
        $this->searchKfaAction();
    }

    public function addPrescription()
    {
        $this->validate([
            'selectedMedicineId' => 'required',
            'qty' => 'required|numeric|min:1',
            'instruction' => 'required|string',
        ]);

        $medicine = Medicine::find($this->selectedMedicineId);

        $this->visit->prescriptions()->create([
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->display_name,
            'qty_ordered' => $this->qty,
            'qty_dispensed' => $this->qty, // Asumsikan langsung sesuai pesanan, bisa diupdate nanti oleh farmasi
            'instruction' => $this->instruction,
            'status' => 'draft',
            // 'sent_to_pharmacy_at' => now(),
        ]);

        // Reset form untuk input obat berikutnya
        $this->reset(['selectedMedicineId', 'selectedMedicineData', 'medicineSearch', 'qty', 'instruction']);

        $this->visit->load('prescriptions'); // Refresh list resep di bawah
    }

    public function confirmingDeletePrescription($id)
    {
        $this->confirmDeletePrescription = true;
        $this->prescriptionId = $id;
    }

    public function deletePrescription()
    {
        Prescription::find($this->prescriptionId)->delete();
        $this->confirmDeletePrescription = false;
        $this->visit->load('prescriptions'); // Refresh list resep di bawah
    }

    public function syncAllToSatuSehat()
    {
        if (!$this->visit->patient) {
            $this->dispatch('toast', text: 'Error: Data pasien tidak ditemukan.', type: 'error');
            return;
        }

        // Pastikan Encounter ID sudah ada sebelum lanjut
        if (!$this->visit->satusehat_encounter_id) {
            // Jika belum ada, paksa kirim Encounter dulu
            SyncEncounterToSatuSehat::dispatch($this->visit);
            $this->dispatch('toaster', message: 'Memulai sinkronisasi Encounter...', type: 'info');
            return;
        }

        // Gunakan Chaining agar urutannya: Diagnosa -> Resep -> Selesai
        Bus::chain([
            new SyncConditionToSatuSehat($this->visit),
            new SyncMedicationRequestToSatuSehat($this->visit),
            // new FinalizeVisitJob($this->visit), // Job baru untuk update status & invoice
        ])->dispatch();

        $this->visit->update([
            'internal_status' => 'sent_to_pharmacy', // Update internal status juga
            'sent_to_pharmacy_at' => now(), // Timestamp untuk tracking internal
        ]);

        Invoice::where('outpatient_visit_id', $this->visit->id)->update([
            'practitioner_fee' => $this->visit->practitioner->fee ?? 0,
            'grand_total' => ($this->visit->practitioner->fee ?? 0) + $this->visit->invoice->registration_fee,
        ]);

        $this->visit->prescriptions()->update([
            'status' => 'sent_to_pharmacy',
            'sent_to_pharmacy_at' => now(),
        ]);

        $this->dispatch('toaster', message: 'Proses sinkronisasi sedang berjalan di background.', type: 'success');
        return redirect()->route('out-patients.index');
    }

    public function sendPrescriptions()
    {
        $service = app(SatuSehatService::class);

        // Ambil resep yang belum dikirim
        $pendingPrescriptions = $this->visit->prescriptions()->whereNull('satusehat_medication_request_id')->get();

        foreach ($pendingPrescriptions as $pres) {
            // KIRIM $this->visit ke service supaya data patient aman
            $result = $service->sendMedicationRequest($pres, $this->visit);

            if (isset($result['id'])) {
                $pres->update([
                    'satusehat_medication_request_id' => $result['id'],
                ]);
            }
        }
    }

    public function render()
    {
        $this->visit->load([
            'diagnoses',
            'prescriptions.medicine', // Sangat penting untuk list obat
        ]);

        $icdResults = [];

        if (strlen($this->search) > 2 && !$this->selectedIcd10) {
            // 1. Ambil semua kode ICD yang sudah diinput untuk kunjungan ini
            $existingCodes = $this->visit->diagnoses()->pluck('icd10_code');

            // 2. Pecah kata kunci pencarian
            $keywords = collect(explode(' ', $this->search))->filter();

            $icdResults = Icd10::query()
                ->where(function ($query) use ($keywords) {
                    // Cek apakah input pertama mirip kode (misal: A25)
                    $query
                        ->where('code', 'ilike', $this->search . '%')
                        // ATAU cari berdasarkan kumpulan kata di nama
                        ->orWhere(function ($nameQuery) use ($keywords) {
                            foreach ($keywords as $word) {
                                $nameQuery->where('name_en', 'ilike', '%' . $word . '%');
                            }
                        });
                })
                ->whereNotIn('code', $existingCodes)
                ->limit(10)
                ->get();
        }

        return $this->view([
            'icdResults' => $icdResults,
        ]);
    }
};
?>

<div>
    <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-6">
        <div class="bg-white border border-brand-200 shadow rounded-lg mb-4 overflow-hidden">
            <div class="bg-brand-600 px-4 py-3">
                <h3 class="text-white font-bold">Pemeriksaan Pasien</h3>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase">Nama Pasien</p>
                    <p class="font-semibold">{{ $visit->patient->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">No. Rekam Medis</p>
                    <p class="font-semibold">{{ $visit->patient->medical_record_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Tanggal Kunjungan</p>
                    <p class="font-semibold">{{ $visit->arrived_at->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Status</p>
                    <p class="font-semibold capitalize">{{ $visit->status }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white border border-brand-200 shadow rounded-lg p-4">
                    <h4 class="font-bold text-gray-700 border-b pb-2 mb-4">Vital Signs</h4>
                    @if ($visit->vitalSign)
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-500">TD (Tensi)</span>
                                <span
                                    class="font-mono font-bold text-brand-600">{{ $visit->vitalSign->systole }}/{{ $visit->vitalSign->diastole }}
                                    <small>mmHg</small></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Berat Badan</span>
                                <span class="font-bold">{{ $visit->vitalSign->weight ?? '-' }} <small>kg</small></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tinggi Badan</span>
                                <span class="font-bold">{{ $visit->vitalSign->height ?? '-' }} <small>cm</small></span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-gray-500 text-sm">Suhu Tubuh</span>
                                <span class="font-bold text-brand-500">{{ $visit->vitalSign->temperature ?? '-' }}
                                    <small>°C</small></span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm italic text-gray-400">Data vital sign belum tersedia.</p>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <div>
                    <div class="bg-white border border-brand-200 shadow rounded-lg p-6">

                        <h4 class="font-bold text-gray-700 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9a1 1 0 112 0v4a1 1 0 11-2 0V9zm5-1a1 1 0 100 2h.01a1 1 0 100-2H12z" />
                            </svg>Input Diagnosa (ICD-10)
                        </h4>

                        <div class="relative" x-data="{ open: true }">
                            <x-input type="search" wire:model.live="search" @input="open = true" :disabled="$visit->status === 'finished'"
                                name="search" placeholder="Ketik kode ICD-10 atau nama penyakit..." name="search" />

                            @if (count($icdResults) > 0)
                                <div x-show="open"
                                    class="absolute z-50 w-full mt-1 bg-white border rounded-md shadow-xl overflow-hidden">
                                    @foreach ($icdResults as $res)
                                        <button wire:click="selectIcd10({{ $res->id }})" @click="open = false"
                                            class="w-full text-left px-4 py-3 hover:bg-brand-50 border-b last:border-0">
                                            <span class="font-bold text-brand-700">[{{ $res->code }}]</span>
                                            <span class="text-sm text-gray-700">{{ $res->name_en }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <x-toggle name="isPrimary" checked="isPrimary" wire:model="isPrimary"
                                label="Diagnosa Utama" />

                            <x-button wire:click="addDiagnosis" variant="brand" :disabled="$visit->status === 'finished'">
                                Tambah ke Daftar
                            </x-button>
                        </div>

                        <div class="mt-8">
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b mb-3 pb-1">
                                Daftar
                                Diagnosa Pasien</h5>
                            <div class="space-y-3">
                                @foreach ($visit->diagnoses as $diag)
                                    <div wire:key="diag-{{ $diag->id }}"
                                        class="flex items-center justify-between p-3 {{ $diag->is_primary ? 'bg-green-50 border-brand-200' : 'bg-gray-50 border-gray-200' }} rounded-lg border transition-all">
                                        <div class="flex items-center space-x-3">
                                            <span
                                                class="px-2 py-1 bg-white border border-gray-300 text-gray-700 rounded text-xs font-mono font-bold">{{ $diag->icd10_code }}</span>
                                            <span
                                                class="text-sm font-medium text-gray-800">{{ $diag->icd10_display }}</span>
                                            @if ($diag->is_primary)
                                                <span
                                                    class="text-[10px] bg-orange-600 text-white px-2 py-0.5 rounded-full uppercase font-extrabold tracking-tighter">Primary</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if ($diag->satusehat_condition_id)
                                                <span
                                                    class="text-[10px] text-green-600 font-bold flex items-center bg-green-50 px-2 py-1 rounded">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                                    </svg>
                                                    SYNCED
                                                </span>
                                            @else
                                                @if (!$diag->is_primary)
                                                    <button wire:click="setAsPrimary({{ $diag->id }})"
                                                        class="text-[11px] text-brand-600 hover:text-brand-800 font-semibold px-2 py-1 border border-brand-200 rounded hover:bg-brand-100 transition">
                                                        Jadikan Utama
                                                    </button>
                                                @endif

                                                <button
                                                    wire:click="confirmingDeleteDiagnosis({{ $diag->id }}, 'diagnosa')"
                                                    class="text-red-400 hover:text-red-600 p-1" title="Hapus">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if ($visit->diagnoses->isEmpty())
                                    <div class="text-center py-6 border-2 border-dashed border-gray-200 rounded-lg">
                                        <p class="text-sm text-gray-400 italic">Belum ada diagnosa yang ditambahkan.</p>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
                <div class="bg-white border border-brand-200 shadow rounded-lg p-6 mt-4">
                    <h4 class="font-bold text-gray-700 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9a1 1 0 112 0v4a1 1 0 11-2 0V9zm5-1a1 1 0 100 2h.01a1 1 0 100-2H12z" />
                        </svg>
                        Resep Obat (KFA)
                    </h4>

                    {{-- Search Obat --}}
                    <div class="relative col-span-2" x-data="{ open: false }">
                        <x-input type="search" wire:model.live.debounce.500ms="medicineSearch" @input="open = true"
                            @focus="open = true" name="medicineSearch"
                            placeholder="Cari obat di KFA (Nama atau Kode)..." :disabled="$visit->status === 'finished'" />

                        @if (!empty($selectedMedicineData))
                            <div class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-lg text-sm text-gray-700">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div>
                                        <div class="text-xs uppercase text-slate-500">KFA Code</div>
                                        <div class="font-semibold">{{ $selectedMedicineData['kfa_code'] }}</div>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="text-xs uppercase text-slate-500">Nama Obat</div>
                                        <div class="font-semibold">{{ $selectedMedicineData['name'] }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase text-slate-500">Manufacturer</div>
                                        <div class="font-semibold">{{ $selectedMedicineData['manufacturer'] ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (count($kfaResults) > 0)
                            <div x-show="open" @click.away="open = false"
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-2xl overflow-hidden max-h-80 overflow-y-auto">

                                @foreach ($kfaResults as $res)
                                    <button type="button"
                                        wire:click="selectMedicineFromKfa('{{ $res['kfa_code'] }}')"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-3 hover:bg-slate-50 border-b border-gray-50 last:border-0 transition-colors duration-150 group">
                                        <div class="flex flex-col">
                                            <div class="flex justify-between items-start mb-1">
                                                <span
                                                    class="text-[10px] font-mono font-bold px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded">
                                                    {{ $res['kfa_code'] }}
                                                </span>
                                                @if (isset($res['manufacturer']))
                                                    <span
                                                        class="text-[10px] uppercase tracking-wider font-semibold text-brand-600">
                                                        {{ $res['manufacturer'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            <span
                                                class="text-sm font-semibold text-gray-800 group-hover:text-brand-700 leading-tight">
                                                {{ $res['name'] }}
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
                        <x-input type="number" wire:model="qty" placeholder="Qty" name="qty_ordered"
                            :disabled="$visit->status === 'finished'" />
                        <div class="col-span-3">
                            <x-input type="text" wire:model="instruction" name="instruction"
                                placeholder="Aturan Pakai" :disabled="$visit->status === 'finished'" />
                        </div>
                        <x-button wire:click="addPrescription" variant="red" :disabled="$visit->status === 'finished'">
                            Tambah
                        </x-button>
                    </div>

                    {{-- Daftar Resep yang sudah ditambah --}}
                    <div class="mt-8 space-y-2">
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b mb-3 pb-1">
                            Daftar
                            Obat Pasien</h5>
                        @foreach ($visit->prescriptions as $pres)
                            @php
                                $statusClasses = match ($pres->status) {
                                    'sent_to_pharmacy' => 'border-l-orange-500 bg-orange-50/30',
                                    'pharmacy_processing', 'sent-for-payment' => 'border-l-yellow-400 bg-yellow-50/30',
                                    'dispensed' => 'border-l-emerald-500 bg-emerald-50/30',
                                    default => 'border-l-gray-300 bg-gray-50',
                                };
                            @endphp
                            <div
                                class="flex justify-between items-center p-3 border-l-8 {{ $statusClasses }} border-y border-r rounded-lg shadow-sm transition-colors">
                                <div>
                                    <span class="font-bold text-gray-800">{{ $pres->medicine->kfa_code }} -
                                        {{ $pres->medicine->name }}</span>
                                    <span class="text-sm text-gray-500 ml-2">({{ $pres->qty_ordered }}
                                        {{ $pres->medicine->unit ?? 'pcs' }} -
                                        {{ $pres->medicine->form_type }})</span>
                                    <p class="text-xs text-red-600 italic">{{ $pres->instruction }}</p>
                                </div>
                                <button wire:click="confirmingDeletePrescription({{ $pres->id }})"
                                    class="text-red-400 hover:text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                        @if ($visit->prescriptions->isEmpty())
                            <div class="text-center py-6 border-2 border-dashed border-gray-200 rounded-lg">
                                <p class="text-sm text-gray-400 italic">Belum ada resep yang ditambahkan.</p>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-4 pt-4 flex justify-end">
                    <x-button wire:click="syncAllToSatuSehat" wire:loading.attr="disabled"
                        wire:target="syncAllToSatuSehat" {{-- TAMBAHKAN INI --}} :disabled="$visit->status === 'finished'" variant="brand">
                        <span wire:loading.remove wire:target="syncAllToSatuSehat">
                            {{ $visit->status === 'finished' ? 'Synced to SatuSehat' : 'Kirim Diagnosa ke SatuSehat' }}
                        </span>

                        <span wire:loading wire:target="syncAllToSatuSehat">
                            Memproses...
                        </span>
                    </x-button>
                </div>
            </div>
        </div>
    </div>
    <x-confirm wire:model="confirmDeletePrescription" title="Hapus obat"
        message="Apakah anda yakin ingin menghapus obat ini?" confirmText="Delete" action="deletePrescription" />
    <x-confirm wire:model="confirmDeleteDiagnosa" title="Hapus diagnosa"
        message="Apakah anda yakin ingin menghapus diagnosa ini?" confirmText="Delete" action="deleteDiagnosis" />
</div>

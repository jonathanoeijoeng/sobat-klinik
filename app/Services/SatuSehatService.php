<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\OutpatientVisit;
use App\Models\Prescription;



class SatuSehatService
{
    protected $baseUrl;
    protected $authUrl;
    protected $clientId;
    protected $clientSecret;
    protected $organizationId;
    protected $orgSatusehatId;
    protected $kfaBaseUrl = 'https://api-satusehat-stg.kemkes.go.id/kfa-v2';

    /**
     * Create a new service instance.
     */

    public function __construct()
    {
        $this->baseUrl = config('services.satusehat.base_url');
        $this->authUrl = config('services.satusehat.auth_url');
        $this->clientId = config('services.satusehat.client_id');
        $this->clientSecret = config('services.satusehat.client_secret');
        $this->organizationId = config('services.satusehat.org_id');

        $this->orgSatusehatId = Organization::find(1)->satusehat_id;
    }

    public function post($resource, $payload)
    {
        $token = $this->getToken();

        if (!$token) {
            Log::error("SATUSEHAT Token tidak ditemukan saat mencoba post ke $resource");
            return false;
        }

        return Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/' . $resource, $payload);
    }

    /**
     * Ambil Access Token dengan Cache 50 menit (3000 detik)
     */
    public function getToken()
    {
        return Cache::remember('satusehat_access_token', 3000, function () {
            $response = Http::asForm()->post($this->authUrl . '/accesstoken?grant_type=client_credentials', [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->failed()) {
                Log::error('SATUSEHAT Auth Failed: ' . $response->body());
                return null;
            }

            return $response->json('access_token');
        });
    }

    public function getPatientByNik($nik)
    {
        $token = $this->getToken();
        $response = Http::withToken($token)
            ->get($this->baseUrl . "/Patient", [
                'identifier' => "https://fhir.kemkes.go.id/id/nik|$nik"
            ]);

        $data = $response->json();

        // Pastikan 'entry' ada dan tidak kosong
        if (!isset($data['entry']) || count($data['entry']) === 0) {
            return ['success' => false, 'message' => 'NIK tidak ditemukan.'];
        }

        $entries = $data['entry'] ?? [];

        $bestMatch = collect($entries)->first(function ($entry) {
            $res = $entry['resource'] ?? [];
            $id = $res['id'] ?? '';

            // Kriteria: Harus Resource Patient, Status Active, dan (Opsional) Awalan P
            return ($res['resourceType'] ?? '') === 'Patient' &&
                ($res['active'] ?? false) === true &&
                str_starts_with($id, 'P');
        });

        // Fallback: Jika kriteria ketat tidak ketemu, cari yang penting ada ID P-nya
        if (!$bestMatch) {
            $bestMatch = collect($entries)->first(function ($entry) {
                return str_starts_with($entry['resource']['id'] ?? '', 'P');
            });
        }

        $resource = $bestMatch['resource'] ?? ($entries[0]['resource'] ?? null);

        return [
            'success' => true,
            'satusehat_id' => $resource['id'] ?? null, // Ini akan mengambil "P02478375538"
            'name' => $resource['name'][0]['text'] ?? 'Nama Tidak Tersedia',
            'birth_date' => $resource['birthDate'] ?? null,
            'gender' => $resource['gender'] ?? null,
        ];
    }
    public function createEncounter($visit)
    {
        $token = $this->getToken();

        if (!$token) {
            return false;
        }

        $visit->refresh();

        $payload = [
            'resourceType' => 'Encounter',
            'status' => 'arrived', // Status awal kedatangan pasien
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB', // Ambulatory (Rawat Jalan)
                'display' => 'ambulatory'
            ],
            'subject' => [
                'reference' => 'Patient/' . $visit->patient->satusehat_patient_id,
                'display' => $visit->patient->name
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender'
                                ]
                            ]
                        ]
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/' . $visit->practitioner->satusehat_id,
                        'display' => $visit->practitioner->name
                    ]
                ]
            ],
            'period' => [
                'start' => $visit->arrived_at->toIso8601String() // Format: 2026-04-12T19:30:00+07:00
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/' . $visit->location->satusehat_id,
                        'display' => $visit->location->name
                    ]
                ]
            ],
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => $visit->arrived_at->toIso8601String()
                    ]
                ]
            ],
            'serviceProvider' => [
                'reference' => 'Organization/' . $this->organizationId
            ],
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $this->orgSatusehatId,
                    'value' => $visit->visit_number // ID unik lokal Anda (KS-xxxxxx)
                ]
            ]
        ];

        return $this->post('Encounter', $payload);
    }

    // app/Services/SatuSehatService.php

    public function createObservationBloodPressure(OutpatientVisit $visit)
    {
        $vitalSign = $visit->vitalSign; // Pastikan relasi ini ada

        $payload = [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs"
                        ]
                    ]
                ]
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "85354-9",
                        "display" => "Blood pressure panel with all children optional"
                    ]
                ]
            ],
            "subject" => [
                "reference" => "Patient/" . $visit->patient->satusehat_patient_id,
                "display" => $visit->patient->name
            ],
            "encounter" => [
                "reference" => "Encounter/" . $visit->satusehat_encounter_id,
                "display" => "Pemeriksaan fisik awal pasien " . $visit->patient->name
            ],
            "performer" => [
                [
                    "reference" => "Practitioner/" . $visit->practitioner->satusehat_id,
                    "display" => $visit->practitioner->name
                ]
            ],
            "effectiveDateTime" => $visit->arrived_at->toIso8601String(),
            "issued" => now()->toIso8601String(),
            "component" => [
                [
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "8480-6",
                                "display" => "Systolic blood pressure"
                            ]
                        ]
                    ],
                    "valueQuantity" => [
                        "value" => (int) $vitalSign->systole,
                        "unit" => "mm[Hg]",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "mm[Hg]"
                    ]
                ],
                [
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "8462-4",
                                "display" => "Diastolic blood pressure"
                            ]
                        ]
                    ],
                    "valueQuantity" => [
                        "value" => (int) $vitalSign->diastole,
                        "unit" => "mm[Hg]",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "mm[Hg]"
                    ]
                ]
            ]
        ];

        return $this->post('Observation', $payload);
    }

    /**
     * Helper untuk mengirim observasi tunggal (Weight, Height, Temp, etc)
     */
    public function createSimpleObservation(OutpatientVisit $visit, $code, $display, $value, $unit, $unitCode)
    {
        $payload = [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs"
                        ]
                    ]
                ]
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => $code,
                        "display" => $display
                    ]
                ]
            ],
            "subject" => [
                "reference" => "Patient/" . $visit->patient->satusehat_patient_id,
                "display" => $visit->patient->name
            ],
            "encounter" => [
                "reference" => "Encounter/" . $visit->satusehat_encounter_id,
                "display" => "Pemeriksaan fisik awal pasien " . $visit->patient->name
            ],
            "performer" => [
                [
                    "reference" => "Practitioner/" . $visit->practitioner->satusehat_id,
                    "display" => $visit->practitioner->name
                ]
            ],
            "effectiveDateTime" => $visit->arrived_at->toIso8601String(),
            "issued" => now()->toIso8601String(),
            "valueQuantity" => [
                "value" => (float) $value,
                "unit" => $unit,
                "system" => "http://unitsofmeasure.org",
                "code" => $unitCode
            ]
        ];

        return $this->post('Observation', $payload);
    }

    // app/Services/SatuSehatService.php

    public function searchKfa($keyword)
    {
        $token = $this->getToken();

        $response = Http::withToken($token)
            ->get("https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2/products/all", [
                'keyword' => $keyword,
                'product_type' => 'obat', // Pastikan huruf kecil
                'page' => 1,
                'size' => 10
            ]);

        return $response->json();
    }

    public function updateEncounterStatusAndDiagnosis($visit, $newStatus)
    {
        $token = $this->getToken();
        $visit->refresh();
        $visit->load('diagnoses');

        $encounterId = $visit->satusehat_encounter_id;

        $response = Http::withToken($token)
            ->get($this->baseUrl . "/Encounter/{$encounterId}");

        $currentEncounter = $response->json();
        $now = now()->toIso8601String();

        // 1. FIX Rule 10122: Tutup status lama
        if (isset($currentEncounter['statusHistory'])) {
            foreach ($currentEncounter['statusHistory'] as &$history) {
                if (!isset($history['period']['end'])) {
                    $history['period']['end'] = $now;
                }
            }
        }

        // 2. Tambahkan status baru ke histori
        $historyEntry = [
            "status" => $newStatus,
            "period" => ["start" => $now]
        ];

        // Jika statusnya finished, harus ada period.end
        if ($newStatus === 'finished') {
            $historyEntry['period']['end'] = $now;
        }
        $currentEncounter['statusHistory'][] = $historyEntry;

        // 3. Update Status Utama
        $currentEncounter['status'] = $newStatus;
        if ($newStatus === 'finished') {
            $currentEncounter['period']['end'] = $now;
        }

        // 4. LOGIKA KRUSIAL: Isi Diagnosis HANYA jika ada data
        $diagnosisPayload = $visit->diagnoses->filter(function ($diag) {
            return !empty($diag->satusehat_condition_id);
        })->map(function ($diag, $index) {
            return [
                "condition" => [
                    "reference" => "Condition/" . $diag->satusehat_condition_id,
                    "display" => $diag->name_en
                ],
                "use" => [
                    "coding" => [[
                        "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                        "code" => ($index === 0) ? "pre-op" : "post-op",
                        "display" => ($index === 0) ? "Primary Diagnosis" : "Secondary Diagnosis"
                    ]]
                ],
                "rank" => $index + 1
            ];
        })->values()->toArray();

        // Jika ada diagnosa, masukkan ke payload. Jika tidak ada, buang key-nya agar tidak error 10457
        if (!empty($diagnosisPayload)) {
            $currentEncounter['diagnosis'] = $diagnosisPayload;
        } else {
            unset($currentEncounter['diagnosis']);
        }

        // 5. Kirim Balik
        $updateResponse = Http::withToken($token)
            ->put($this->baseUrl . "/Encounter/{$encounterId}", $currentEncounter);

        return $updateResponse->json();
    }

    public function sendCondition($diagnosis, $visit)
    {
        $token = $this->getToken();

        $payload = [
            "resourceType" => "Condition",
            "clinicalStatus" => [
                "coding" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                        "code" => "active",
                        "display" => "Active"
                    ]
                ]
            ],
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                            "code" => "encounter-diagnosis",
                            "display" => "Encounter Diagnosis"
                        ]
                    ]
                ]
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://hl7.org/fhir/sid/icd-10",
                        "code" => $diagnosis->icd10_code,
                        "display" => $diagnosis->name_en
                    ]
                ]
            ],
            "subject" => [
                "reference" => "Patient/" . $visit->patient->satusehat_patient_id,
                "display" => $visit->patient->name
            ],
            // TIPS: Beberapa dev SatuSehat menyarankan hapus blok encounter di sini 
            // dan hubungkan HANYA via PUT Encounter diagnosis.
            "encounter" => [
                "reference" => "Encounter/" . $visit->satusehat_encounter_id,
            ],
            "recordedDate" => now()->toIso8601String(),
        ];

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/Condition', $payload);

        $resJson = $response->json();

        // Pastikan ID disimpan ke DB Intel NUC
        if (isset($resJson['id'])) {
            $diagnosis->update(['satusehat_condition_id' => $resJson['id']]);
        }

        return $resJson;
    }

    public function syncPrescription($prescriptionId)
    {
        $prescription = Prescription::with('medicine')->findOrFail($prescriptionId);
        $medicine = $prescription->medicine;
        $service = app(SatuSehatService::class);

        // --- STEP 1: AUTO-SYNC MASTER OBAT JIKA BELUM ADA ID ---
        if (!$medicine->satusehat_medication_id) {
            // Pastikan kode KFA ada sebelum mencoba sync
            if (!$medicine->kfa_code) {
                return $this->dispatch(
                    'toast',
                    text: "Gagal: Kode KFA untuk {$medicine->name} belum diisi!",
                    type: 'error'
                );
            }

            $resMed = $service->createMedication($medicine);

            if (isset($resMed['id'])) {
                // Update tabel medicine di Intel NUC Anda
                $medicine->update(['satusehat_medication_id' => $resMed['id']]);
                $medicine->refresh(); // Segarkan data di memory
            } else {
                return $this->dispatch(
                    'toast',
                    text: "Gagal mendaftarkan master obat ke SatuSehat.",
                    type: 'error'
                );
            }
        }

        // --- STEP 2: KIRIM MEDICATION REQUEST ---
        $resRequest = $service->sendMedicationRequest($prescription, $this->visit);

        if (isset($resRequest['id'])) {
            $prescription->update([
                'satusehat_request_id' => $resRequest['id'],
                'received_at' => now(),
            ]);
            $this->dispatch('toast', text: 'Resep berhasil terkirim!', type: 'success');
        } else {
            $this->dispatch(
                'toast',
                text: 'Gagal kirim resep: ' . ($resRequest['issue'][0]['details']['text'] ?? 'Unknown Error'),
                type: 'error'
            );
        }
    }

    public function sendMedicationRequest($prescription, $visit)
    {

        $qty = (float) $prescription->qty_ordered;
        $freq = (int) ($prescription->frequency_per_day ?: 1);

        $payload = [
            "resourceType" => "MedicationRequest",
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/prescription/" . $this->organizationId,
                    "value" => "PRES-" . $prescription->id
                ]
            ],
            "status" => "active",
            "intent" => "order",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                            "code" => "outpatient",
                            "display" => "Outpatient"
                        ]
                    ]
                ]
            ],
            "medicationReference" => [
                // RUJUKAN KE UUID OBAT YANG SUDAH KITA SIMPAN
                "reference" => "Medication/" . $prescription->medicine->satusehat_medication_id,
                "display" => $prescription->medicine->name
            ],
            "subject" => [
                "reference" => "Patient/" . $visit->patient->satusehat_patient_id
            ],
            "encounter" => [
                "reference" => "Encounter/" . $visit->satusehat_encounter_id
            ],
            "authoredOn" => now()->toIso8601String(),
            "requester" => [
                "reference" => "Practitioner/" . $visit->practitioner->satusehat_id
            ],
            "dosageInstruction" => [
                [
                    "sequence" => 1,
                    "text" => $prescription->instruction, // "2x sehari setelah makan"
                    "timing" => [
                        "repeat" => [
                            "frequency" => $freq, // Minimal 1
                            "period" => 1,
                            "periodUnit" => "d"
                        ]
                    ],
                    "additionalInstruction" => [
                        [
                            "text" => "Setelah makan"
                        ]
                    ],
                    "doseAndRate" => [
                        [
                            "type" => [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                        "code" => "ordered",
                                        "display" => "Ordered"
                                    ]
                                ]
                            ],
                            "doseQuantity" => [
                                "value" => (float) $prescription->qty_ordered, // Pastikan 1.0 atau 1
                                "unit" => "TAB",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "{tablet}" // Gunakan standar UCUM '{tablet}' atau 'TAB'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Logging Payload sebelum dikirim
        Log::info("SatuSehat MedicationRequest Payload:", [
            'url' => $this->baseUrl . '/MedicationRequest',
            'body' => $payload
        ]);

        return Http::withToken($this->getToken())
            ->post($this->baseUrl . '/MedicationRequest', $payload)
            ->json();

        // Logging Response dari SatuSehat
        Log::info("SatuSehat MedicationRequest Response:", [
            'status' => $response->status(),
            'response' => $result
        ]);

        return $result;
    }

    public function createMedication($medicine)
    {
        $token = $this->getToken();

        $payload = [
            "resourceType" => "Medication",
            "meta" => [
                "profile" => [
                    "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"
                ]
            ],
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/medication/" . $this->organizationId,
                    "use" => "official",
                    "value" => (string) $medicine->id // ID obat di database lokal Anda
                ]
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/kfa",
                        "code" => $medicine->kfa_code, // Kode KFA (misal: 93001019)
                        "display" => $medicine->name
                    ]
                ]
            ],
            "status" => "active",
            "extension" => [
                [
                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                "code" => "NC",
                                "display" => "Non-compound"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/Medication', $payload);

        return $response->json();
    }

    public function sendMedicationDispense($prescriptionId)
    {
        $prescription = Prescription::with(['medicine', 'outpatient_visit.patient'])->findOrFail($prescriptionId);
        $visit = $prescription->outpatient_visit;

        $payload = [
            "resourceType" => "MedicationDispense",
            "identifier" => [
                [
                    // Perbaikan Rule 10389: Gunakan ID Organisasi (Angka)
                    "system" => "http://sys-ids.kemkes.go.id/prescription/" . $this->organizationId,
                    "value" => "DISP-" . $prescription->id
                ]
            ],
            "status" => "completed",
            "category" => [
                "coding" => [
                    [
                        // Perbaikan Rule 10048: Tambahkan /fhir/ di URL
                        "system" => "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                        "code" => "community",
                        "display" => "Community"
                    ]
                ]
            ],
            "medicationReference" => [
                "reference" => "Medication/" . $prescription->medicine->satusehat_medication_id
            ],
            "subject" => [
                "reference" => "Patient/" . $visit->patient->satusehat_patient_id
            ],
            "context" => [
                "reference" => "Encounter/" . $visit->satusehat_encounter_id
            ],
            "authorizingPrescription" => [
                [
                    "reference" => "MedicationRequest/" . $prescription->satusehat_medication_request_id
                ]
            ],
            "quantity" => [
                "value" => (float) $prescription->qty_ordered,
                "unit" => $prescription->unit,
                "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm", // Sesuaikan dengan contoh
                "code" => "TAB" // Sesuaikan dengan database kamu, misal TAB/CAPS
            ],
            "whenPrepared" => now()->toIso8601String(),
            "whenHandedOver" => now()->toIso8601String(),
        ];

        Log::info("Payload MedicationDispense ID: " . $prescription->id, $payload);

        return $this->post('/MedicationDispense', $payload);
    }
}

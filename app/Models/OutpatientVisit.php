<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Location;
use App\Models\VitalSign;
use App\Models\OutPatientDiagnosis;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Concerns\BelongsToClinic;


class OutpatientVisit extends Model
{
    use BelongsToClinic;

    protected $table = 'outpatient_visits';
    // protected $fillable = [
    //     'patient_id',
    //     'practitioner_id',
    //     'location_id',
    //     'visit_date',
    //     'visit_type',
    //     'visit_reason',
    //     'status',
    //     'created_at',
    //     'updated_at',
    //     'sync_status',
    //     'satusehat_encounter_id',
    //     'complaint',
    //     'systole',
    //     'diastole',
    //     'weight',
    //     'temperature',
    //     'arrived_at',
    //     'in_progress_at',
    //     'finished_at',
    //     'cancelled_at',
    // ];

    protected $guarded = [];

    public function isSynced(): bool
    {
        return !is_null($this->satusehat_encounter_id);
    }

    public function scopePendingSync($query)
    {
        return $query->where('sync_status', 'pending');
    }

    // app/Models/OutpatientVisit.php
    public function invoice()
    {
        // Gunakan hasOne jika 1 kunjungan hanya punya 1 invoice
        return $this->hasOne(Invoice::class);
    }

    // app/Models/OutpatientVisit.php
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // app/Models/OutpatientVisit.php
    public function practitioner()
    {
        return $this->belongsTo(Practitioner::class);
    }

    // app/Models/OutpatientVisit.php
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function vitalSign()
    {
        return $this->hasOne(VitalSign::class);
    }

    protected $casts = [
        'arrived_at' => 'datetime', // Ini kuncinya!
    ];

    // App/Models/OutpatientVisit.php
    public function diagnoses()
    {
        // Gunakan outpatient_visit_id sesuai nama kolom di tabel out_patient_diagnoses
        return $this->hasMany(OutpatientDiagnosis::class, 'outpatient_visit_id')
            ->orderBy('is_primary', 'desc') // True (1) akan di atas False (0)
            ->orderBy('created_at', 'desc'); // Yang terbaru di atas jika sama-sama sekunder
    }

    public function prescriptions()
    {
        // Pastikan foreign key sesuai dengan yang kamu buat di migrasi
        return $this->hasMany(Prescription::class, 'outpatient_visit_id')
            ->latest(); // Supaya obat yang baru diinput ada di atas
    }

    // app/Models/OutpatientVisit.php

    // app/Models/OutpatientVisit.php

    // app/Models/Visit.php

    public function getSatuSehatSyncStatus()
    {
        $status = [
            'is_synced' => false,
            'missing_resources' => [],
        ];

        $internalStatus = strtolower($this->internal_status);

        // 1. TAHAP ENCOUNTER (Data ada di tabel visit itu sendiri)
        if (!$this->satusehat_encounter_id) {
            $status['missing_resources'][] = 'Encounter';
        }

        // 2. TAHAP OBSERVATION (Data ada di tabel vitalsign)
        // Asumsi relasi: $this->vitalsign
        $vitals = $this->vitalSign;
        if (!$vitals) {
            $status['missing_resources'][] = 'Semua Vital Sign';
        } else {
            // Cek satu per satu ID SatuSehat-nya
            if (!$vitals->satusehat_observation_blood_pressure_id) $status['missing_resources'][] = 'Obs: Blood Pressure';
            if (!$vitals->satusehat_observation_weight_id) $status['missing_resources'][] = 'Obs: Weight';
            if (!$vitals->satusehat_observation_height_id) $status['missing_resources'][] = 'Obs: Height';
            if (!$vitals->satusehat_observation_temperature_id) $status['missing_resources'][] = 'Obs: Temperature';
        }

        // 3. TAHAP CONDITION (Data ada di tabel outpatient_diagnose)
        // Asumsi relasi: $this->diagnoses
        if (in_array($internalStatus, ['sent_to_pharmacy', 'finished', 'dispensed'])) {
            $hasSyncedDiagnosis = $this->diagnoses()->whereNotNull('satusehat_condition_id')->exists();
            if (!$hasSyncedDiagnosis) {
                $status['missing_resources'][] = 'Condition (Diagnosis)';
            }
        }

        // 4. TAHAP RESEP & DISPENSE (Data ada di tabel prescription)
        // Asumsi relasi: $this->prescription
        $prescription = $this->prescription;
        if ($prescription) {
            // Cek Medication Request
            if (!$prescription->satusehat_medication_request_id) {
                $status['missing_resources'][] = 'Medication Request';
            }

            // Cek Medication Dispense (Jika tebus internal & status sudah dispensed)
            if (!$this->external && $internalStatus === 'dispensed') {
                if (!$prescription->satusehat_medication_dispense_id) {
                    $status['missing_resources'][] = 'Medication Dispense';
                }
            }
        } elseif ($this->has_prescription) {
            $status['missing_resources'][] = 'Prescription Data';
        }

        $status['is_synced'] = empty($status['missing_resources']);
        return $status;
    }
}

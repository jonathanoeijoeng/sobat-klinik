<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\BelongsToClinic;
use App\Models\OutPatientVisit;
use App\Models\Icd10;

class OutPatientDiagnosis extends Model
{
    use BelongsToClinic;


    protected $table = 'out_patient_diagnoses';
    protected $guarded = [];

    public function outpatient_visit()
    {
        return $this->belongsTo(OutPatientVisit::class, 'outpatient_visit_id');
    }
    public function icd10()
    {
        return $this->belongsTo(Icd10::class);
    }
}

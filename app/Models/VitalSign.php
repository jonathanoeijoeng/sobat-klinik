<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\BelongsToClinic;
use App\Models\OutpatientVisit;

class VitalSign extends Model
{
    use BelongsToClinic;
    protected $guarded = [];

    public function outpatient_visit()
    {
        return $this->belongsTo(OutpatientVisit::class);
    }
    protected $table = 'vital_signs';
}

<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\OutpatientVisit;
use App\Concerns\BelongsToClinic;

class Invoice extends Model
{
    use BelongsToClinic;

    protected $guarded = [];

    public function outpatient_visit()
    {
        return $this->belongsTo(OutpatientVisit::class, 'outpatient_visit_id');
    }
}

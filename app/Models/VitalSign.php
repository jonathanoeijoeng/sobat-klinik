<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    // protected $fillable = ['visit_id', 'systole', 'diastole', 'weight', 'height', 'temperature'];
    protected $guarded = [];

    public function visit()
    {
        return $this->belongsTo(OutpatientVisit::class);
    }
    protected $table = 'vital_signs';
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\BelongsToClinic;

class Prescription extends Model
{
    use BelongsToClinic;

    protected $table = 'prescriptions';
    protected $guarded = [];

    public function medicine()
    {
        // Pastikan foreign_key di tabel prescriptions bernama medicine_id
        return $this->belongsTo(Medicine::class);
    }

    public function outpatient_visit()
    {
        // Pastikan foreign_key di tabel prescriptions bernama visit_id
        return $this->belongsTo(OutpatientVisit::class, 'outpatient_visit_id');
    }
}

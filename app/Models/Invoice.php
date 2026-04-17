<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = [];

    public function visit()
    {
        return $this->belongsTo(OutpatientVisit::class, 'visit_id');
    }
}

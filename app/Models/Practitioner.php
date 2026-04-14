<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Practitioner extends Model
{
    protected $table = 'practitioners';
    protected $fillable = [
        'nik',
        'name',
        'satusehat_id',
        'ihs_number',
        'sip',
        'profession_type',
        'is_active',
        'fee',
    ];
}

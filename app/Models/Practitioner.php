<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\BelongsToClinic;

class Practitioner extends Model
{
    use BelongsToClinic;

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

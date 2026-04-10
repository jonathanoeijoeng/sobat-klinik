<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    protected $fillable = [
        'satusehat_id',
        'nik',
        'name',
        'gender',
        'birth_date',
        'phone_number',
        'address',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'last_sync_at',
    ];
}

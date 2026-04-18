<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 
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

    public function getAgeAttribute()
    {
        return \Carbon\Carbon::parse($this->birth_date)->age;
    }

    public function getAgeStringAttribute()
    {
        $diff = \Carbon\Carbon::parse($this->birth_date)->diff(now());
        return "{$diff->y} th, {$diff->m} bln, {$diff->d} hr";
    }

    // Di dalam file Patient.php

    protected function medicalRecordNumber(): Attribute
    {
        return Attribute::make(
            // Saat dipanggil di View: 2604180001 -> RM-260418-0001
            get: function ($value) {
                if (!$value) return '-';
                // Misal: YYMMDD (6 digit) + Urutan (4 digit) = 10 digit
                $datePart = substr($value, 0, 6);
                $seqPart = substr($value, 6);
                return "RM-{$datePart}-{$seqPart}";
            },
            // Saat disimpan ke DB: RM-260418-0001 -> 2604180001
            set: fn ($value) => preg_replace('/[^0-9]/', '', $value)
        );
    }
}

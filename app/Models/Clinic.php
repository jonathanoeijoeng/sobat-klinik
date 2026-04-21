<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $table = 'clinics';
    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('active_until')
                ->orWhere('active_until', '>=', now()->startOfDay());
        });
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->active_until) || $this->active_until >= now()->startOfDay();
    }

    protected $casts = [
        'active_until' => 'date:Y-m-d', // Otomatis terformat saat ditarik ke array/JSON
    ];

    public function practitioners()
    {
        return $this->hasMany(Practitioner::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function outpatientVisits()
    {
        return $this->hasMany(OutpatientVisit::class);
    }
}

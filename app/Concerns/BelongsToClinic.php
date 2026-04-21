<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToClinic
{
    protected static function bootBelongsToClinic()
    {
        // 1. OTOMATIS FILTER: Setiap kali query (Select, Update, Delete)
        static::addGlobalScope('clinic', function (Builder $builder) {
            if (Auth::check() && Auth::user()->clinic_id) {
                $builder->where('clinic_id', Auth::user()->clinic_id);
            }
        });

        // 2. OTOMATIS ISI: Setiap kali membuat data baru (Insert)
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->clinic_id) {
                // Jika clinic_id belum diisi manual, ambil dari user yang login
                if (!$model->clinic_id) {
                    $model->clinic_id = Auth::user()->clinic_id;
                }
            }
        });
    }

    /**
     * Relasi ke Model Clinic
     */
    public function clinic()
    {
        return $this->belongsTo(\App\Models\Clinic::class);
    }
}

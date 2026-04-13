<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Icd10 extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name_en',
        'name_id',
        'is_active'
    ];

    /**
     * Scope untuk pencarian cepat di Livewire Autocomplete
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('code', 'like', $term . '%')
            ->orWhere('name_id', 'like', '%' . $term . '%')
            ->where('is_active', true);
    }
}

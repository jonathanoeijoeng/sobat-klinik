<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\BelongsToClinic;

class Medicine extends Model
{
    use BelongsToClinic;
    protected $table = 'medicines';
    protected $guarded = [];

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}

<?php

namespace App\Models;

use App\Concerns\BelongsToClinic;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use BelongsToClinic;
}

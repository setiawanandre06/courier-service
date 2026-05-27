<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'vehicle_type',
        'vehicle_plate',
        'level',
    ];
}

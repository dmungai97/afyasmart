<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    protected $fillable = [
        'name', 'location', 'address', 'phone', 'email',
        'latitude', 'longitude', 'opening_hours', 'open_24hrs', 'open',
    ];

    protected $casts = [
        'open_24hrs' => 'boolean',
        'open'       => 'boolean',
    ];
}
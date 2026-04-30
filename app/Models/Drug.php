<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Drug extends Model
{
    protected $fillable = [
        'name', 'generic_name', 'category', 'uses',
        'dosage', 'side_effects', 'pregnancy_safe',
        'alcohol_safe', 'lactation_safe', 'prescription_required',
    ];

    protected $casts = [
        'pregnancy_safe'  => 'boolean',
        'alcohol_safe'    => 'boolean',
        'lactation_safe'  => 'boolean',
    ];
}
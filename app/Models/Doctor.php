<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'name',
        'specialization',
        'hospital',
        'location',
        'region',             // ← add: needed for seeder + future region filter
        'phone',
        'email',
        'latitude',
        'longitude',
        'experience_years',
        'rating',
        'availability',
        'available',
    ];

    protected $casts = [
        'available'        => 'boolean',  // true/false, never 0/1 in API responses
        'rating'           => 'float',
        'latitude'         => 'float',    // ← add: prevents string comparison in Haversine
        'longitude'        => 'float',    // ← add: same reason
        'experience_years' => 'integer',  // ← add: clean int in JSON, not "8"
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Filter to only available doctors.
     * Usage: Doctor::available()->get()
     */
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    /**
     * Filter by region (case-insensitive).
     * Usage: Doctor::inRegion('nairobi')->get()
     */
    public function scopeInRegion($query, string $region)
    {
        return $query->where('region', strtolower($region));
    }

    /**
     * Search across name, hospital, and specialization.
     * Usage: Doctor::search('cardio')->get()
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name',            'like', "%{$term}%")
              ->orWhere('hospital',       'like', "%{$term}%")
              ->orWhere('specialization', 'like', "%{$term}%");
        });
    }

    /**
     * Returns the SQL SELECT expression for Haversine distance.
     * Kept here so both DoctorController and any future jobs share one source of truth.
     *
     * Usage: Doctor::selectRaw(Doctor::distanceSql(), Doctor::distanceBindings($lat, $lng))
     */
    public static function distanceSql(): string
    {
        return "*, ROUND(
            6371 * 2 * ATAN2(
                SQRT(
                    POW(SIN(RADIANS(latitude  - ?) / 2), 2) +
                    COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                    POW(SIN(RADIANS(longitude - ?) / 2), 2)
                ),
                SQRT(1 - (
                    POW(SIN(RADIANS(latitude  - ?) / 2), 2) +
                    COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                    POW(SIN(RADIANS(longitude - ?) / 2), 2)
                ))
            )
        , 1) AS distance_km";
    }

    public static function distanceBindings(float $lat, float $lng): array
    {
        // 6 bindings match the 6 ? placeholders in distanceSql()
        return [$lat, $lat, $lng, $lat, $lat, $lng];
    }
}
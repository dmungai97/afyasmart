<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $lat            = $request->query('lat');
        $lng            = $request->query('lng');
        $specialization = $request->query('specialization');
        $available      = $request->query('available');
        $search         = $request->query('search');
        $region         = $request->query('region');
        $hasGps         = $lat !== null && $lng !== null;
        $hasRadius      = $request->has('radius'); // only true when explicitly passed

        $query = $hasGps
            ? Doctor::selectRaw(Doctor::distanceSql(), Doctor::distanceBindings((float) $lat, (float) $lng))
            : Doctor::query();

        // ── Filters ──────────────────────────────────────────────────────────

        if ($specialization) {
            $query->where('specialization', 'like', "%{$specialization}%");
        }

        if ($available !== null && $available !== '') {
            $query->where('available', filter_var($available, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search) {
            $query->search($search);
        }

        if ($region) {
            $query->inRegion($region);
        }

        // ── GPS sorting + optional radius cutoff ─────────────────────────────

        if ($hasGps && $hasRadius) {
            // Hard radius cutoff — "Near me" mode
            $radius = (float) $request->query('radius', 50);
            $query->havingRaw('distance_km <= ?', [$radius])
                  ->orderBy('distance_km');
        } elseif ($hasGps) {
            // Sort by proximity but show everyone — default national browsing
            $query->orderBy('distance_km');
        }

        $doctors = $query->get();

        return response()->json([
            'status' => 'success',
            'count'  => $doctors->count(),
            'data'   => $doctors,
        ]);
    }

    public function show(Doctor $doctor)
    {
        return response()->json(['status' => 'success', 'data' => $doctor]);
    }

    public function regions()
    {
        $regions = Doctor::select('region')
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return response()->json(['status' => 'success', 'data' => $regions]);
    }

    public function specializations()
    {
        $specs = Doctor::select('specialization')
            ->whereNotNull('specialization')
            ->distinct()
            ->orderBy('specialization')
            ->pluck('specialization');

        return response()->json(['status' => 'success', 'data' => $specs]);
    }
}
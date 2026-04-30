<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function index(Request $request)
    {
        $query = Pharmacy::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->orderBy('open', 'desc')->orderBy('name')->get(),
        ]);
    }

    public function show(Pharmacy $pharmacy)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $pharmacy,
        ]);
    }
}
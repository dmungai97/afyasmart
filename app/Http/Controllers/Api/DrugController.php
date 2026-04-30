<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function index(Request $request)
    {
        $query = Drug::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('generic_name', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->orderBy('name')->get(),
        ]);
    }

    public function show(Drug $drug)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $drug,
        ]);
    }
}
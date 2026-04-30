<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AfyaLinkController extends Controller
{
    private function getToken(): string|null
    {
        $username = env('AFYALINK_USERNAME');
        $password = env('AFYALINK_PASSWORD');
        $key      = env('AFYALINK_KEY');
        $baseUrl  = env('AFYALINK_BASE_URL', 'https://uat.dha.go.ke');

        $credentials = base64_encode("{$username}:{$password}");

        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
        ])->get("{$baseUrl}/v1/hie-auth?key={$key}");

        if ($response->successful()) {
            $body = $response->json();
            return $body['token'] ?? trim($response->body(), '"');
        }

        return null;
    }

    public function searchPractitioner(Request $request)
    {
        $request->validate([
            'identification_number' => 'required|string',
        ]);

        $token = $this->getToken();

        if (!$token) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not authenticate with Health Worker Registry.',
            ], 503);
        }

        $baseUrl = env('AFYALINK_BASE_URL', 'https://uat.dha.go.ke');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
        ])->get("{$baseUrl}/v1/practitioner-search", [
            'national-id' => $request->identification_number,
        ]);

        \Log::info('AfyaLink response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        $data = $response->json();

        if (isset($data['message']['found']) && $data['message']['found'] == 1) {
            return response()->json([
                'status' => 'success',
                'data'   => $data['message'],
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Practitioner not found.',
        ], 404);
    }

    public function searchFacility(Request $request)
    {
        $request->validate([
            'facility_code' => 'required|string',
        ]);

        $token = $this->getToken();

        if (!$token) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not authenticate with Health Worker Registry.',
            ], 503);
        }

        $baseUrl = env('AFYALINK_BASE_URL', 'https://uat.dha.go.ke');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
        ])->get("{$baseUrl}/v1/facility-search", [
            'facility_code' => $request->facility_code,
        ]);

        \Log::info('AfyaLink facility response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        $data = $response->json();

        if (isset($data['message']['found']) && $data['message']['found'] == 1) {
            return response()->json([
                'status' => 'success',
                'data'   => $data['message'],
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Facility not found.',
        ], 404);
    }
}
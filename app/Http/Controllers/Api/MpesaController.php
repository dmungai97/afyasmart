<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(private MpesaService $mpesa) {}

    // ── 1. Initiate STK Push ─────────────────────────────────────
    public function initiate(Request $request)
    {
        $request->validate([
            'phone'        => 'required|string',
            'plan'         => 'required|in:daily,weekly,monthly',
            'firebase_uid' => 'nullable|string',
        ]);

        $amount = match($request->plan) {
            'daily'   => 20,
            'weekly'  => 100,
            'monthly' => 200,
        };

        try {
            $result = $this->mpesa->stkPush(
                phone:     $request->phone,
                amount:    $amount,
                reference: strtoupper($request->plan)
            );

            if (($result['ResponseCode'] ?? null) !== '0') {
                Log::error('STK Push failed', $result);
                return response()->json([
                    'status'  => 'error',
                    'message' => $result['errorMessage'] ?? 'STK Push failed. Try again.',
                ], 422);
            }

            $checkoutId = $result['CheckoutRequestID'];

            Cache::put("mpesa_{$checkoutId}", [
                'firebase_uid' => $request->firebase_uid,
                'plan'         => $request->plan,
            ], now()->addMinutes(5));

            return response()->json([
                'status'              => 'success',
                'message'             => 'STK Push sent. Enter your M-Pesa PIN.',
                'checkout_request_id' => $checkoutId,
            ]);

        } catch (\Exception $e) {
            Log::error('MpesaService error', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not connect to M-Pesa. Please try again.',
            ], 503);
        }
    }

    // ── 2. Poll status (frontend polls every 3s) ─────────────────
    public function status(Request $request)
    {
        $request->validate([
            'checkout_request_id' => 'required|string',
        ]);

        $checkoutId = $request->checkout_request_id;

        // ── Already confirmed via callback ───────────────────────
        if (Cache::get("mpesa_paid_{$checkoutId}")) {
            Cache::forget("mpesa_paid_{$checkoutId}");
            return response()->json([
                'status'  => 'success',
                'paid'    => true,
                'message' => 'Payment confirmed.',
            ]);
        }

        // ── Query Daraja directly ────────────────────────────────
        try {
            $result     = $this->mpesa->stkQuery($checkoutId);
            $resultCode = $result['ResultCode'] ?? null;

            if ($resultCode === '0' || $resultCode === 0) {
                $cached = Cache::get("mpesa_{$checkoutId}");
                if ($cached) {
                    Cache::forget("mpesa_{$checkoutId}");
                }

                return response()->json([
                    'status'  => 'success',
                    'paid'    => true,
                    'message' => 'Payment confirmed.',
                ]);
            }

            if ($resultCode === '1032') {
                Cache::forget("mpesa_{$checkoutId}");
                return response()->json([
                    'status'  => 'cancelled',
                    'paid'    => false,
                    'message' => 'Payment cancelled by user.',
                ]);
            }

            // Still pending
            return response()->json([
                'status'  => 'pending',
                'paid'    => false,
                'message' => 'Waiting for payment confirmation.',
            ]);

        } catch (\Exception $e) {
            // Daraja query failed — return pending instead of 500
            Log::warning('STK Query failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'pending',
                'paid'    => false,
                'message' => 'Awaiting confirmation.',
            ]);
        }
    }

    // ── 3. Daraja callback (called by Safaricom) ─────────────────
    public function callback(Request $request)
    {
        Log::info('M-Pesa callback', $request->all());

        $body       = $request->input('Body.stkCallback');
        $resultCode = $body['ResultCode'] ?? null;
        $checkoutId = $body['CheckoutRequestID'] ?? null;

        if ($resultCode === 0 && $checkoutId) {
            $cached = Cache::get("mpesa_{$checkoutId}");

            if ($cached) {
                Cache::put("mpesa_paid_{$checkoutId}", true, now()->addMinutes(5));
                Cache::forget("mpesa_{$checkoutId}");
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MpesaService
{
    private string $env;
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortcode;
    private string $passkey;
    private string $callbackUrl;

    public function __construct()
    {
        $this->env            = config('services.mpesa.env');
        $this->consumerKey    = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->shortcode      = config('services.mpesa.shortcode');
        $this->passkey        = config('services.mpesa.passkey');
        $this->callbackUrl    = config('services.mpesa.callback_url');
    }

    private function baseUrl(): string
    {
        return $this->env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    // ── Get OAuth token ──────────────────────────────────────────
    public function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get("{$this->baseUrl()}/oauth/v1/generate?grant_type=client_credentials");

        return $response->json('access_token');
    }

    // ── Initiate STK Push ────────────────────────────────────────
    public function stkPush(string $phone, int $amount, string $reference): array
    {
        $token     = $this->getAccessToken();
        $timestamp = Carbon::now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

        // Normalize phone: 07XXXXXXXX → 2547XXXXXXXX
        $phone = preg_replace('/^0/', '254', $phone);
        $phone = preg_replace('/^\+/', '', $phone);

        $response = Http::withToken($token)
            ->post("{$this->baseUrl()}/mpesa/stkpush/v1/processrequest", [
                'BusinessShortCode' => $this->shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => $amount,
                'PartyA'            => $phone,
                'PartyB'            => $this->shortcode,
                'PhoneNumber'       => $phone,
                'CallBackURL'       => $this->callbackUrl,
                'AccountReference'  => $reference,
                'TransactionDesc'   => "AfyaSmart {$reference} subscription",
            ]);

        return $response->json();
    }

    // ── Query STK Push status ────────────────────────────────────
    public function stkQuery(string $checkoutRequestId): array
    {
        $token     = $this->getAccessToken();
        $timestamp = Carbon::now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $response = Http::withToken($token)
            ->post("{$this->baseUrl()}/mpesa/stkpushquery/v1/query", [
                'BusinessShortCode' => $this->shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json();
    }
}
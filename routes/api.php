<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DrugController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\AfyaLinkController;
use App\Http\Controllers\Api\MpesaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ───────────────────────────────────────────────
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);

    // ── M-Pesa callback — PUBLIC (Safaricom calls this, no token) ──
    Route::post('/mpesa/callback', [MpesaController::class, 'callback']);

    // ── Protected ────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me',      [AuthController::class, 'me']);

        // Chat
        Route::post('/chat/send',      [ChatController::class, 'send']);
        Route::get('/chat/history',    [ChatController::class, 'history']);
        Route::get('/chat/status',     [ChatController::class, 'status']);
        Route::post('/chat/subscribe', [ChatController::class, 'resetOnSubscribe']);

        // M-Pesa
        Route::post('/mpesa/initiate', [MpesaController::class, 'initiate']);
        Route::post('/mpesa/status',   [MpesaController::class, 'status']);

        // Drugs
        Route::get('/drugs',        [DrugController::class, 'index']);
        Route::get('/drugs/{drug}', [DrugController::class, 'show']);

        // Doctors
        Route::get('/doctors/regions',         [DoctorController::class, 'regions']);
        Route::get('/doctors/specializations', [DoctorController::class, 'specializations']);
        Route::get('/doctors',                 [DoctorController::class, 'index']);
        Route::get('/doctors/{doctor}',        [DoctorController::class, 'show']);

        // Pharmacies
        Route::get('/pharmacies',            [PharmacyController::class, 'index']);
        Route::get('/pharmacies/{pharmacy}', [PharmacyController::class, 'show']);

        // AfyaLink
        Route::get('/verify-practitioner', [AfyaLinkController::class, 'searchPractitioner']);
        Route::get('/search-facility',     [AfyaLinkController::class, 'searchFacility']);

    });

});
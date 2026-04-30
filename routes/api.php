<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DrugController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\AfyaLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ───────────────────────────────────────────────────────────────
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);

    // ── Protected ────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me',      [AuthController::class, 'me']);

        // Chat
        Route::post('/chat/send',   [ChatController::class, 'send']);
        Route::get('/chat/history', [ChatController::class, 'history']);

        // Drugs
        Route::get('/drugs',        [DrugController::class, 'index']);
        Route::get('/drugs/{drug}', [DrugController::class, 'show']);

        // Doctors
        // ⚠️  /doctors/regions MUST come before /doctors/{doctor}
        // otherwise Laravel treats the string "regions" as a {doctor} binding
        // In the doctors block — both static routes before the {doctor} wildcard
        Route::get('/doctors/regions',          [DoctorController::class, 'regions']);
        Route::get('/doctors/specializations',  [DoctorController::class, 'specializations']);
        Route::get('/doctors',                  [DoctorController::class, 'index']);
        Route::get('/doctors/{doctor}',         [DoctorController::class, 'show']);

        // Pharmacies
        Route::get('/pharmacies',            [PharmacyController::class, 'index']);
        Route::get('/pharmacies/{pharmacy}', [PharmacyController::class, 'show']);

        // AfyaLink — practitioner & facility verification
        Route::get('/verify-practitioner', [AfyaLinkController::class, 'searchPractitioner']);
        Route::get('/search-facility',     [AfyaLinkController::class, 'searchFacility']);

    });

});
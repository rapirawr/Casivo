<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

// =============================================
// ESP32 Sensor API Endpoints
// =============================================

// POST: ESP32 kirim data sensor ke sini
Route::post('/sensor', [SensorController::class, 'store']);

// GET: Ambil data sensor terbaru (dipakai oleh dashboard polling)
Route::get('/sensor/latest', [SensorController::class, 'latest']);

// GET: Ambil riwayat data sensor
Route::get('/sensor/history', [SensorController::class, 'history']);

// =============================================
// Default Laravel Auth
// =============================================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

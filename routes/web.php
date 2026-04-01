<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;

use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DeviceController::class, 'index'])->name('dashboard');
Route::get('/room/{name}', [DeviceController::class, 'roomShow'])->name('rooms.show');

// Device control
Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
Route::post('/devices/{device}/toggle', [DeviceController::class, 'toggle'])->name('devices.toggle');
Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

// Rooms
Route::post('/rooms', [\App\Http\Controllers\RoomController::class, 'store'])->name('rooms.store');

// Sensor data (used by dashboard polling)
Route::get('/api/sensors', [DeviceController::class, 'sensorData'])->name('sensors.data');

// routes/web.php
Route::get('/ble', function () {
    return view('ble');
});

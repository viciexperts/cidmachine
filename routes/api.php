<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\PhonePoolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthTokenController::class, 'login'])->name('login');
Route::middleware('auth:sanctum')->post('/logout', [AuthTokenController::class, 'logout'])->name('logout');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Returns a phone from the pool with same or closest area code to the provided caller_id
    Route::post('/phone-pool/assign', [PhonePoolController::class, 'assignFromPool']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationRecordController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
// ->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/change-password', [PasswordController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/send-reset-link', [PasswordController::class, 'sendResetLink'])->middleware('throttle:3,60');
Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

Route::put('/update-email', [UserController::class, 'updateEmail'])->middleware('auth:sanctum');
Route::put('/edit-user-information', [UserController::class, 'updateInformation'])->middleware('auth:sanctum');

Route::apiResource('patients', PatientController::class);
Route::apiResource('consultation-records', ConsultationRecordController::class);
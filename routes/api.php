<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;

Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/change-password', [PasswordController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/send-reset-link', [PasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordController::class, 'resetPassword']);
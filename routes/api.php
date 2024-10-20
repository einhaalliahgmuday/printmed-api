<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhysicianPatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;

//authentication and password-related routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::put('/change-password', [AuthController::class,'changePassword'])->middleware('auth:sanctum');

//users route
Route::get('/users', [UserController::class, 'getUsers']);
Route::get('/users/physicians', [UserController::class, 'getPhysicians']);
Route::get('/users/count', [UserController::class, 'getUsersCount']);
Route::put('/users/update-email', [UserController::class, 'updateEmail'])->middleware('auth:sanctum');
Route::put('/users/update-information', [UserController::class, 'updateInformation'])->middleware('auth:sanctum');
Route::put('/users/unrestrict-account', [UserController::class, 'unrestrictAccount']);
Route::put('/users/toggle-lock', [UserController::class,'toggleLockUserAccount']);

//assign physician
Route::post('/assign-patient-physician', [PhysicianPatientController::class, 'assignPatientPhysician'])->middleware('auth:sanctum');

//patient route
Route::apiResource('patients', PatientController::class)->middleware('auth:sanctum');
Route::get('/patients/is-exists', [PatientController::class, 'isPatientExists'])->middleware('auth:sanctum');
Route::get('/patients/count', [PatientController::class, 'getPatientsCount']);  

//consultation records route
Route::apiResource('consultation-records', ConsultationRecordController::class)->except(['index'])->middleware('auth:sanctum');

//payment records route
Route::apiResource('payments', PaymentController::class)->except(['store']);
Route::get('/payments/total', [PaymentController::class, 'getPaymentsTotal']);  

//queue routes
Route::apiResource('queue', QueueController::class)->except(['index', 'update', 'show']);
Route::post('/queue/increment-total', [QueueController::class, 'incrementQueueTotal']);
Route::post('/queue/increment-current', [QueueController::class, 'incrementQueueCurrent']);
Route::post('/queue/clear', [QueueController::class, 'clearQueue']);
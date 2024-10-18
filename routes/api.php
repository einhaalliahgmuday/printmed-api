<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationRecordController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhysicianPatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;

//login and registration routes
Route::post('/register', [UserController::class, 'register']);
// ->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

//password-related routes
Route::post('/change-password', [PasswordController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/send-reset-link', [PasswordController::class, 'sendResetLink'])->middleware('throttle:3,60');
Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

//users route
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/toggle-lock', [UserController::class, 'toggleLockUserAccount']);
// Route::get('/users/change-password', [UserController::class, 'reset']);
Route::put('/update-email', [UserController::class, 'updateEmail'])->middleware('auth:sanctum');
Route::put('/edit-user-information', [UserController::class, 'updateInformation'])->middleware('auth:sanctum');
Route::get('/users/count', [UserController::class, 'getUsersCount']);

//get physicians
Route::get('/physicians', [UserController::class, 'getPhysicians'])->middleware('auth:sanctum')->middleware('auth:sanctum');
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
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationRecordController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhysicianPatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;

// authentication and password-related
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware(['throttle:3,5']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register'])->middleware(['auth:sanctum', 'role:admin']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['auth:sanctum', 'role:admin']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::put('/change-password', [AuthController::class,'changePassword'])->middleware('auth:sanctum');

// users
Route::get('/users', [UserController::class, 'index'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/users/physicians', [UserController::class, 'getPhysicians'])->middleware(['auth:sanctum', 'role:secretary']);
Route::get('/users/count', [UserController::class, 'getUsersCount'])->middleware(['auth:sanctum', 'role:admin']);
Route::put('/users/update-email', [UserController::class, 'updateEmail'])->middleware('auth:sanctum');
Route::put('/users/update-information', [UserController::class, 'updateInformation'])->middleware(['auth:sanctum', 'role:admin']);
Route::put('/users/unrestrict-account', [UserController::class, 'unrestrictAccount'])->middleware(['auth:sanctum', 'role:admin']);
Route::put('/users/toggle-lock', [UserController::class,'toggleLockUser'])->middleware(['auth:sanctum', 'role:admin']);

//departments
Route::apiResource('departments', DepartmentController::class)->except(['show'])->middleware(['auth:sanctum', 'role:admin']);

//assign physician
// Route::post('/assign-patient-physician', [PhysicianPatientController::class, 'assignPatientPhysician'])->middleware('auth:sanctum');

// patients
Route::apiResource('patients', PatientController::class)->except(['destroy'])->middleware(['auth:sanctum', 'role:secretary,physician']);
Route::get('/duplicate-patients', [PatientController::class, 'getDuplicatePatients'])->middleware(['auth:sanctum', 'role:secretary,physician']);
Route::get('/patients-count', [PatientController::class, 'getPatientsCount'])->middleware(['auth:sanctum', 'role:admin']);

// consultation records
Route::apiResource('consultation-records', ConsultationRecordController::class)->except(['index', 'destroy', 'show'])->middleware(['auth:sanctum', 'role:physician']);
Route::get('/consultation-records/{consultation_record}', [ConsultationRecordController::class, 'show'])->middleware(['auth:sanctum', 'role:secretary,physician']);

// payments
Route::apiResource('payments', PaymentController::class)->except(['destroy', 'update', 'store', 'show'])->middleware(['auth:sanctum', 'role:admin,physician,secretary']);
Route::put('/payments/{payment}', [PaymentController::class, 'update'])->middleware(['auth:sanctum', 'role:physician,secretary']);
Route::get('/payments-total', [PaymentController::class, 'getPaymentsTotal'])->middleware(['auth:sanctum', 'role:admin']);  

// queue
Route::get('/queue', [QueueController::class, 'index'])->middleware(['auth:sanctum', 'role:queue manager,secretary,physician']);
Route::post('/queue', [QueueController::class, 'store'])->middleware(['auth:sanctum', 'role:queue manager']);
Route::delete('/queue/{queue}', [QueueController::class, 'destroy'])->middleware(['auth:sanctum', 'role:queue manager']);
Route::put('/queue/{queue}/clear', [QueueController::class, 'clearQueue'])->middleware(['auth:sanctum', 'role:queue manager']);
Route::put('/queue/{queue}/increment-total', [QueueController::class, 'incrementQueueTotal'])->middleware(['auth:sanctum', 'role:queue manager']);;
Route::put('/queue/{queue}/increment-current', [QueueController::class, 'incrementQueueCurrent'])->middleware(['auth:sanctum', 'role:secretary,physician']);
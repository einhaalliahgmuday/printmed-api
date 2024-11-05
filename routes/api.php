<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PatientPhysicianController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;

// auth and password
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware(['throttle:3,60']);

//routes that requires authentication to access
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // auth, registration, and password
    Route::post('/register', [AuthController::class, 'register'])->middleware(['role:admin']);
    Route::get('/register/is-email-exists', [UserController::class, 'isEmailExists'])->middleware(['role:admin']);
    Route::get('/register/is-personnel-number-exists', [UserController::class, 'isPersonnelNumberExists'])->middleware(['role:admin']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['role:admin']);
    Route::put('/change-password', [AuthController::class,'changePassword']);

    // users
    Route::put('/update-email', [UserController::class, 'updateEmail']);
    Route::get('/users', [UserController::class, 'index'])->middleware(['role:admin']);
    Route::get('/users/physicians', [UserController::class, 'getPhysicians'])->middleware(['role:secretary,physician']);
    Route::get('/users-count', [UserController::class, 'getUsersCount'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/update-information', [UserController::class, 'updateInformation'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/unrestrict', [UserController::class, 'unrestrict'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/toggle-lock', [UserController::class,'toggleLock'])->middleware(['role:admin']);

    // audits
    Route::get('/audits', [AuditController::class, 'index'])->middleware(['role:admin']);
    Route::get('/audits/download', [AuditController::class, 'downloadAudits'])->middleware(['role:admin']);

    // departments
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::apiResource('departments', DepartmentController::class)->except(['show', 'index'])->middleware(['role:admin']);

    // patient-physician relationship
    Route::post('/patients/{patient}/assign-physician', [PatientPhysicianController::class, 'store'])->middleware(['role:secretary']);
    Route::put('/patients/{patient}/remove-physician', [PatientPhysicianController::class, 'update'])->middleware(['role:secretary']);

    // patients
    Route::apiResource('patients', PatientController::class)->except(['destroy'])->middleware(['role:secretary,physician']);
    Route::get('/duplicate-patients', [PatientController::class, 'getDuplicates'])->middleware(['role:secretary,physician']);
    Route::get('/patients-count', [PatientController::class, 'getCount'])->middleware(['role:admin']);

    // consultation records
    Route::apiResource('consultations', ConsultationController::class)->only(['store', 'update', 'show'])->middleware(['role:physician']);

    // payments
    Route::get('/payments', [PaymentController::class, 'index'])->middleware(['role:admin,physician,secretary']);
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->middleware(['role:physician,secretary']);
    Route::get('/payments-total', [PaymentController::class, 'getTotal'])->middleware(['role:admin']);

    // queue
    Route::get('/queue', [QueueController::class, 'index'])->middleware(['role:queue manager,secretary,physician']);
    Route::post('/queue', [QueueController::class, 'store'])->middleware(['role:queue manager']);
    Route::put('/queue/{queue}/clear', [QueueController::class, 'clear'])->middleware(['role:queue manager']);
    Route::delete('/queue/{queue}', [QueueController::class, 'destroy'])->middleware(['role:queue manager']);
    Route::put('/queue/{queue}/increment-total', [QueueController::class, 'incrementTotal'])->middleware(['role:queue manager']);
    Route::put('/queue/{queue}/increment-current', [QueueController::class, 'incrementCurrent'])->middleware(['role:secretary,physician']);
});
<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\RegistrationController;
use Barryvdh\Snappy\Facades\SnappyImage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPhysicianController;
use App\Http\Controllers\PatientQrController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VitalSignsController;
use App\Models\Patient;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::post('/{patient}', [PatientQrController::class, 'store']);

Route::post('/update-patient-photo/{patient}', [PatientController::class, 'updatePhoto']);

// patient registration
Route::post('/registrations', [RegistrationController::class, 'store']);

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
    Route::post('/create-user', [AuthController::class, 'createUser'])->middleware(['role:admin']);
    Route::get('/create-user/is-email-exists', [AuthController::class, 'isEmailExists'])->middleware(['role:admin']);
    Route::get('/create-user/is-personnel-number-exists', [AuthController::class, 'isPersonnelNumberExists'])->middleware(['role:admin']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['role:admin']);
    Route::put('/change-password', [AuthController::class,'changePassword']);

    // users
    Route::get('/users', [UserController::class, 'index'])->middleware(['role:admin']);
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware(['role:admin']);
    Route::put('/update-email', [UserController::class, 'updateEmail']);
    Route::put('/update-email/verify-otp', [UserController::class, 'verifyEmailOtp']);
    Route::put('/users/{user_to_update}/toggle-lock', [UserController::class,'toggleLock'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/update-information', [UserController::class, 'updateInformation'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/unrestrict', [UserController::class, 'unrestrict'])->middleware(['role:admin']);
    Route::get('/physicians', [UserController::class, 'getPhysicians'])->middleware(['role:secretary']);
    Route::get('/users-count', [UserController::class, 'getUsersCount'])->middleware(['role:admin']);

    // audits
    Route::get('/audits', [AuditController::class, 'index'])->middleware(['role:admin']);
    Route::get('/audits/download', [AuditController::class, 'downloadAudits'])->middleware(['role:admin']);

    // departments
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::apiResource('departments', DepartmentController::class)->except(['show', 'index'])->middleware(['role:admin']);

    // patient registration
    Route::apiResource('registrations', RegistrationController::class)->only(['index', 'show'])->middleware(['role:secretary']);

    // patients
    Route::apiResource('patients', PatientController::class)->except(['destroy'])->middleware(['role:secretary,physician']);
    Route::post('/get-patient/{uuid}', [PatientController::class, 'getUsingUuid'])->middleware(['role:secretary,physician']);
    Route::get('/get-patient-photo/{patient}', [PatientController::class, 'getPhoto'])->middleware(['role:secretary,physician']);
    Route::get('/duplicate-patients', [PatientController::class, 'getDuplicates'])->middleware(['role:secretary,physician']);
    Route::get('/patients-count', [PatientController::class, 'getCount'])->middleware(['role:admin']);

    // patient qr ids
    Route::post('/generate-patient-id-card', [PatientQrController::class, 'store'])->middleware(['role:secretary']);
    Route::post('/deactivate-patient-id-card', [PatientQrController::class, 'deactivate'])->middleware(['role:secretary']);

    // vital signs
    Route::post('/vital-signs/{patient}', [VitalSignsController::class, 'store'])->middleware('role:secretary');
    Route::apiResource('vital-signs', VitalSignsController::class)->only('update', 'destroy')->middleware('role:secretary');

    // patient-physician relationship
    Route::post('/patients/{patient}/assign-physician', [PatientPhysicianController::class, 'store'])->middleware(['role:secretary']);
    Route::put('/patients/{patient}/remove-physician', [PatientPhysicianController::class, 'destroy'])->middleware(['role:secretary']);

    // consultations
    Route::apiResource('consultations', ConsultationController::class)->only(['store', 'update', 'show'])->middleware(['role:physician']);
    Route::get('/patients/{patient}/consultations', [ConsultationController::class, 'index'])->middleware(['role:physician']);
});
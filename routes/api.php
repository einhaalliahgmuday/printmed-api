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
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::get('/', function () {
    $qr = QrCode::size(300)
                    ->style('round') //square, dot, round
                    ->eye('circle') // square, circle
                    ->format('png')
                    ->merge('/public/images/carmona_hospital_logo_3.png')
                    ->generate("Carmona Hospital and Medical Center");

    return response($qr)->header('Content-Type', 'images/png');

});

// PATIENT REGISTRATION
Route::post('/registrations', [RegistrationController::class, 'store']);

// AUTH AND PASSWORD
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

//routes that requires authentication to access
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);

    // USERS
    // admin controls
    Route::get('/users', [UserController::class, 'index'])->middleware(['role:admin']);
    Route::post('/users', [UserController::class, 'store'])->middleware(['role:admin']);
    Route::get('/is-user-email-exists', [UserController::class, 'isEmailExists'])->middleware(['role:admin']);
    Route::get('/is-personnel-number-exists', [UserController::class, 'isPersonnelNumberExists'])->middleware(['role:admin']);
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware(['role:admin']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/toggle-lock', [UserController::class,'toggleLock'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/update-information', [UserController::class, 'updateInformation'])->middleware(['role:admin']);
    Route::put('/users/{user_to_update}/unrestrict', [UserController::class, 'unrestrict'])->middleware(['role:admin']);
    Route::get('/users-count', [UserController::class, 'getUsersCount'])->middleware(['role:admin']);
    // user controls
    Route::put('/update-email', [UserController::class, 'updateEmail']);
    Route::put('/update-email/verify-otp', [UserController::class, 'verifyEmailOtp']);
    Route::put('/change-password', [UserController::class,'changePassword']);
    Route::get('/physicians', [UserController::class, 'getPhysicians'])->middleware(['role:secretary']);

    // AUDITS
    Route::get('/audits', [AuditController::class, 'index'])->middleware(['role:admin']);
    Route::get('/audits/download', [AuditController::class, 'downloadAudits'])->middleware(['role:admin']);

    // DEPARTMENTS
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::apiResource('departments', DepartmentController::class)->except(['show', 'index'])->middleware(['role:admin']);

    // REGISTRATIONS
    Route::apiResource('registrations', RegistrationController::class)->only(['index', 'show'])->middleware(['role:secretary']);

    // PATIENTS
    Route::apiResource('patients', PatientController::class)->only(['store', 'index', 'show'])->middleware(['role:secretary']);
    Route::POST('/patients/{patient}', [PatientController::class, 'update'])->middleware(['role:secretary']);
    Route::get('/patient-photo/{patient}', [PatientController::class, 'getPhoto'])->middleware(['role:secretary,physician']);
    Route::post('/patient-photo/{patient}', [PatientController::class, 'updatePhoto'])->middleware(['role:secretary,physician']);
    Route::get('/duplicate-patients', [PatientController::class, 'getDuplicates'])->middleware(['role:secretary']);

    // PATIENT VITAL SIGNS
    Route::post('/vital-signs/{patient}', [VitalSignsController::class, 'store'])->middleware('role:secretary');
    Route::apiResource('vital-signs', VitalSignsController::class)->only('update', 'destroy')->middleware('role:secretary');

    // CONSULTATIONS
    Route::apiResource('consultations', ConsultationController::class)->only(['store', 'show'])->middleware(['role:physician']);
    Route::get('/patients/{patient}/consultations', [ConsultationController::class, 'index'])->middleware(['role:physician']);

    // PATIENT QR IDs
    Route::post('/generate-patient-id-card/{patient}', [PatientQrController::class, 'store'])->middleware(['role:secretary']);
    Route::post('/deactivate-patient-id-card/{patient}', [PatientQrController::class, 'deactivate'])->middleware(['role:secretary']);
    Route::post('/patient-using-qr', [PatientQrController::class, 'getPatient'])->middleware(['role:secretary,physician']);

    // PATIENT-PHYSICIAN RELATIONSHIP
    Route::post('/patients/{patient}/assign-physician', [PatientPhysicianController::class, 'store'])->middleware(['role:secretary']);
    Route::put('/patients/{patient}/remove-physician', [PatientPhysicianController::class, 'destroy'])->middleware(['role:secretary']);
});
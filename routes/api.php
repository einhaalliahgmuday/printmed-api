<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacialRecognitionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPhysicianController;
use App\Http\Controllers\PatientQrController;
use App\Http\Controllers\RekognitionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VitalSignsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

Route::post('/create-collection', [RekognitionController::class, 'createPatientsCollection']);
Route::post('/compare-faces', [FacialRecognitionController::class, 'compareTwoFaces']);
Route::post('/delete-collection', [RekognitionController::class, 'deletePatientsCollection']);
Route::post('/list-faces', [RekognitionController::class, 'listFacesFromPatientsCollection']);
Route::post('/search-face', [RekognitionController::class, 'searchFacesByImage']);
Route::post('/compare-patient-face', [PatientController::class, 'verifyPatientUsingFace']);

// PATIENT REGISTRATION
Route::post('/registrations', [RegistrationController::class, 'store']);

// AUTH AND PASSWORD
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

//routes that requires authentication to access
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        if ($user->role == "physician" && $user->signature != null && $user->signature != "") {
            $user['signature'] = Storage::temporaryUrl($user->signature, now()->addHours(16));
        }

        return $user;
    });

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);

    // USERS
    // admin controls
    Route::get('/users', [UserController::class, 'index'])->middleware(['role:super admin,admin']);
    Route::post('/users', [UserController::class, 'store'])->middleware(['role:super admin,admin']);
    Route::get('/is-user-email-exists', [UserController::class, 'isEmailExists'])->middleware(['role:super admin,admin']);
    Route::get('/is-personnel-number-exists', [UserController::class, 'isPersonnelNumberExists'])->middleware(['role:super admin,admin']);
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware(['role:super admin,admin']);
    Route::put('/users/{user_to_update}/toggle-lock', [UserController::class,'toggleLock'])->middleware(['role:super admin,admin']);
    Route::put('/users/{user_to_update}/update-information', [UserController::class, 'updateInformation'])->middleware(['role:super admin,admin']);
    Route::put('/users/{user_to_update}/unrestrict', [UserController::class, 'unrestrict'])->middleware(['role:super admin,admin']);
    Route::get('/users-count', [UserController::class, 'count'])->middleware(['role:super admin,admin']);
    // user controls
    Route::post('/upload-signature', [UserController::class, 'uploadSignature']);
    Route::delete('/delete-signature', [UserController::class, 'deleteSignature']);
    Route::put('/update-email', [UserController::class, 'updateEmail']);
    Route::post('/resend-update-email-otp', [UserController::class, 'resendUpdateEmailOtp']);
    Route::post('/update-email/verify-otp', [UserController::class, 'verifyUpdateEmailOtp']);
    Route::put('/change-password', [UserController::class,'changePassword']);
    Route::get('/physicians', [UserController::class, 'getPhysicians'])->middleware(['role:secretary']);

    // AUDITS
    Route::get('/audits', [AuditController::class, 'index'])->middleware(['role:super admin,admin']);
    Route::get('/audits/download', [AuditController::class, 'downloadAudits'])->middleware(['role:super admin']);

    // DEPARTMENTS
    Route::get('/departments', [DepartmentController::class, 'index'])->middleware(['role:super admin,admin']);
    Route::apiResource('departments', DepartmentController::class)->except(['show', 'index'])->middleware(['role:super admin']);

    // REGISTRATIONS
    Route::apiResource('registrations', RegistrationController::class)->only(['index', 'show'])->middleware(['role:secretary']);

    // PATIENTS
    Route::apiResource('patients', PatientController::class)->only(['store', 'index', 'show'])->middleware(['role:secretary']);
    Route::post('/patients/{patient}', [PatientController::class, 'update'])->middleware(['role:secretary']);
    Route::get('/patient-photo/{patient}', [PatientController::class, 'getPhoto'])->middleware(['role:secretary,physician']);
    Route::post('/patient-photo/{patient}', [PatientController::class, 'updatePhoto'])->middleware(['role:secretary,physician']);
    Route::post('/duplicate-patient', [PatientController::class, 'getDuplicates'])->middleware(['role:secretary']);
    Route::post('/patient-using-id', [PatientController::class, 'getUsingId'])->middleware(['role:physician']);
    Route::post('/patient-using-qr', [PatientController::class, 'getUsingQr'])->middleware(['role:secretary,physician']);
    Route::post('/patient-using-face', [PatientController::class, 'getUsingFace'])->middleware(['role:secretary,physician']);
    Route::post('/verify-patient-face', [PatientController::class, 'verifyPatientUsingFace'])->middleware(['role:secretary,physician']);

    // PATIENT VITAL SIGNS
    Route::post('/vital-signs/{patient}', [VitalSignsController::class, 'store'])->middleware('role:secretary');
    Route::apiResource('vital-signs', VitalSignsController::class)->only('update', 'destroy')->middleware('role:secretary');

    // CONSULTATIONS
    Route::apiResource('consultations', ConsultationController::class)->only(['store', 'show'])->middleware(['role:physician']);
    Route::get('/consultations/{consultation}/print-prescription', [ConsultationController::class, 'printPrescription'])->middleware(['role:physician']);
    Route::get('/patients/{patient}/consultations', [ConsultationController::class, 'index'])->middleware(['role:physician']);

    // PATIENT QR
    Route::post('/generate-patient-id-card/{patient}', [PatientQrController::class, 'store'])->middleware(['role:secretary']);
    Route::post('/deactivate-patient-id-card/{patient}', [PatientQrController::class, 'deactivate'])->middleware(['role:secretary']);

    // PATIENT-PHYSICIAN RELATIONSHIP
    Route::post('/patients/{patient}/assign-physician', [PatientPhysicianController::class, 'store'])->middleware(['role:secretary']);
    Route::put('/patients/{patient}/remove-physician', [PatientPhysicianController::class, 'destroy'])->middleware(['role:secretary']);
});
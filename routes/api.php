<?php

use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{patientId}', [PatientController::class, 'show']);

    Route::post('/patients/verify-identity', [PatientController::class, 'verifyIdentity']);

    Route::get('/patients/{patientId}/health-histories', [PatientController::class, 'healthHistories']);
    Route::post('/patients/{patientId}/health-histories', [PatientController::class, 'addHealthHistory']);
    Route::put('/patients/{patientId}/health-histories/{historyId}', [PatientController::class, 'updateHealthHistory']);
    Route::delete('/patients/{patientId}/health-histories/{historyId}', [PatientController::class, 'deleteHealthHistory']);

    Route::get('/patients/{patientId}/vaccination-histories', [PatientController::class, 'vaccinationHistories']);
    Route::post('/patients/{patientId}/vaccination-histories', [PatientController::class, 'addVaccinationHistory']);
    Route::put('/patients/{patientId}/vaccination-histories/{historyId}', [PatientController::class, 'updateVaccinationHistory']);
    Route::delete('/patients/{patientId}/vaccination-histories/{historyId}', [PatientController::class, 'deleteVaccinationHistory']);
});

<?php

use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Module 3: Queue & Appointment
| All routes are prefixed with /api/v1/
|
*/

Route::prefix('v1')->group(function () {

    // -------------------------------------------------------
    // Schedules
    // -------------------------------------------------------
    Route::get('schedules', [ScheduleController::class, 'index']);
    Route::get('schedules/{id}', [ScheduleController::class, 'show']);
    Route::get('schedules/{id}/quota', [ScheduleController::class, 'quota']);

    // -------------------------------------------------------
    // Bookings
    // -------------------------------------------------------
    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/{id}', [BookingController::class, 'show']);
    Route::patch('bookings/{id}/check-in', [BookingController::class, 'checkIn']);
    Route::patch('bookings/{id}/complete', [BookingController::class, 'complete']);
    Route::patch('bookings/{id}/cancel', [BookingController::class, 'cancel']);
});

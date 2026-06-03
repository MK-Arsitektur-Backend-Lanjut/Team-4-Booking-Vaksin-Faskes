<?php

use App\Http\Controllers\HealthCenterController;
use App\Http\Controllers\VaccineController;
use App\Http\Controllers\VaccineScheduleController;
use App\Http\Controllers\VaccineStockController;
use Illuminate\Support\Facades\Route;

// Health Centers
Route::prefix('health-centers')->group(function () {
    Route::get('/', [HealthCenterController::class, 'index'])->name('health-centers.index');
    Route::post('/', [HealthCenterController::class, 'store'])->name('health-centers.store');
    Route::get('/active', [HealthCenterController::class, 'getActive'])->name('health-centers.active');
    Route::get('/search', [HealthCenterController::class, 'search'])->name('health-centers.search');
    Route::get('/by-province/{province}', [HealthCenterController::class, 'getByProvince'])->name('health-centers.by-province');
    Route::get('/by-city/{city}', [HealthCenterController::class, 'getByCity'])->name('health-centers.by-city');
    Route::get('/{id}', [HealthCenterController::class, 'show'])->name('health-centers.show');
    Route::put('/{id}', [HealthCenterController::class, 'update'])->name('health-centers.update');
    Route::delete('/{id}', [HealthCenterController::class, 'destroy'])->name('health-centers.destroy');
});

// Vaccines
Route::prefix('vaccines')->group(function () {
    Route::get('/', [VaccineController::class, 'index'])->name('vaccines.index');
    Route::post('/', [VaccineController::class, 'store'])->name('vaccines.store');
    Route::get('/active', [VaccineController::class, 'getActive'])->name('vaccines.active');
    Route::get('/search', [VaccineController::class, 'search'])->name('vaccines.search');
    Route::get('/{id}', [VaccineController::class, 'show'])->name('vaccines.show');
    Route::put('/{id}', [VaccineController::class, 'update'])->name('vaccines.update');
    Route::delete('/{id}', [VaccineController::class, 'destroy'])->name('vaccines.destroy');
});

// Vaccine Stocks
Route::prefix('vaccine-stocks')->group(function () {
    Route::get('/', [VaccineStockController::class, 'index'])->name('vaccine-stocks.index');
    Route::post('/', [VaccineStockController::class, 'store'])->name('vaccine-stocks.store');
    Route::get('/available', [VaccineStockController::class, 'getAvailable'])->name('vaccine-stocks.available');
    Route::get('/health-center/{healthCenterId}', [VaccineStockController::class, 'getByHealthCenter'])->name('vaccine-stocks.by-health-center');
    Route::get('/{id}', [VaccineStockController::class, 'show'])->name('vaccine-stocks.show');
    Route::put('/{id}', [VaccineStockController::class, 'update'])->name('vaccine-stocks.update');
    Route::delete('/{id}', [VaccineStockController::class, 'destroy'])->name('vaccine-stocks.destroy');
});

// Vaccine Schedules
Route::prefix('vaccine-schedules')->group(function () {
    Route::get('/', [VaccineScheduleController::class, 'index'])->name('vaccine-schedules.index');
    Route::post('/', [VaccineScheduleController::class, 'store'])->name('vaccine-schedules.store');
    Route::get('/available', [VaccineScheduleController::class, 'getAvailable'])->name('vaccine-schedules.available');
    Route::get('/by-date', [VaccineScheduleController::class, 'getByDate'])->name('vaccine-schedules.by-date');
    Route::get('/by-date-range', [VaccineScheduleController::class, 'getByDateRange'])->name('vaccine-schedules.by-date-range');
    Route::get('/health-center/{healthCenterId}', [VaccineScheduleController::class, 'getByHealthCenter'])->name('vaccine-schedules.by-health-center');
    Route::get('/{id}', [VaccineScheduleController::class, 'show'])->name('vaccine-schedules.show');
    Route::put('/{id}', [VaccineScheduleController::class, 'update'])->name('vaccine-schedules.update');
    Route::delete('/{id}', [VaccineScheduleController::class, 'destroy'])->name('vaccine-schedules.destroy');
});

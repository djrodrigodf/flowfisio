<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('bi')->group(function () {
    Route::get('/summary', [\App\Http\Controllers\Api\BiController::class, 'summary']);
    Route::get('/series/revenue', [\App\Http\Controllers\Api\BiController::class, 'seriesRevenue']);
    Route::get('/series/attendance', [\App\Http\Controllers\Api\BiController::class, 'seriesAttendance']);
    Route::get('/top/treatments', [\App\Http\Controllers\Api\BiController::class, 'topTreatments']);
    Route::get('/top/professionals', [\App\Http\Controllers\Api\BiController::class, 'topProfessionals']);
    Route::get('/top/insurances', [\App\Http\Controllers\Api\BiController::class, 'topInsurances']);
    Route::get('/outstanding', [\App\Http\Controllers\Api\BiController::class, 'outstanding']);
});

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('/schedule/daily', [\App\Http\Controllers\Api\ReportsController::class, 'dailySchedule']);
    Route::get('/schedule/weekly', [\App\Http\Controllers\Api\ReportsController::class, 'weeklySchedule']);
    Route::get('/production/pro', [\App\Http\Controllers\Api\ReportsController::class, 'productionByProfessional']);
    Route::get('/monthly-comparison', [\App\Http\Controllers\Api\ReportsController::class, 'monthlyComparison']);
    Route::get('/dataset/appointments', [\App\Http\Controllers\Api\ReportsController::class, 'datasetAppointments']);
    Route::get('/dataset/payments', [\App\Http\Controllers\Api\ReportsController::class, 'datasetPayments']);
});

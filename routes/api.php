<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MobileReportController;
use App\Http\Controllers\Api\MobileMaintenanceController;

/*
|--------------------------------------------------------------------------
| MOBILE REPORTING API
|--------------------------------------------------------------------------
*/

Route::get(

    '/rooms',

    [MobileReportController::class, 'rooms']

);

Route::get(

    '/equipment/{roomId}',

    [MobileReportController::class, 'equipment']

);

Route::get(

    '/suggested-issues/{equipmentId}',

    [MobileReportController::class, 'suggestedIssues']

);

Route::get(
    '/global-suggested-issues',
    [MobileReportController::class, 'globalSuggestedIssues']
);

Route::get(

    '/reporter/{employeeId}',

    [MobileReportController::class, 'reporter']

);

Route::post(

    '/submit-report',

    [MobileReportController::class, 'submitReport']

);

Route::prefix('maintenance')->group(function () {

    // ==========================================
    // MICROSOFT LOGIN
    // ==========================================
    Route::post('/login', [MobileMaintenanceController::class, 'login']);

    // ==========================================
    // GET EQUIPMENT BY QR
    // ==========================================
    Route::get('/equipment/{qr}', [MobileMaintenanceController::class, 'equipment']);

    // ==========================================
    // UPDATE EQUIPMENT
    // ==========================================
    Route::put('/equipment/{id}', [MobileMaintenanceController::class, 'updateEquipment']);

    // ==========================================
    // MAINTENANCE HISTORY
    // ==========================================
    Route::get('/history/{equipmentId}', [MobileMaintenanceController::class, 'history']);

    Route::post('/history', [MobileMaintenanceController::class, 'storeHistory']);

    // ==========================================
    // MAINTENANCE SCHEDULE
    // ==========================================
    Route::get(
        '/schedule/{equipmentId}',
        [MobileMaintenanceController::class, 'schedule']
    );

    // UPDATE A SPECIFIC SCHEDULE
    Route::put(
        '/schedule/{scheduleId}',
        [MobileMaintenanceController::class, 'updateSchedule']
    );

});


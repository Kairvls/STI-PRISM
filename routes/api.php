<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MobileReportController;
use App\Http\Controllers\Api\MobileMaintenanceController;
use App\Http\Controllers\Api\MicrosoftAuthController;

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

    Route::post(
        '/login',
        [MicrosoftAuthController::class, 'login']
    );

    Route::middleware('auth:sanctum')->group(function () {

        // List / dashboard endpoints (register before parameterized routes)
        Route::get(
            '/equipments',
            [MobileMaintenanceController::class, 'listEquipment']
        );

        Route::get(
            '/histories',
            [MobileMaintenanceController::class, 'listHistory']
        );

        Route::get(
            '/schedules',
            [MobileMaintenanceController::class, 'listSchedules']
        );

        Route::get(
            '/recent',
            [MobileMaintenanceController::class, 'recent']
        );

        Route::get(
            '/equipment/{qr}',
            [MobileMaintenanceController::class, 'equipment']
        );

        Route::put(
            '/equipment/{id}',
            [MobileMaintenanceController::class, 'updateEquipment']
        );

        Route::get(
            '/history/{equipmentId}',
            [MobileMaintenanceController::class, 'history']
        );

        Route::post(
            '/history',
            [MobileMaintenanceController::class, 'storeHistory']
        );

        Route::get(
            '/schedule/{equipmentId}',
            [MobileMaintenanceController::class, 'schedule']
        );

        Route::post(
            '/schedule',
            [MobileMaintenanceController::class, 'storeSchedule']
        );

        Route::put(
            '/schedule/{scheduleId}',
            [MobileMaintenanceController::class, 'updateSchedule']
        );

    });

});


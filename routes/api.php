<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MobileReportController;
use App\Http\Controllers\Api\MobileMaintenanceController;
use App\Http\Controllers\Api\MobileSemesterInspectionController;
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
            '/reports',
            [MobileMaintenanceController::class, 'listReports']
        );

        Route::get(
            '/reports/{id}',
            [MobileMaintenanceController::class, 'showReport']
        );

        Route::post(
            '/reports/{id}/status',
            [MobileMaintenanceController::class, 'updateReportStatus']
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

        Route::get(
            '/semester-inspections',
            [MobileSemesterInspectionController::class, 'index']
        );

        Route::get(
            '/semester-inspections/{id}',
            [MobileSemesterInspectionController::class, 'show']
        )->whereNumber('id');

        Route::get(
            '/semester-inspections/{id}/by-qr',
            [MobileSemesterInspectionController::class, 'resolveByQr']
        )->whereNumber('id');

        Route::post(
            '/semester-inspections/{id}/inspect/{itemId}',
            [MobileSemesterInspectionController::class, 'inspect']
        )->whereNumber('id')->whereNumber('itemId');

    });

});


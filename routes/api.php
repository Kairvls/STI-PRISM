<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MobileReportController;
use App\Http\Controllers\Api\MobileMaintenanceController;
use App\Http\Controllers\Api\MobilePurchaserController;
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

    });

});

Route::prefix('purchaser')->middleware(['auth:sanctum', 'purchaser.api'])->group(function () {

    Route::get('/summary', [MobilePurchaserController::class, 'summary']);

    Route::get('/reports', [MobilePurchaserController::class, 'listReports']);

    Route::get('/reports/{id}', [MobilePurchaserController::class, 'showReport']);

    Route::post('/reports/{id}/accept', [MobilePurchaserController::class, 'acceptReport']);

    Route::post('/reports/{id}/resolve', [MobilePurchaserController::class, 'resolveReport']);

    Route::post('/reports/{id}/replacement', [MobilePurchaserController::class, 'replaceReport']);

    Route::post('/reports/{id}/reject', [MobilePurchaserController::class, 'rejectReport']);

    Route::post('/reports/{id}/archive', [MobilePurchaserController::class, 'archiveReport']);

    Route::post('/reports/{id}/restore', [MobilePurchaserController::class, 'restoreReport']);

});


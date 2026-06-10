<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MobileReportController;

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

    '/reporter/{employeeId}',

    [MobileReportController::class, 'reporter']

);

Route::post(

    '/submit-report',

    [MobileReportController::class, 'submitReport']

);


<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\MaintenanceController;

/*
|--------------------------------------------------------------------------
| LANDING PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return view('landing.index');

});

/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

/*Route::get('/login', function () {

    return view('auth.login');

})->name('login');*/

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {

    if (Auth::user()->user_role_id == 1) {

        return redirect('/admin/dashboard');

    }

    elseif (Auth::user()->user_role_id == 2) {

        return redirect('/maintenance/dashboard');

    }

    elseif (Auth::user()->user_role_id == 3) {

        return redirect('/purchaser/dashboard');

    }

    elseif (Auth::user()->user_role_id == 4) {

        return redirect('/president/dashboard');

    }

    elseif (Auth::user()->user_role_id == 5) {

        return redirect('/accounting/dashboard');

    }

    elseif (Auth::user()->user_role_id == 6) {

        return redirect('/receiving/dashboard');

    }

    abort(403);

})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/admin/dashboard', function () {

        return view('admin.dashboard');

    });

    Route::get('/admin/users', function () {

        return view('admin.users.index');

    });

    Route::get('/admin/users/create', function () {

        return view('admin.users.create');

    });

    Route::post('/admin/users/store',
        [AdminController::class, 'storeUser']);

});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout',
    [AuthenticatedSessionController::class, 'destroy']
)->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MAINTENANCE PERSONNEL
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/maintenance/dashboard', function () {

        return view('maintenance-personnel.dashboard');

    });

});

// MICROSOFT LOGIN

Route::get(
    '/auth/microsoft',
    [MicrosoftController::class, 'redirectToMicrosoft']
)->name('auth.microsoft.redirect');

Route::get(
    '/auth/microsoft/callback',
    [MicrosoftController::class, 'handleMicrosoftCallback']
)->name('auth.microsoft.callback');



// MAINTENANCE PERSONNEL

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/maintenance/dashboard',
        [MaintenanceController::class, 'dashboard']
    );

    Route::get(
        '/maintenance/reports/incoming',
        [MaintenanceController::class, 'incomingReports']
    );

    Route::get(
        '/maintenance/reports/urgent',
        [MaintenanceController::class, 'urgentReports']
    );

    Route::get(
        '/maintenance/reports/pending',
        [MaintenanceController::class, 'pendingReports']
    );

    Route::get(
        '/maintenance/reports/processing',
        [MaintenanceController::class, 'processingReports']
    );

    Route::get(
        '/maintenance/reports/resolved',
        [MaintenanceController::class, 'resolvedReports']
    );

    Route::get(
        '/maintenance/reports/replacement',
        [MaintenanceController::class, 'replacementReports']
    );

    Route::get(
        '/maintenance/reports/rejected',
        [MaintenanceController::class, 'rejectedReports']
    );
});



/*
|--------------------------------------------------------------------------
| MAINTENANCE REPORT MANAGEMENT
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | REPORT DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/maintenance/reports/incoming',
        [MaintenanceController::class, 'incomingReports']
    );

    Route::get(
        '/maintenance/reports/urgent',
        [MaintenanceController::class, 'urgentReports']
    );

    Route::get(
        '/maintenance/reports/pending',
        [MaintenanceController::class, 'pendingReports']
    );

    Route::get(
        '/maintenance/reports/processing',
        [MaintenanceController::class, 'processingReports']
    );

    Route::get(
        '/maintenance/reports/resolved',
        [MaintenanceController::class, 'resolvedReports']
    );

    Route::get(
        '/maintenance/reports/replacement',
        [MaintenanceController::class, 'replacementReports']
    );

    Route::get(
        '/maintenance/reports/rejected',
        [MaintenanceController::class, 'rejectedReports']
    );

    /*
    |--------------------------------------------------------------------------
    | REPORT OPERATIONS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/maintenance/reports/details/{id}',
        [MaintenanceController::class, 'reportDetails']
    );

    Route::get(
        '/maintenance/reports/assign/{id}',
        [MaintenanceController::class, 'assignReportPage']
    );

    Route::post(
        '/maintenance/reports/assign/{id}',
        [MaintenanceController::class, 'assignReport'
    ]);

    Route::get(
        '/maintenance/reports/findings/{id}',
        [MaintenanceController::class, 'addFindings']
    );

    Route::get(
        '/maintenance/reports/update-status/{id}',
        [MaintenanceController::class, 'updateStatusPage']
    );

    Route::post(
        '/maintenance/reports/update-status/{id}',
        [MaintenanceController::class, 'updateStatus']
    );

});

//Report Details
Route::get(
    '/maintenance/reports/details/{id}',
    [MaintenanceController::class, 'reportDetails']
);


/*
|--------------------------------------------------------------------------
| REPORTER ROUTES
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\ReporterController;

Route::get(
    '/',
    [ReporterController::class, 'index']
);

Route::get(
    '/submit-report',
    [ReporterController::class, 'submitReport']
);

Route::post(
    '/store-report',
    [ReporterController::class, 'storeReport']
);

Route::get(
    '/get-equipment/{roomId}',
    [ReporterController::class, 'getEquipmentByRoom']
);


/*
|--------------------------------------------------------------------------
| REPORTER VALIDATION
|--------------------------------------------------------------------------
*/

Route::get(

    '/get-reporter/{employeeId}',

    [ReporterController::class, 'getReporter']

);



/*
|--------------------------------------------------------------------------
| REPORTER VALIDATION
|--------------------------------------------------------------------------
*/

//AUTO SUGGESTION
Route::get(
    '/get-suggestions/{equipmentId}',
    [ReporterController::class, 'getSuggestions']
);


Route::get(
    '/maintenance/reports',
    [MaintenanceController::class, 'allReports']
);


Route::post(
    '/maintenance/reports/archive/{id}',
    [MaintenanceController::class, 'archiveReport']
);

Route::post(
    '/maintenance/reports/restore/{id}',
    [MaintenanceController::class, 'restoreReport']
);



/*
|--------------------------------------------------------------------------
| EQUIPMENT MODULE
|--------------------------------------------------------------------------
*/

// Equipment Inventory
Route::get(
    '/maintenance/equipment/inventory',
    [MaintenanceController::class, 'equipmentInventory']
);

Route::get(
    '/maintenance/equipment/view/{id}',
    [MaintenanceController::class, 'viewEquipment']
);

Route::get(
    '/maintenance/equipment/create',
    [MaintenanceController::class, 'createEquipment']
);

Route::post(
    '/maintenance/equipment/store',
    [MaintenanceController::class, 'storeEquipment']
);

Route::post(
    '/maintenance/equipment/update/{id}',
    [MaintenanceController::class, 'updateEquipment']
);

Route::get(
    '/maintenance/equipment/transfer',
    [MaintenanceController::class, 'equipmentTransferHistory']
);

Route::post(
    '/maintenance/equipment/transfer',
    [MaintenanceController::class, 'transferEquipment']
);

Route::get(
    '/maintenance/equipment/history/{id}',
    [MaintenanceController::class, 'getEquipmentHistory']
);

Route::post(
    '/maintenance/equipment/history/store',
    [MaintenanceController::class, 'storeMaintenanceHistory']
);

Route::get(
    '/maintenance/equipment/transfers/{id}',
    [MaintenanceController::class, 'getTransferHistory']
);

Route::get(
    '/maintenance/equipment/qr-tools',
    [MaintenanceController::class, 'qrTools']
);

Route::post(
    '/maintenance/equipment/qr/generate/{id}',
    [MaintenanceController::class, 'generateQr']
);

Route::get(
    '/maintenance/equipment/qr-image/{code}',
    [MaintenanceController::class, 'qrImage']
);

Route::get(
    '/equipment/{qrCode}',
    [MaintenanceController::class, 'equipmentByQr']
);


/*
|--------------------------------------------------------------------------
| BORROWING MODULE
|--------------------------------------------------------------------------
*/
Route::get(
    '/maintenance/borrowing',
    [MaintenanceController::class, 'borrowing']
);

Route::post(
    '/maintenance/borrowing/store',
    [MaintenanceController::class, 'storeBorrowing']
);

Route::post(
    '/maintenance/borrowing/return',
    [MaintenanceController::class, 'returnEquipment']
);


/*
|--------------------------------------------------------------------------
| CREATE SCHEDULE
|--------------------------------------------------------------------------
*/

Route::get(
    '/maintenance/schedules',
    [MaintenanceController::class, 'schedules']
);

Route::post(
    '/maintenance/schedules/store',
    [MaintenanceController::class, 'storeSchedule']
);

/*
|--------------------------------------------------------------------------
| COMPLETE SCHEDULE
|--------------------------------------------------------------------------
*/

Route::post(
    '/maintenance/schedules/complete/',
    [MaintenanceController::class, 'completeSchedule']
);

/*
|--------------------------------------------------------------------------
| RESCHEDULE
|--------------------------------------------------------------------------
*/

Route::post(
    '/maintenance/schedules/reschedule',
    [MaintenanceController::class, 'rescheduleSchedule']
);

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

Route::delete(
    '/maintenance/schedules/delete',
    [MaintenanceController::class, 'deleteSchedule']
);


/*
|--------------------------------------------------------------------------
| DISPOSAL MODULE
|--------------------------------------------------------------------------
*/

Route::get(
    '/maintenance/disposal',
    [MaintenanceController::class, 'disposal']
);

Route::post(
    '/maintenance/disposal/store',
    [MaintenanceController::class, 'storeDisposal']
);

Route::delete(
    '/maintenance/disposal/delete',
    [MaintenanceController::class, 'deleteDisposal']
);

require __DIR__.'/auth.php';
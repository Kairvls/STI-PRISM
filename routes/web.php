<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\InfrastructureController;
use App\Http\Controllers\QRController;
use App\Http\Controllers\PurchaserController;
use App\Http\Controllers\AuthorityToPurchaseController;
use App\Http\Controllers\ReplacementRequestController;
use App\Http\Controllers\PresidentController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ReceivingController;
use App\Http\Controllers\MessageController;

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

    // Only authenticated users can reach this due to middleware('auth').
    $roleId = auth()->user()?->user_role_id;

    return match ((int) $roleId) {
        1 => redirect('/admin/dashboard'),
        2 => redirect('/maintenance/dashboard'),
        3 => redirect('/purchaser/dashboard'),
        4 => redirect('/president/dashboard'),
        5 => redirect('/accounting/dashboard'),
        6 => redirect('/receiving/dashboard'),
        default => abort(403),
    };

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





// =====================================================
// ADMIN ROUTES
// =====================================================

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ==========================================
        // DASHBOARD
        // ==========================================

        Route::get(
            '/dashboard',
            [AdminController::class, 'dashboard']
        )->name('dashboard');

        // ==========================================
        // PROCUREMENT REVIEW
        // ==========================================

        Route::get(
            '/procurement-review',
            [AdminController::class, 'procurementReview']
        )->name('procurement-review');

        // ==========================================
        // DIGITAL SIGNATURES
        // ==========================================

        Route::get(
            '/digital-signatures/sign-ris',
            [AdminController::class, 'signRis']
        )->name('digital-signatures.sign-ris');

        Route::get(
            '/digital-signatures/history',
            [AdminController::class, 'signatureHistory']
        )->name('digital-signatures.history');

        Route::post(
            '/digital-signatures/ris/decide',
            [AdminController::class, 'decideRis']
        )->name('digital-signatures.ris.decide');

        Route::post(
            '/digital-signatures/ris/{ris}/return-purchaser',
            [AdminController::class, 'returnRisToPurchaser']
        )->name('digital-signatures.ris.return-purchaser');

        Route::post(
            '/digital-signatures/ris/{ris}/return-revision',
            [AdminController::class, 'returnRisForRevision']
        )->name('digital-signatures.ris.return-revision');

        // ==========================================
        // NOTIFICATIONS
        // ==========================================

        Route::get(
            '/notifications',
            [AdminController::class, 'notifications']
        )->name('notifications');

        Route::get(
            '/notifications/create',
            [AdminController::class, 'createNotification']
        )->name('notifications.create');

        Route::get(
            '/notifications/view',
            [AdminController::class, 'viewNotification']
        )->name('notifications.view');

        Route::get(
            '/notifications/sent-history',
            [AdminController::class, 'sentNotificationHistory']
        )->name('notifications.sent-history');

        // ==========================================
        // USERS
        // ==========================================

        Route::get(
            '/users',
            [AdminController::class, 'users']
        )->name('users');

        Route::get(
            '/users/create',
            [AdminController::class, 'createUser']
        )->name('users.create');

        Route::post(
            '/users/store',
            [AdminController::class, 'storeUser']
        )->name('users.store');

        Route::get(
            '/users/edit',
            [AdminController::class, 'editUser']
        )->name('users.edit');

        Route::get(
            '/users/view',
            [AdminController::class, 'viewUser']
        )->name('users.view');

        Route::get(
            '/users/reset-password',
            [AdminController::class, 'resetPassword']
        )->name('users.reset-password');

        Route::get(
            '/users/activity-logs',
            [AdminController::class, 'userActivityLogs']
        )->name('users.activity-logs');

        // ==========================================
        // REPORTS
        // ==========================================

        Route::get(
            '/reports',
            [AdminController::class, 'systemReports']
        )->name('reports.index');

        Route::get(
            '/reports/approval-logs',
            [AdminController::class, 'approvalLogs']
        )->name('reports.approval-logs');

        Route::get(
            '/reports/audit-logs',
            [AdminController::class, 'auditLogs']
        )->name('reports.audit-logs');

        Route::get(
            '/reports/maintenance-history',
            [AdminController::class, 'maintenanceHistory']
        )->name('reports.maintenance-history');

        Route::get(
            '/reports/receiving',
            [AdminController::class, 'receivingSummary']
        )->name('reports.receiving');

        Route::get(
            '/reports/procurement-history',
            [AdminController::class, 'procurementHistory']
        )->name('reports.procurement-history');

        Route::get(
            '/reports/user-login-logs',
            [AdminController::class, 'userLoginLogs']
        )->name('reports.user-login-logs');

        // ==========================================
        // SETTINGS
        // ==========================================

        Route::get(
            '/settings/campus-setup-pin',
            [AdminController::class, 'campusSetupPin']
        )->name('settings.campus-setup-pin');

        Route::post(
            '/settings/campus-setup-pin',
            [AdminController::class, 'updateCampusSetupPin']
        )->name('settings.campus-setup-pin.update');

        Route::get(
            '/settings/maintenance-settings',
            [AdminController::class, 'maintenanceSettings']
        )->name('settings.maintenance-settings');

        Route::get(
            '/settings/notification-settings',
            [AdminController::class, 'notificationSettings']
        )->name('settings.notification-settings');

        Route::get(
            '/settings/system-settings',
            [AdminController::class, 'systemSettings']
        )->name('settings.system-settings');

        // ==========================================
        // PROCUREMENT REVIEW RIS APPROVALS
        // ==========================================

        Route::get(
            '/procurement-review/ris',
            [AdminController::class, 'risApprovals']
        )->name('procurement-review.ris');

        Route::post(
            '/procurement-review/ris/{ris}/approve',
            [AdminController::class, 'approveRis']
        )->name('procurement-review.ris.approve');

        Route::get(
            '/procurement-review/ris/{ris}/direct-approve-form',
            [AdminController::class, 'directApproveForm']
        )->name('procurement-review.ris.direct-approve-form');

        Route::post(
            '/procurement-review/ris/{ris}/direct-approve',
            [AdminController::class, 'directApproveRis']
        )->name('procurement-review.ris.direct-approve');

        Route::post(
            '/procurement-review/ris/{ris}/reject',
            [AdminController::class, 'rejectRis']
        )->name('procurement-review.ris.reject');

Route::get(
            '/procurement-review/ris/{ris}/print',
            [AdminController::class, 'printRis']
        )->name('procurement-review.ris.print');

        Route::get(
            '/procurement-review/export-pdf',
            [AdminController::class, 'exportProcurementRisPdf']
        )->name('procurement-review.export-pdf');

        Route::get(
            '/digital-signatures/sign-ris/export-pdf',
            [AdminController::class, 'exportSignRisPdf']
        )->name('digital-signatures.sign-ris.export-pdf');

        Route::get(
            '/digital-signatures/history/export-pdf',
            [AdminController::class, 'exportSignatureHistoryPdf']
        )->name('digital-signatures.history.export-pdf');

        // ==========================================
        // QUICK ACCESS MODAL CONTENT (AJAX)
        // ==========================================

        Route::get(
            '/quick-access/procurement-content',
            [AdminController::class, 'quickAccessProcurementContent']
        )->name('quick-access.procurement-content');

        Route::get(
            '/quick-access/signris-content',
            [AdminController::class, 'quickAccessSignRisContent']
        )->name('quick-access.signris-content');

        Route::get(
            '/quick-access/history-content',
            [AdminController::class, 'quickAccessHistoryContent']
        )->name('quick-access.history-content');

        Route::get(
            '/quick-access/users-content',
            [AdminController::class, 'quickAccessUsersContent']
        )->name('quick-access.users-content');

        Route::get(
            '/quick-access/reports-content',
            [AdminController::class, 'quickAccessReportsContent']
        )->name('quick-access.reports-content');

        Route::get(
            '/quick-access/settings-content',
            [AdminController::class, 'quickAccessSettingsContent']
        )->name('quick-access.settings-content');

    });

    Route::get('/admin/users', [AdminController::class, 'users']);

    Route::get('/admin/users/create', [AdminController::class, 'createUser']);

    Route::post('/admin/users/store',
        [AdminController::class, 'storeUser']);

    Route::get('/admin/settings/campus-setup-pin',
        [AdminController::class, 'campusSetupPin']);

    Route::post('/admin/settings/campus-setup-pin',
        [AdminController::class, 'updateCampusSetupPin']);
    // =====================================================
    // ADDED RIS ADMIN APPROVAL ROUTES
    // =====================================================

    Route::get(
        '/admin/procurement-review',
        [AdminController::class, 'risApprovals']
    )->name('admin.procurement-review.index');

    Route::post(
        '/admin/procurement-review/ris/{risId}/approve',
        [AdminController::class, 'approveRis']
    )->name('admin.procurement-review.ris.approve');

    Route::post(
        '/admin/procurement-review/ris/{risId}/reject',
        [AdminController::class, 'rejectRis']
    )->name('admin.procurement-review.ris.reject');

    // =====================================================
    // END ADDED RIS ADMIN APPROVAL ROUTES
    // =====================================================







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







// =====================================================
// PUT HERE THE MAINTENANCE PERSONNEL ROUTES BELOW
// =====================================================

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

// =====================================================
// TODAY'S REPORTS
// =====================================================

Route::get(
    '/maintenance/reports/today',
    [MaintenanceController::class, 'todayReports']
);

Route::get(
    '/maintenance/activities',
    [MaintenanceController::class, 'activities']
)->name('maintenance.activities.index');


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


// =====================================================
// REPORTER STATUS MANAGEMENT
// =====================================================

Route::patch(
    '/maintenance/reporters/{id}/deactivate',
    [MaintenanceController::class, 'deactivateReporter']
)->name('maintenance.reporters.deactivate');


Route::patch(
    '/maintenance/reporters/{id}/reactivate',
    [MaintenanceController::class, 'reactivateReporter']
)->name('maintenance.reporters.reactivate');

// =====================================================
// REPORTER HISTORY PAGE
// =====================================================

Route::get(
    '/maintenance/reporters/{id}/history',
    [MaintenanceController::class, 'reporterHistory']
)->name('maintenance.reporters.history');


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
    '/equipment/{qrCode}',
    [MaintenanceController::class, 'equipmentByQr']
);



// ==============================
// QR ROUTES
// ==============================

Route::get(
    '/maintenance/equipment/qr-tools',
    [QRController::class, 'qrTools']
);

Route::post(
    '/maintenance/equipment/qr/generate/{id}',
    [QRController::class, 'generateQr']
);

Route::get(
    '/maintenance/equipment/qr-image/{code}',
    [QRController::class, 'qrImage']
);

Route::get(
    '/maintenance/equipment/qr/{code}/pdf',
    [QRController::class, 'downloadQrPdf']
);

Route::get(
    '/maintenance/equipment/qr/{code}/png',
    [QRController::class, 'downloadQrPng']
);

Route::get(
    '/maintenance/equipment/qr/{code}/svg',
    [QRController::class, 'downloadQrSvg']
);

Route::get(
    '/maintenance/equipment/qr/{code}/print',
    [QRController::class, 'printLabel']
);

Route::post(
    '/maintenance/equipment/qr/scan',
    [QRController::class, 'scanQr']
)->name('maintenance.equipment.qr.scan');

// =====================================================
// EQUIPMENT CATEGORY ROUTES
// =====================================================

Route::get(
    '/maintenance/equipment/categories',
    [MaintenanceController::class, 'equipmentCategories']
);

Route::post(
    '/maintenance/equipment/categories',
    [MaintenanceController::class, 'storeEquipmentCategory']
);

Route::put(
    '/maintenance/equipment/categories/{id}',
    [MaintenanceController::class, 'updateEquipmentCategory']
);

Route::delete(
    '/maintenance/equipment/categories/{id}',
    [MaintenanceController::class, 'deleteEquipmentCategory']
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
| INFRASTRUCTURE
|--------------------------------------------------------------------------
*/

Route::get(
    '/maintenance/infrastructure',
    [InfrastructureController::class, 'index']
)->name('maintenance.infrastructure.index');

Route::get(
    '/maintenance/infrastructure/campus',
    [InfrastructureController::class, 'loadCampus']
)->name('maintenance.infrastructure.campus.load');

Route::post(
    '/maintenance/infrastructure/campus',
    [InfrastructureController::class, 'storeCampus']
)->name('maintenance.infrastructure.campus.store');

Route::post(
    '/maintenance/infrastructure/campus/unlock-verify',
    [InfrastructureController::class, 'verifySetupUnlockCredential']
)->name('maintenance.infrastructure.campus.unlock-verify');

Route::post(
    '/maintenance/infrastructure/save-layout',
    [InfrastructureController::class,
    'saveLayout']
)->name('maintenance.infrastructure.layout.save');

Route::patch(
    '/maintenance/infrastructure/rooms/{room}',
    [InfrastructureController::class, 'updateRoom']
)->name('maintenance.infrastructure.rooms.update');

Route::delete(
    '/maintenance/infrastructure/rooms/{room}',
    [InfrastructureController::class, 'archiveRoom']
)->name('maintenance.infrastructure.rooms.archive');


Route::put(
    '/maintenance/infrastructure/rooms/{room}',
    [InfrastructureController::class,'updateRoom']
);

Route::patch(
    '/maintenance/equipment/{equipment}',
    [InfrastructureController::class, 'updateEquipment']
)->name('maintenance.equipment.update');

Route::put(
    '/maintenance/infrastructure/equipment/{equipment}',
    [InfrastructureController::class, 'updateEquipment']
);

Route::post(
    '/maintenance/infrastructure/equipment/{equipment}/transfer',
    [InfrastructureController::class, 'transferEquipment']
);

Route::delete(
    '/maintenance/infrastructure/equipment/{equipment}',
    [InfrastructureController::class, 'archiveEquipment']
);

Route::post(
    '/maintenance/infrastructure/equipment',
    [InfrastructureController::class, 'storeEquipment']
);


Route::get(

    '/maintenance/infrastructure/rooms/{room}/equipment',

    [InfrastructureController::class,'roomEquipment']

)->name('maintenance.infrastructure.room-equipment');

Route::get(
    '/maintenance/infrastructure/rooms/{room}/layout',
    [InfrastructureController::class, 'getLayout']
)->name('maintenance.infrastructure.room-layout');

Route::patch(
    '/maintenance/infrastructure/workstation-slots/{workstationSlot}/coordinates',
    [InfrastructureController::class, 'updateCoordinates']
)->name('maintenance.infrastructure.workstation-slots.coordinates');

Route::post(
    '/maintenance/infrastructure/rooms/{room}/workstation-slots',
    [InfrastructureController::class, 'storeWorkstationSlots']
)->name('maintenance.infrastructure.workstation-slots.store');

/*
|--------------------------------------------------------------------------
| INFRASTRUCTURE
|--------------------------------------------------------------------------


Route::get(
    '/maintenance/infrastructure',
    [MaintenanceController::class, 'infrastructure']
);

Route::post(
    '/maintenance/buildings/store',
    [MaintenanceController::class, 'storeBuilding']
);

Route::post(
    '/maintenance/buildings/update/{id}',
    [MaintenanceController::class, 'updateBuilding']
);

Route::delete(
    '/maintenance/buildings/delete/{id}',
    [MaintenanceController::class, 'deleteBuilding']
);

Route::post(
    '/maintenance/floors/store',
    [MaintenanceController::class, 'storeFloor']
);

Route::post(
    '/maintenance/floors/update/{id}',
    [MaintenanceController::class, 'updateFloor']
);

Route::delete(
    '/maintenance/floors/delete/{id}',
    [MaintenanceController::class, 'deleteFloor']
);

Route::post(
    '/maintenance/rooms/store',
    [MaintenanceController::class, 'storeRoom']
);

Route::post(
    '/maintenance/rooms/update/{id}',
    [MaintenanceController::class, 'updateRoom']
);

Route::delete(
    '/maintenance/rooms/delete/{id}',
    [MaintenanceController::class, 'deleteRoom']
);



Route::get(
    '/maintenance/rooms/{id}',
    [MaintenanceController::class, 'roomDetails']
);*/

// =====================================================
// TODAY'S MAINTENANCE SCHEDULES
// =====================================================

Route::get(
    '/maintenance/schedules/today',
    [MaintenanceController::class, 'todaySchedules']
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


/*
|--------------------------------------------------------------------------
| REPORTERS MODULE
|--------------------------------------------------------------------------
*/

Route::get(
    '/maintenance/reporters',
    [MaintenanceController::class, 'reporters']
);

Route::post(
    '/store-report',
    [MaintenanceController::class, 'storeReport']
)->name('reports.store');

Route::post(
    '/maintenance/reporters/update',
    [MaintenanceController::class, 'updateReporter']
);

Route::post(
    '/maintenance/reporters/delete',
    [MaintenanceController::class, 'deleteReporter']
);





// =====================================================
// NOTIFICATIONS PAGE
// =====================================================

Route::get(
    '/maintenance/notifications',
    [MaintenanceController::class, 'notifications']
);


// =====================================================
// MARK ALL AS READ
// KEEP STATIC ROUTE BEFORE DYNAMIC ROUTES
// =====================================================

Route::post(
    '/maintenance/notifications/mark-all-read',
    [MaintenanceController::class, 'markAllNotificationsAsRead']
);


// =====================================================
// OPEN SINGLE NOTIFICATION
// =====================================================

Route::get(
    '/maintenance/notifications/{id}/open',
    [MaintenanceController::class, 'openNotification']
);

Route::middleware([
    'auth',
    'maintenance',
])->prefix('maintenance')->group(function () {

    // =====================================================
    // DASHBOARD
    // =====================================================

    Route::get(
        '/dashboard',
        [MaintenanceController::class, 'dashboard']
    );


    // =====================================================
    // NOTIFICATIONS PAGE
    // =====================================================

    Route::get(
        '/notifications',
        [MaintenanceController::class, 'notifications']
    );


    // =====================================================
    // MARK ALL NOTIFICATIONS AS READ
    // =====================================================

    Route::post(
        '/notifications/mark-all-read',
        [
            MaintenanceController::class,
            'markAllNotificationsAsRead'
        ]
    );


    // =====================================================
    // OPEN NOTIFICATION
    // =====================================================

    Route::get(
        '/notifications/{id}/open',
        [
            MaintenanceController::class,
            'openNotification'
        ]
    );


    // =====================================================
    // PUT ALL OTHER MAINTENANCE ROUTES HERE
    // =====================================================

});











// =====================================================
// PUT HERE THE PURCHASER ROUTES BELOW
// =====================================================


// =====================================================
// PURCHASER ROUTES
// =====================================================

Route::middleware([
    'auth',
    'purchaser',
])
    ->prefix('purchaser')
    ->name('purchaser.')
    ->group(function () {


        // =====================================================
        // PURCHASER DASHBOARD
        // =====================================================

        Route::get(
            '/dashboard',
            [PurchaserController::class, 'dashboard']
        )
        ->name('dashboard');


        // =====================================================
        // REPLACEMENT REQUESTS
        // =====================================================

        Route::get(
            '/procurement/replacement-requests',
            [ReplacementRequestController::class, 'index']
        )
        ->name('procurement.replacement-requests');

        Route::post(
            '/procurement/replacement-requests/{requestId}/approve',
            [ReplacementRequestController::class, 'approve']
        )
        ->name('procurement.replacement-requests.approve');

        Route::post(
            '/procurement/replacement-requests/{requestId}/reject',
            [ReplacementRequestController::class, 'reject']
        )
        ->name('procurement.replacement-requests.reject');

        Route::post(
            '/procurement/replacement-requests/{requestId}/archive',
            [ReplacementRequestController::class, 'archive']
        )
        ->name('procurement.replacement-requests.archive');

        Route::post(
            '/procurement/replacement-requests/{requestId}/restore',
            [ReplacementRequestController::class, 'restore']
        )
        ->name('procurement.replacement-requests.restore');
        // =====================================================
        // ADDED RIS MODULE ROUTES
        // =====================================================

        Route::get(
            '/ris',
            [PurchaserController::class, 'risIndex']
        )
            ->name('ris.index');

        Route::post(
                    '/ris',
                    [PurchaserController::class, 'storeRis']
                )
                    ->name('ris.store');

                // RIS MODULE: UPDATE DRAFT OR MINOR REVISION RIS
                Route::put(
                    '/ris/{risId}',
                    [PurchaserController::class, 'updateRis']
                )
                    ->name('ris.update');

        Route::post(
            '/ris/{risId}/submit',
            [PurchaserController::class, 'submitRis']
        )
            ->name('ris.submit');
        // =====================================================
        // ADDED RIS MODULE: SUPPORTING DOCUMENT DOWNLOAD ROUTE
        // =====================================================

        Route::get(
            '/ris/attachments/{attachmentId}/download',
            [PurchaserController::class, 'downloadRisAttachment']
        )
            ->name('ris.attachments.download');

        // =====================================================
        // END ADDED RIS MODULE ROUTES
        // =====================================================

        // =====================================================
        // AUTHORITY TO PURCHASE
        // =====================================================

        Route::get(
            '/authority-to-purchase',
            [AuthorityToPurchaseController::class, 'index']
        )
            ->name('atp.index');

        Route::get(
            '/authority-to-purchase/create',
            [AuthorityToPurchaseController::class, 'create']
        )
            ->name('atp.create');

        Route::post(
            '/authority-to-purchase',
            [AuthorityToPurchaseController::class, 'store']
        )
            ->name('atp.store');

        Route::get(
            '/authority-to-purchase/{id}',
            [AuthorityToPurchaseController::class, 'show']
        )
            ->name('atp.show');

        Route::get(
            '/authority-to-purchase/{id}/edit',
            [AuthorityToPurchaseController::class, 'edit']
        )
            ->name('atp.edit');

        Route::put(
            '/authority-to-purchase/{id}',
            [AuthorityToPurchaseController::class, 'update']
        )
            ->name('atp.update');

        Route::post(
            '/authority-to-purchase/{id}/submit',
            [AuthorityToPurchaseController::class, 'submit']
        )
            ->name('atp.submit');

        Route::post(
            '/authority-to-purchase/{id}/approve',
            [AuthorityToPurchaseController::class, 'approve']
        )
            ->name('atp.approve');

        Route::post(
            '/authority-to-purchase/{id}/reject',
            [AuthorityToPurchaseController::class, 'reject']
        )
            ->name('atp.reject');

        Route::post(
            '/authority-to-purchase/{id}/archive',
            [AuthorityToPurchaseController::class, 'archive']
        )
            ->name('atp.archive');

        Route::post(
            '/authority-to-purchase/{id}/restore',
            [AuthorityToPurchaseController::class, 'restore']
        )
            ->name('atp.restore');

        // =====================================================
        // SUPPLIERS
        // =====================================================

        Route::get(
            '/suppliers',
            [\App\Http\Controllers\SupplierController::class, 'index']
        )
        ->name('suppliers.index');

        Route::get(
            '/suppliers/create',
            [\App\Http\Controllers\SupplierController::class, 'create']
        )
        ->name('suppliers.create');

        Route::post(
            '/suppliers',
            [\App\Http\Controllers\SupplierController::class, 'store']
        )
        ->name('suppliers.store');


        // =====================================================
        // PURCHASER URGENT REPORT ROUTES
        // =====================================================


        // =====================================================
        // SHOW URGENT REPORTS
        // =====================================================

        Route::get(
            '/reports/urgent',
            [PurchaserController::class, 'urgentReports']
        )
            ->name('reports.urgent');


        // =====================================================
        // ACCEPT URGENT REPORT
        // =====================================================

        Route::post(
            '/reports/urgent/{reportId}/accept',
            [PurchaserController::class, 'acceptUrgentReport']
        )
            ->name('reports.urgent.accept');


        // =====================================================
        // RESOLVE URGENT REPORT
        // =====================================================

        Route::post(
            '/reports/urgent/{reportId}/resolve',
            [PurchaserController::class, 'resolveUrgentReport']
        )
            ->name('reports.urgent.resolve');


        // =====================================================
        // SEND URGENT REPORT FOR REPLACEMENT
        // =====================================================

        Route::post(
            '/reports/urgent/{reportId}/replacement',
            [PurchaserController::class, 'replaceUrgentReport']
        )
            ->name('reports.urgent.replacement');


        // =====================================================
        // PURCHASER ARCHIVE URGENT REPORT
        // =====================================================

        Route::post(
            '/reports/urgent/{reportId}/archive',
            [
                PurchaserController::class,
                'archiveUrgentReport',
            ]
        )
            ->name(
                'reports.urgent.archive'
            );


        // =====================================================
        // PURCHASER RESTORE URGENT REPORT
        // =====================================================

        Route::post(
            '/reports/urgent/{reportId}/restore',
            [
                PurchaserController::class,
                'restoreUrgentReport',
            ]
        )
            ->name(
                'reports.urgent.restore'
            );

        // =====================================================
        // PURCHASER RIS ROUTES
        // =====================================================

        Route::get(
            '/ris',
            [PurchaserController::class, 'risIndex']
        )
            ->name('ris.index');

        Route::post(
            '/ris',
            [PurchaserController::class, 'storeRis']
        )
            ->name('ris.store');

        Route::post(
            '/ris/{ris}/submit',
            [PurchaserController::class, 'submitRis']
        )
            ->name('ris.submit');

        Route::get(
            '/ris/{ris}/print',
            [PurchaserController::class, 'printRis']
        )
            ->name('ris.print');

    });





// =====================================================
// PUT HERE THE PRESIDENT ROUTES BELOW
// =====================================================

// =====================================================
// PRESIDENT ROUTES
// =====================================================

Route::middleware([
    'auth',
    'president',
])
    ->prefix('president')
    ->name('president.')
    ->group(function () {


        // =====================================================
        // DASHBOARD
        // =====================================================

        Route::get(
            '/dashboard',
            [PresidentController::class, 'dashboard']
        )->name('dashboard');

        // =====================================================
        // APPROVALS
        // =====================================================

        Route::get(
            '/approvals',
            [PresidentController::class, 'approvals']
        )->name('approvals');

        Route::get(
            '/approvals/history',
            [PresidentController::class, 'approvalHistory']
        )->name('approvals.history');

        Route::get(
            '/approvals/digital-signature',
            [PresidentController::class, 'digitalSignature']
        )->name('approvals.digital-signature');

        // =====================================================
        // REPORTS
        // =====================================================

        Route::get(
            '/reports/approved',
            [PresidentController::class, 'approvedReports']
        )->name('reports.approved');

        Route::get(
            '/reports/monthly-summary',
            [PresidentController::class, 'monthlySummary']
        )->name('reports.monthly-summary');

        // =====================================================
        // APPROVAL DECISION ENDPOINTS
        // =====================================================

        Route::get(
            '/approvals/ris/{ris}/approve-form',
            [PresidentController::class, 'approveForm']
        )->name('approvals.ris.approve-form');

        Route::post(
            '/approvals/ris/decide',
            [PresidentController::class, 'decideRis']
        )->name('approvals.ris.decide');

        Route::post(
            '/approvals/procurement/decide',
            [PresidentController::class, 'decideProcurement']
        )->name('approvals.procurement.decide');

        // =====================================================
        // NOTIFICATIONS
        // =====================================================

        Route::get(
            '/notifications',
            [PresidentController::class, 'notifications']
        )->name('notifications');

        Route::get(
            '/notifications/rejection-history',
            [PresidentController::class, 'rejectionHistory']
        )->name('notifications.rejection-history');

        // =====================================================
        // PROFILE
        // =====================================================

        Route::get(
            '/profile',
            [PresidentController::class, 'profile']
        )->name('profile');
        
        // =====================================================
        // PRESIDENT: PRINTABLE RIS FOR APPROVAL PREVIEW
        // =====================================================
        Route::get(
            '/ris/{ris}/print',
            [PresidentController::class, 'viewRis']
        )
            ->name('ris.print');

        // =====================================================
        // PRESIDENT: RIS VIEWER (for preview)
        // =====================================================
        Route::get(
            '/ris/{ris}/view',
            [PresidentController::class, 'viewRis']
        )
            ->name('ris.view');
     
     });




// =====================================================
// PUT HERE THE ACCOUNTING ROUTES BELOW
// =====================================================

Route::middleware(['auth', 'accounting'])
    ->prefix('accounting')
    ->group(function () {

        Route::get(
            '/dashboard',
            [AccountingController::class, 'dashboard']
        );

        Route::get(
            '/request-check',
            [AccountingController::class, 'requestCheck']
        );

        Route::get(
            '/authority-to-purchase',
            [AccountingController::class, 'authorityToPurchase']
        );

        Route::get(
            '/financial-records',
            [AccountingController::class, 'financialRecords']
        );

        Route::get(
            '/liquidation-reports',
            [AccountingController::class, 'liquidationReports']
        );

        Route::get(
            '/notifications',
            [AccountingController::class, 'notifications']
        );

        

    });




// =====================================================
// PUT HERE THE RECEIVING ROUTES BELOW
// =====================================================

Route::middleware(['auth', 'receiving'])
    ->prefix('receiving')
    ->group(function () {

        Route::get('/dashboard', [ReceivingController::class, 'dashboard']);

        Route::get('/quick-access/{section}', [ReceivingController::class, 'quickAccessContent']);

        Route::get('/reports', [ReceivingController::class, 'reports']);

        Route::get('/delivered-items', [ReceivingController::class, 'deliveredItems']);

        Route::get('/inventory-update', [ReceivingController::class, 'inventoryUpdate']);

        Route::get('/official-receipts', [ReceivingController::class, 'officialReceipts']);

        Route::get('/supplier-records', [ReceivingController::class, 'supplierRecords']);

        Route::get('/history', [ReceivingController::class, 'history']);

        Route::get('/logs', [ReceivingController::class, 'receivingLogs']);

        Route::get('/ris/{ris}/print', [ReceivingController::class, 'printRis']);

        Route::get('/reports/{report}/print', [ReceivingController::class, 'printReport']);

        Route::get('/export-pdf', [ReceivingController::class, 'exportTablePdf']);

        Route::post('/reports/{atp}/accept', [ReceivingController::class, 'accept']);

        Route::post('/reports/{atp}/return', [ReceivingController::class, 'returnReport']);

    });

Route::get(
    '/maintenance/report-card/{id}',
    [MaintenanceController::class, 'reportCard']
)->name('maintenance.report.card');

// =====================================================
// MESSAGING ROUTES
// =====================================================

Route::middleware(['auth'])
    ->prefix('messages')
    ->name('messages.')
    ->group(function () {

        Route::get(
            '/conversations',
            [MessageController::class, 'index']
        )->name('conversations.index');

        Route::get(
            '/users',
            [MessageController::class, 'users']
        )->name('users');

        Route::get(
            '/conversations/{conversation}',
            [MessageController::class, 'show']
        )->name('conversations.show');

        Route::get(
            '/conversations/{conversation}/messages',
            [MessageController::class, 'messages']
        )->name('conversations.messages');

        Route::post(
            '/conversations/{conversation}/send',
            [MessageController::class, 'store']
        )->name('conversations.send');

        Route::post(
            '/conversations/{conversation}/read',
            [MessageController::class, 'markAsRead']
        )->name('conversations.read');

        Route::post(
            '/conversations',
            [MessageController::class, 'storeConversation']
        )->name('conversations.store');

        Route::post(
            '/conversations/group',
            [MessageController::class, 'storeGroupConversation']
        )->name('conversations.group.store');

        Route::post(
            '/conversations/{conversation}/mute',
            [MessageController::class, 'muteConversation']
        )->name('conversations.mute');

        Route::post(
            '/conversations/{conversation}/unmute',
            [MessageController::class, 'unmuteConversation']
        )->name('conversations.unmute');


        Route::delete(
            '/conversations/{conversation}',
            [MessageController::class, 'destroy']
        )->name('conversations.destroy');

        Route::delete(
            '/conversations/{conversation}',
            [MessageController::class, 'destroy']
        )->name('conversations.destroy');

        Route::post(
            '/upload',
            [MessageController::class, 'uploadAttachment']
        )->name('upload');

        Route::post(
            '/conversations/{conversation}/messages/{message}/delivered',
            [MessageController::class, 'markAsDelivered']
        )->name('delivered');

        Route::post(
            '/conversations/{conversation}/messages/{message}/reaction',
            [MessageController::class, 'reactToMessage']
        )->name('reaction');

        Route::patch(
            '/conversations/{conversation}/messages/{message}/edit',
            [MessageController::class, 'editMessage']
        )->name('message.edit');

        Route::post(
            '/conversations/{conversation}/messages/{message}/unsend',
            [MessageController::class, 'unsendMessage']
        )->name('message.unsend');

        Route::delete(
            '/conversations/{conversation}/messages/{message}/remove',
            [MessageController::class, 'removeMessageForUser']
        )->name('message.remove');

        Route::post(
            '/conversations/{conversation}/messages/{message}/pin',
            [MessageController::class, 'pinMessage']
        )->name('message.pin');

        Route::post(
            '/conversations/{conversation}/messages/{message}/forward',
            [MessageController::class, 'forwardMessage']
        )->name('message.forward');


        Route::post(
            '/sync-delivered',
            [MessageController::class, 'syncDeliveredMessages']
        )->name('sync-delivered');

        Route::get(
            '/unread-count',
            [MessageController::class, 'unreadCount']
        )->name('unread-count');

        Route::post(
            '/conversations/{conversation}/typing',
            [MessageController::class, 'typing']
        )->name('typing');

        Route::get(
            '/conversations/{conversation}/pinned',
            [MessageController::class, 'pinnedMessages']
        )->name('pinned');

        Route::post(
            '/conversations/{conversation}/members',
            [MessageController::class, 'addGroupMembers']
        )->name('conversations.members.add');


        // LEAVE GROUP
        Route::post(
            '/conversations/{conversation}/leave',
            [MessageController::class, 'leaveGroup']
        )->name('conversations.leave');

        Route::patch(
            '/conversations/{conversation}/name',
            [MessageController::class, 'renameGroup']
        )->name('messages.conversations.rename');

        Route::post(
            '/conversations/{conversation}/image',
            [MessageController::class, 'updateGroupImage']
        )->name('conversations.image.update');

        Route::post(
            '/calls/signal',
            [MessageController::class, 'callSignal']
        )->name('calls.signal');


    });

// =====================================================
// USER ONLINE STATUS HEARTBEAT
// =====================================================

Route::post('/user/heartbeat', function () {

    // =============================================
    // MAKE SURE USER IS LOGGED IN
    // =============================================

    if (!auth()->check()) {
        return response()->json([
            'success' => false,
        ], 401);
    }


    // =============================================
    // UPDATE LAST ACTIVE TIME DIRECTLY
    // =============================================

    \Illuminate\Support\Facades\DB::table('users_table')
        ->where('user_id', auth()->user()->user_id)
        ->update([
            'last_active_at' => now(),
        ]);


    return response()->json([
        'success' => true,
        'last_active_at' => now(),
    ]);

})->middleware('auth');



require __DIR__.'/auth.php';
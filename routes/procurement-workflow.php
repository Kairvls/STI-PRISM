<?php

/**
 * Shared procurement workflow routes for purchaser and maintenance portals.
 * Included inside groups that set prefix + name prefix (purchaser. / maintenance.).
 */

use App\Http\Controllers\AuthorityToPurchaseController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\FileMaintenanceController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemSubCategoryController;
use App\Http\Controllers\LiquidationReportController;
use App\Http\Controllers\ProcurementRecordPackageController;
use App\Http\Controllers\ReceivingReportController;
use App\Http\Controllers\ReplacementRequestController;
use App\Http\Controllers\RequestForCheckController;
use App\Http\Controllers\RisController;
use App\Http\Controllers\UomController;
use App\Support\ProcurementPortal;
use Illuminate\Support\Facades\Route;

// =====================================================
// REPLACEMENT REQUESTS
// =====================================================

Route::get(
    '/procurement/replacement-requests',
    [ReplacementRequestController::class, 'index']
)->name('procurement.replacement-requests');

Route::post(
    '/procurement/replacement-requests/{requestId}/approve',
    [ReplacementRequestController::class, 'approve']
)->name('procurement.replacement-requests.approve');

Route::post(
    '/procurement/replacement-requests/{requestId}/reject',
    [ReplacementRequestController::class, 'reject']
)->name('procurement.replacement-requests.reject');

Route::post(
    '/procurement/replacement-requests/{requestId}/archive',
    [ReplacementRequestController::class, 'archive']
)->name('procurement.replacement-requests.archive');

Route::post(
    '/procurement/replacement-requests/{requestId}/restore',
    [ReplacementRequestController::class, 'restore']
)->name('procurement.replacement-requests.restore');


// =====================================================
// RIS
// =====================================================

Route::get('/ris', [RisController::class, 'index'])->name('ris.index');
Route::post('/ris', [RisController::class, 'store'])->name('ris.store');
Route::post('/ris/saved-signatures', [RisController::class, 'storeSavedSignature'])->name('ris.saved-signatures.store');
Route::delete('/ris/saved-signatures/{signature}', [RisController::class, 'destroySavedSignature'])
    ->whereNumber('signature')
    ->name('ris.saved-signatures.destroy');
Route::get('/ris/attachments/{attachmentId}/download', [RisController::class, 'downloadAttachment'])
    ->name('ris.attachments.download');
Route::get('/ris/export-blank-xlsx', [RisController::class, 'exportBlankExcel'])->name('ris.export-blank-xlsx');
Route::get('/ris/export-blank-docx', [RisController::class, 'exportBlankWord'])->name('ris.export-blank-docx');
Route::put('/ris/{risId}', [RisController::class, 'update'])->name('ris.update');
Route::post('/ris/{risId}/submit', [RisController::class, 'submit'])->name('ris.submit');
Route::get('/ris/{risId}/print', [RisController::class, 'print'])->name('ris.print');
Route::get('/ris/{risId}/export-xlsx', [RisController::class, 'exportExcel'])->name('ris.export-xlsx');
Route::get('/ris/{risId}/export-docx', [RisController::class, 'exportWord'])->name('ris.export-docx');


// =====================================================
// AUTHORITY TO PURCHASE
// =====================================================

Route::get('/authority-to-purchase', [AuthorityToPurchaseController::class, 'index'])->name('atp.index');
Route::get('/authority-to-purchase/create', [AuthorityToPurchaseController::class, 'create'])->name('atp.create');
Route::post('/authority-to-purchase', [AuthorityToPurchaseController::class, 'store'])->name('atp.store');
Route::get('/authority-to-purchase/export-blank-xlsx', [AuthorityToPurchaseController::class, 'exportBlankExcel'])->name('atp.export-blank-xlsx');
Route::get('/authority-to-purchase/export-blank-docx', [AuthorityToPurchaseController::class, 'exportBlankWord'])->name('atp.export-blank-docx');
Route::get('/authority-to-purchase/{id}', [AuthorityToPurchaseController::class, 'show'])->name('atp.show');
Route::get('/authority-to-purchase/{id}/edit', [AuthorityToPurchaseController::class, 'edit'])->name('atp.edit');
Route::put('/authority-to-purchase/{id}', [AuthorityToPurchaseController::class, 'update'])->name('atp.update');
Route::post('/authority-to-purchase/{id}/submit', [AuthorityToPurchaseController::class, 'submit'])->name('atp.submit');
Route::post('/authority-to-purchase/{id}/payment-path', [AuthorityToPurchaseController::class, 'choosePaymentPath'])->name('atp.payment-path');
Route::post('/authority-to-purchase/{id}/archive', [AuthorityToPurchaseController::class, 'archive'])->name('atp.archive');
Route::post('/authority-to-purchase/{id}/restore', [AuthorityToPurchaseController::class, 'restore'])->name('atp.restore');
Route::get('/authority-to-purchase/{id}/export-xlsx', [AuthorityToPurchaseController::class, 'exportExcel'])->name('atp.export-xlsx');
Route::get('/authority-to-purchase/{id}/export-docx', [AuthorityToPurchaseController::class, 'exportWord'])->name('atp.export-docx');


// =====================================================
// REQUEST FOR CHECK
// =====================================================

Route::get('/request-check', [RequestForCheckController::class, 'index'])->name('rfc.index');
Route::post('/request-check', [RequestForCheckController::class, 'store'])->name('rfc.store');
Route::get('/request-check/export-blank-xlsx', [RequestForCheckController::class, 'exportBlankExcel'])->name('rfc.export-blank-xlsx');
Route::get('/request-check/export-blank-docx', [RequestForCheckController::class, 'exportBlankWord'])->name('rfc.export-blank-docx');
Route::put('/request-check/{id}', [RequestForCheckController::class, 'update'])->name('rfc.update');
Route::post('/request-check/{id}/submit', [RequestForCheckController::class, 'submit'])->name('rfc.submit');
Route::post('/request-check/{id}/archive', [RequestForCheckController::class, 'archive'])->name('rfc.archive');
Route::post('/request-check/{id}/restore', [RequestForCheckController::class, 'restore'])->name('rfc.restore');
Route::get('/request-check/{id}/attachments/{attachmentId}', [RequestForCheckController::class, 'downloadAttachment'])->name('rfc.attachment');
Route::get('/request-check/{id}/export-xlsx', [RequestForCheckController::class, 'exportExcel'])->name('rfc.export-xlsx');
Route::get('/request-check/{id}/export-docx', [RequestForCheckController::class, 'exportWord'])->name('rfc.export-docx');


// =====================================================
// RECEIVING REPORTS
// =====================================================

Route::get('/receiving-reports', [ReceivingReportController::class, 'index'])->name('rr.index');
Route::post('/receiving-reports', [ReceivingReportController::class, 'store'])->name('rr.store');
Route::get('/receiving-reports/export-blank-xlsx', [ReceivingReportController::class, 'exportBlankExcel'])->name('rr.export-blank-xlsx');
Route::get('/receiving-reports/export-blank-docx', [ReceivingReportController::class, 'exportBlankWord'])->name('rr.export-blank-docx');
Route::put('/receiving-reports/{id}', [ReceivingReportController::class, 'update'])->name('rr.update');
Route::post('/receiving-reports/{id}/submit', [ReceivingReportController::class, 'submit'])->name('rr.submit');
Route::post('/receiving-reports/{id}/archive', [ReceivingReportController::class, 'archive'])->name('rr.archive');
Route::post('/receiving-reports/{id}/restore', [ReceivingReportController::class, 'restore'])->name('rr.restore');
Route::get('/receiving-reports/{id}/export-xlsx', [ReceivingReportController::class, 'exportExcel'])->name('rr.export-xlsx');
Route::get('/receiving-reports/{id}/export-docx', [ReceivingReportController::class, 'exportWord'])->name('rr.export-docx');


// =====================================================
// LIQUIDATION REPORTS
// =====================================================

Route::get('/liquidation-reports', [LiquidationReportController::class, 'index'])->name('liq.index');
Route::post('/liquidation-reports', [LiquidationReportController::class, 'store'])->name('liq.store');
Route::get('/liquidation-reports/export-blank-xlsx', [LiquidationReportController::class, 'exportBlankExcel'])->name('liq.export-blank-xlsx');
Route::get('/liquidation-reports/export-blank-docx', [LiquidationReportController::class, 'exportBlankWord'])->name('liq.export-blank-docx');
Route::put('/liquidation-reports/{id}', [LiquidationReportController::class, 'update'])->name('liq.update');
Route::post('/liquidation-reports/{id}/submit', [LiquidationReportController::class, 'submit'])->name('liq.submit');
Route::post('/liquidation-reports/{id}/archive', [LiquidationReportController::class, 'archive'])->name('liq.archive');
Route::post('/liquidation-reports/{id}/restore', [LiquidationReportController::class, 'restore'])->name('liq.restore');
Route::get('/liquidation-reports/{id}/attachments/{attachmentId}', [LiquidationReportController::class, 'downloadAttachment'])->name('liq.attachment');
Route::get('/liquidation-reports/{id}/export-xlsx', [LiquidationReportController::class, 'exportExcel'])->name('liq.export-xlsx');
Route::get('/liquidation-reports/{id}/export-docx', [LiquidationReportController::class, 'exportWord'])->name('liq.export-docx');


// =====================================================
// COMPILED PROCUREMENT RECORDS
// =====================================================

Route::get('/procurement-records', [ProcurementRecordPackageController::class, 'index'])->name('procurement-records.index');
Route::post('/procurement-records', [ProcurementRecordPackageController::class, 'store'])->name('procurement-records.store');


// =====================================================
// SUPPLIERS
// =====================================================

Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])->name('suppliers.index');
Route::get('/suppliers/create', [\App\Http\Controllers\SupplierController::class, 'create'])->name('suppliers.create');
Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store');
Route::post('/suppliers/quick-store', [\App\Http\Controllers\SupplierController::class, 'quickStore'])->name('suppliers.quick-store');
Route::get('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'show'])->name('suppliers.show');
Route::get('/suppliers/{supplier}/edit', [\App\Http\Controllers\SupplierController::class, 'edit'])->name('suppliers.edit');
Route::put('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
Route::patch('/suppliers/{supplier}/activate', [\App\Http\Controllers\SupplierController::class, 'activate'])->name('suppliers.activate');
Route::patch('/suppliers/{supplier}/deactivate', [\App\Http\Controllers\SupplierController::class, 'deactivate'])->name('suppliers.deactivate');
Route::post('/suppliers/{supplier}/notes', [\App\Http\Controllers\SupplierController::class, 'storeNote'])->name('suppliers.notes.store');
Route::post('/suppliers/{supplier}/blacklist', [\App\Http\Controllers\SupplierController::class, 'blacklist'])->name('suppliers.blacklist');
Route::post('/suppliers/{supplier}/unblacklist', [\App\Http\Controllers\SupplierController::class, 'unblacklist'])->name('suppliers.unblacklist');


// =====================================================
// FILE MAINTENANCE
// =====================================================

Route::get('/file-maintenance', [FileMaintenanceController::class, 'index'])->name('file-maintenance.index');

Route::get('/brands', function () {
    return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'brands']);
})->name('brands.index');
Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

Route::get('/uom', function () {
    return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'uom']);
})->name('uom.index');
Route::post('/uom', [UomController::class, 'store'])->name('uom.store');
Route::put('/uom/{uom}', [UomController::class, 'update'])->name('uom.update');
Route::delete('/uom/{uom}', [UomController::class, 'destroy'])->name('uom.destroy');

Route::get('/categories', function () {
    return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'categories']);
})->name('categories.index');
Route::post('/categories', [ItemCategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [ItemCategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [ItemCategoryController::class, 'destroy'])->name('categories.destroy');

Route::get('/subcategories', function () {
    return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'subcategories']);
})->name('subcategories.index');
Route::post('/subcategories', [ItemSubCategoryController::class, 'store'])->name('subcategories.store');
Route::put('/subcategories/{subcategory}', [ItemSubCategoryController::class, 'update'])->name('subcategories.update');
Route::delete('/subcategories/{subcategory}', [ItemSubCategoryController::class, 'destroy'])->name('subcategories.destroy');

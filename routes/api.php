<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteJobController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\CasualController;
use App\Http\Controllers\Api\CasualSiteJobController;

Route::get('company-profile', [CompanyProfileController::class, 'show']);
Route::put('company-profile', [CompanyProfileController::class, 'update']);

Route::apiResource('customers', CustomerController::class);
Route::get('customers/{customer}/statement', [CustomerController::class, 'statement']);

Route::apiResource('site-jobs', SiteJobController::class);
Route::patch('site-jobs/{siteJob}/status', [SiteJobController::class, 'updateStatus']);

Route::apiResource('invoices', InvoiceController::class);

Route::apiResource('payments', PaymentController::class);

Route::apiResource('casuals', CasualController::class);
Route::get('casuals-report/jobs', [CasualController::class, 'jobsReport']);

// Site job casuals assignment routes
Route::get('site-jobs/{siteJob}/casuals', [CasualSiteJobController::class, 'index']);
Route::post('site-jobs/{siteJob}/casuals', [CasualSiteJobController::class, 'store']);
Route::delete('site-jobs/{siteJob}/casuals/{casual}', [CasualSiteJobController::class, 'destroy']);

// Export routes
Route::prefix('export')->group(function () {
    Route::get('invoices/{invoice}/pdf', [ExportController::class, 'invoicePdf']);
    Route::get('invoices/{invoice}/pdf/preview', [ExportController::class, 'invoicePdfPreview']);
    Route::get('invoices/excel', [ExportController::class, 'invoicesExcel']);
    Route::get('customers/excel', [ExportController::class, 'customersExcel']);
    Route::get('jobs/excel', [ExportController::class, 'jobsExcel']);
    Route::get('payments/excel', [ExportController::class, 'paymentsExcel']);
    Route::get('casuals/excel', [ExportController::class, 'casualsExcel']);
    Route::get('casuals-report/excel', [ExportController::class, 'casualsReportExcel']);
});

Route::prefix('import')->group(function () {
    Route::post('casuals', [ExportController::class, 'importCasuals']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteJobController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\ExportController;

Route::get('company-profile', [CompanyProfileController::class, 'show']);
Route::put('company-profile', [CompanyProfileController::class, 'update']);

Route::apiResource('customers', CustomerController::class);
Route::get('customers/{customer}/statement', [CustomerController::class, 'statement']);

Route::apiResource('site-jobs', SiteJobController::class);
Route::patch('site-jobs/{siteJob}/status', [SiteJobController::class, 'updateStatus']);

Route::apiResource('invoices', InvoiceController::class);

Route::apiResource('payments', PaymentController::class);

// Export routes
Route::prefix('export')->group(function () {
    Route::get('invoices/{invoice}/pdf', [ExportController::class, 'invoicePdf']);
    Route::get('invoices/{invoice}/pdf/preview', [ExportController::class, 'invoicePdfPreview']);
    Route::get('invoices/excel', [ExportController::class, 'invoicesExcel']);
    Route::get('customers/excel', [ExportController::class, 'customersExcel']);
    Route::get('jobs/excel', [ExportController::class, 'jobsExcel']);
    Route::get('payments/excel', [ExportController::class, 'paymentsExcel']);
});

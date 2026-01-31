<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteJobController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CompanyProfileController;

Route::get('company-profile', [CompanyProfileController::class, 'show']);
Route::put('company-profile', [CompanyProfileController::class, 'update']);

Route::apiResource('customers', CustomerController::class);

Route::apiResource('site-jobs', SiteJobController::class);
Route::patch('site-jobs/{siteJob}/status', [SiteJobController::class, 'updateStatus']);

Route::apiResource('invoices', InvoiceController::class);

Route::apiResource('payments', PaymentController::class);

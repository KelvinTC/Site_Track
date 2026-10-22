<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteJobController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\CasualController;
use App\Http\Controllers\Api\CasualSiteJobController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\CostCentreController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'company'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::post('switch-company', [AuthController::class, 'switchCompany']);
    Route::get('companies', [AuthController::class, 'companies']);

    /*
    |--------------------------------------------------------------------------
    | Company Profile (Owner Only for updates)
    |--------------------------------------------------------------------------
    */
    Route::get('company-profile', [CompanyProfileController::class, 'show']);

    Route::middleware('role:owner')->group(function () {
        Route::put('company-profile', [CompanyProfileController::class, 'update']);
        Route::post('company-profile/logo', [CompanyProfileController::class, 'uploadLogo']);
        Route::delete('company-profile/logo', [CompanyProfileController::class, 'deleteLogo']);
    });

    /*
    |--------------------------------------------------------------------------
    | User Management (Admin Only: owner, admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Customers, Jobs, Workers (Admin Only: owner, admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin')->group(function () {
        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/statement', [CustomerController::class, 'statement']);

        // Site Jobs
        Route::apiResource('site-jobs', SiteJobController::class);
        Route::patch('site-jobs/{siteJob}/status', [SiteJobController::class, 'updateStatus']);

        // Workers (Casuals)
        Route::apiResource('casuals', CasualController::class);
        Route::get('casuals-report/jobs', [CasualController::class, 'jobsReport']);

        // Site job casuals assignment
        Route::get('site-jobs/{siteJob}/casuals', [CasualSiteJobController::class, 'index']);
        Route::post('site-jobs/{siteJob}/casuals', [CasualSiteJobController::class, 'store']);
        Route::delete('site-jobs/{siteJob}/casuals/{casual}', [CasualSiteJobController::class, 'destroy']);

        // Departments - Manage (store, update, destroy)
        Route::post('departments', [DepartmentController::class, 'store']);
        Route::put('departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);
    });

    // Department list for dropdowns - all authenticated users can view
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Invoices, Payments, Expenses (Finance: owner, admin, finance)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin,finance')->group(function () {
        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        Route::get('invoices-aging', [InvoiceController::class, 'aging']);
        Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);

        // Payments
        Route::apiResource('payments', PaymentController::class);

        // Expenses
        Route::apiResource('expense-categories', ExpenseCategoryController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::patch('expenses/{expense}/status', [ExpenseController::class, 'updateStatus']);
        Route::get('expenses-summary', [ExpenseController::class, 'summary']);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('employee-jobs', [ReportController::class, 'employeeJobs']);
            Route::get('jobs-summary', [ReportController::class, 'jobsSummary']);
            Route::get('revenue', [ReportController::class, 'revenue']);
            Route::get('profit-loss', [ReportController::class, 'profitLoss']);
        });

        // Exports
        Route::prefix('export')->group(function () {
            Route::get('invoices/{invoice}/pdf', [ExportController::class, 'invoicePdf']);
            Route::get('invoices/{invoice}/pdf/preview', [ExportController::class, 'invoicePdfPreview']);
            Route::get('invoices/excel', [ExportController::class, 'invoicesExcel']);
            Route::get('customers/excel', [ExportController::class, 'customersExcel']);
            Route::get('jobs/excel', [ExportController::class, 'jobsExcel']);
            Route::get('payments/excel', [ExportController::class, 'paymentsExcel']);
            Route::get('casuals/excel', [ExportController::class, 'casualsExcel']);
            Route::get('casuals-report/excel', [ExportController::class, 'casualsReportExcel']);
            Route::get('casuals/import-template', [ExportController::class, 'casualsImportTemplate']);
        });

        // Imports
        Route::prefix('import')->group(function () {
            Route::post('casuals', [ExportController::class, 'importCasuals']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Accounting - View (Finance: owner, admin, finance)
    | Only Cost Centres active for now
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin,finance')->group(function () {
        // Cost Centres - View
        Route::get('cost-centres', [CostCentreController::class, 'index']);
        Route::get('cost-centres/{costCentre}', [CostCentreController::class, 'show']);

        // Commented out - future features
        // // Chart of Accounts - View
        // Route::get('chart-of-accounts', [AccountController::class, 'index']);
        // Route::get('chart-of-accounts/{account}', [AccountController::class, 'show']);

        // // Journal Entries - View
        // Route::get('journal-entries', [JournalEntryController::class, 'index']);
        // Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show']);

        // // Bank Accounts - View
        // Route::get('bank-accounts', [BankAccountController::class, 'index']);
        // Route::get('bank-accounts/{bankAccount}', [BankAccountController::class, 'show']);
        // Route::get('bank-accounts/recent-transactions', [BankAccountController::class, 'recentTransactions']);
    });

    /*
    |--------------------------------------------------------------------------
    | Accounting - Manage (Admin Only: owner, admin)
    | Only Cost Centres active for now
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:owner,admin')->group(function () {
        // Cost Centres - Manage
        Route::post('cost-centres', [CostCentreController::class, 'store']);
        Route::put('cost-centres/{costCentre}', [CostCentreController::class, 'update']);
        Route::delete('cost-centres/{costCentre}', [CostCentreController::class, 'destroy']);

        // Commented out - future features
        // // Chart of Accounts - Manage
        // Route::post('chart-of-accounts', [AccountController::class, 'store']);
        // Route::put('chart-of-accounts/{account}', [AccountController::class, 'update']);
        // Route::delete('chart-of-accounts/{account}', [AccountController::class, 'destroy']);

        // // Journal Entries - Manage
        // Route::post('journal-entries', [JournalEntryController::class, 'store']);
        // Route::put('journal-entries/{journalEntry}', [JournalEntryController::class, 'update']);
        // Route::delete('journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy']);
        // Route::post('journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post']);

        // // Bank Accounts - Manage
        // Route::post('bank-accounts', [BankAccountController::class, 'store']);
        // Route::put('bank-accounts/{bankAccount}', [BankAccountController::class, 'update']);
        // Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy']);
    });
});

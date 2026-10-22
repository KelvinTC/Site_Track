<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Site Jobs: job_number unique per company
        Schema::table('site_jobs', function (Blueprint $table) {
            $table->unique(['company_id', 'job_number'], 'site_jobs_company_job_number_unique');
        });

        // Invoices: invoice_number unique per company
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['company_id', 'invoice_number'], 'invoices_company_invoice_number_unique');
        });

        // Casuals: casual_number unique per company
        Schema::table('casuals', function (Blueprint $table) {
            $table->unique(['company_id', 'casual_number'], 'casuals_company_casual_number_unique');
        });

        // Expenses: expense_number unique per company
        Schema::table('expenses', function (Blueprint $table) {
            $table->unique(['company_id', 'expense_number'], 'expenses_company_expense_number_unique');
        });

        // Departments: name unique per company
        Schema::table('departments', function (Blueprint $table) {
            $table->unique(['company_id', 'name'], 'departments_company_name_unique');
        });

        // Accounts: code unique per company
        Schema::table('accounts', function (Blueprint $table) {
            $table->unique(['company_id', 'code'], 'accounts_company_code_unique');
        });

        // Cost Centres: code unique per company
        Schema::table('cost_centres', function (Blueprint $table) {
            $table->unique(['company_id', 'code'], 'cost_centres_company_code_unique');
        });

        // Expense Categories: code unique per company
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unique(['company_id', 'code'], 'expense_categories_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('site_jobs', function (Blueprint $table) {
            $table->dropUnique('site_jobs_company_job_number_unique');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_company_invoice_number_unique');
        });

        Schema::table('casuals', function (Blueprint $table) {
            $table->dropUnique('casuals_company_casual_number_unique');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_company_expense_number_unique');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_company_name_unique');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique('accounts_company_code_unique');
        });

        Schema::table('cost_centres', function (Blueprint $table) {
            $table->dropUnique('cost_centres_company_code_unique');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique('expense_categories_company_code_unique');
        });
    }
};

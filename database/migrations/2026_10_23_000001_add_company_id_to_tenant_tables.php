<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'customers', 'site_jobs', 'invoices', 'invoice_items', 'payments',
            'casuals', 'departments', 'expenses', 'expense_categories', 'cost_centres',
            'accounts', 'journal_entries', 'journal_entry_lines', 'bank_accounts', 'bank_transactions'
        ];

        foreach ($tables as $table) {
            // cost_centres may already have company_id from an earlier fix migration
            if (Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'customers', 'site_jobs', 'invoices', 'invoice_items', 'payments',
            'casuals', 'departments', 'expenses', 'expense_categories', 'cost_centres',
            'accounts', 'journal_entries', 'journal_entry_lines', 'bank_accounts', 'bank_transactions'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};

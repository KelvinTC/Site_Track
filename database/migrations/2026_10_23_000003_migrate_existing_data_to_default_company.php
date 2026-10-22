<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get or create default company from CompanyProfile
        $profile = DB::table('company_profiles')->first();

        $companyData = [
            'name' => $profile->company_name ?? 'Default Company',
            'slug' => 'default-company',
            'email' => $profile->email ?? null,
            'phone' => $profile->phone ?? null,
            'website' => $profile->website ?? null,
            'address' => $profile->address ?? null,
            'city' => $profile->city ?? null,
            'state' => $profile->state ?? null,
            'zip' => $profile->zip ?? null,
            'tax_id' => $profile->tax_id ?? null,
            'vat_number' => $profile->vat_number ?? null,
            'vat_rate' => $profile->vat_rate ?? 0,
            'vat_registered' => $profile->vat_registered ?? false,
            'logo_path' => $profile->logo_path ?? null,
            'invoice_notes' => $profile->invoice_notes ?? null,
            'invoice_footer' => $profile->invoice_footer ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Check if company already exists
        $existingCompany = DB::table('companies')->where('slug', 'default-company')->first();

        if ($existingCompany) {
            $companyId = $existingCompany->id;
        } else {
            $companyId = DB::table('companies')->insertGetId($companyData);
        }

        // Assign all existing users to the default company as owners
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            // Check if user already has company assignment
            $exists = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                DB::table('company_user')->insert([
                    'company_id' => $companyId,
                    'user_id' => $user->id,
                    'role' => 'owner',
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Set current_company_id if not set
            if (empty($user->current_company_id)) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['current_company_id' => $companyId]);
            }
        }

        // Update all tenant tables with the default company_id
        $tables = [
            'customers',
            'site_jobs',
            'invoices',
            'invoice_items',
            'payments',
            'casuals',
            'departments',
            'expenses',
            'expense_categories',
            'cost_centres',
            'accounts',
            'journal_entries',
            'journal_entry_lines',
            'bank_accounts',
            'bank_transactions',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        // Now make company_id NOT NULL on all tables
        $this->makeCompanyIdRequired();
    }

    protected function makeCompanyIdRequired(): void
    {
        $tables = [
            'customers',
            'site_jobs',
            'invoices',
            'invoice_items',
            'payments',
            'casuals',
            'departments',
            'expenses',
            'expense_categories',
            'cost_centres',
            'accounts',
            'journal_entries',
            'journal_entry_lines',
            'bank_accounts',
            'bank_transactions',
        ];

        foreach ($tables as $tableName) {
            // Use raw SQL to modify the column to NOT NULL
            // This works for both MySQL and PostgreSQL
            DB::statement("ALTER TABLE {$tableName} ALTER COLUMN company_id SET NOT NULL");
        }
    }

    public function down(): void
    {
        // Get the default company
        $company = DB::table('companies')->where('slug', 'default-company')->first();

        if ($company) {
            // Remove user associations
            DB::table('company_user')->where('company_id', $company->id)->delete();

            // Reset current_company_id for users
            DB::table('users')
                ->where('current_company_id', $company->id)
                ->update(['current_company_id' => null]);

            // Delete the default company
            DB::table('companies')->where('id', $company->id)->delete();
        }
    }
};

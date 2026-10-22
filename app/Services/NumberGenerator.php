<?php

namespace App\Services;

use App\Models\SiteJob;
use App\Models\Invoice;
use App\Models\Casual;
use App\Models\Expense;
use App\Models\Company;

class NumberGenerator
{
    public static function generateJobNumber(?int $companyId = null): string
    {
        $companyId = $companyId ?? auth()->user()?->current_company_id;
        $company = $companyId ? Company::find($companyId) : null;
        $prefix = $company?->job_prefix ?? 'JOB';

        $year = now()->year;
        $count = SiteJob::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }

    public static function generateInvoiceNumber(?int $companyId = null): string
    {
        $companyId = $companyId ?? auth()->user()?->current_company_id;
        $company = $companyId ? Company::find($companyId) : null;
        $prefix = $company?->invoice_prefix ?? 'INV';

        $year = now()->year;
        $count = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }

    public static function generateCasualNumber(?int $companyId = null): string
    {
        $companyId = $companyId ?? auth()->user()?->current_company_id;
        $company = $companyId ? Company::find($companyId) : null;
        $prefix = $company?->employee_prefix ?? 'EMP';

        $year = now()->year;
        $count = Casual::withoutGlobalScopes()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }

    public static function generateExpenseNumber(?int $companyId = null): string
    {
        $companyId = $companyId ?? auth()->user()?->current_company_id;
        $company = $companyId ? Company::find($companyId) : null;
        $prefix = $company?->expense_prefix ?? 'EXP';

        $year = now()->year;
        $count = Expense::withoutGlobalScopes()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }
}

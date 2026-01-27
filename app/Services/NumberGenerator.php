<?php

namespace App\Services;

use App\Models\SiteJob;
use App\Models\Invoice;

class NumberGenerator
{
    public static function generateJobNumber(): string
    {
        $year = now()->year;
        $count = SiteJob::whereYear('created_at', $year)->count() + 1;

        return sprintf('JOB-%d-%04d', $year, $count);
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $count = Invoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%d-%04d', $year, $count);
    }
}

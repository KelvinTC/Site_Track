<?php

namespace App\Http\Controllers;

use App\Models\SiteJob;
use App\Models\Invoice;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'activeJobs' => SiteJob::whereIn('status', ['new', 'in_progress'])->count(),
            'unpaidInvoices' => Invoice::whereIn('status', ['unpaid', 'partial'])->count(),
            'totalRevenue' => Invoice::where('status', 'paid')->sum('total'),
            'outstandingBalance' => Invoice::whereIn('status', ['unpaid', 'partial'])
                ->get()
                ->sum(function ($invoice) {
                    return $invoice->balance;
                }),
        ];

        $recentJobs = SiteJob::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentJobs' => $recentJobs,
        ]);
    }
}

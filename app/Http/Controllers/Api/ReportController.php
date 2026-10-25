<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\ArrayExport;
use App\Models\Casual;
use App\Models\Department;
use App\Models\SiteJob;
use App\Models\Invoice;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected const CHART_COLORS = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#64748b'];

    /*
    |--------------------------------------------------------------------------
    | Employee Jobs
    |--------------------------------------------------------------------------
    */

    public function employeeJobs(Request $request)
    {
        return response()->json($this->buildEmployeeJobs(...$this->dateRange($request, 'month')));
    }

    public function employeeJobsExport(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request, 'month');
        $report = $this->buildEmployeeJobs($startDate, $endDate);

        $rows = [];
        foreach ($report['employees'] as $employee) {
            foreach ($employee['jobs'] as $job) {
                $rows[] = [
                    $employee['employee_number'],
                    $employee['name'],
                    $employee['department'],
                    $job['job_number'],
                    $job['client_name'],
                    $job['description'],
                    $job['date'],
                    $job['notes'],
                ];
            }
        }

        return $this->download(
            ['Employee #', 'Employee', 'Department', 'Job #', 'Client', 'Job Title', 'Work Date', 'Notes'],
            $rows,
            'Employee Jobs',
            "employee-jobs-{$startDate}-to-{$endDate}",
            $request->input('format')
        );
    }

    protected function buildEmployeeJobs(string $startDate, string $endDate): array
    {
        // An assignment counts on its work date, or its assigned date when no work date is set
        $inRange = function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('casual_site_job.work_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('casual_site_job.work_date')
                            ->whereBetween('casual_site_job.assigned_date', [$startDate, $endDate]);
                    });
            });
        };

        $casuals = Casual::with(['department', 'siteJobs' => function ($query) use ($inRange) {
                $inRange($query);
                $query->with('customer');
            }])
            ->whereHas('siteJobs', $inRange)
            ->orderBy('name')
            ->get();

        // Casuals have no pay rate or hours stored, so hours/rate/amount are reported as zero
        $employees = $casuals->map(function ($casual) {
            $jobs = $casual->siteJobs
                ->sortBy(fn ($job) => $job->pivot->work_date ?? $job->pivot->assigned_date)
                ->map(fn ($job) => [
                    'id' => $job->pivot->id ?? $job->id . '-' . ($job->pivot->work_date ?? $job->pivot->assigned_date),
                    'job_number' => $job->job_number,
                    'client_name' => $job->customer?->name ?? 'No Client',
                    'description' => $job->title,
                    'date' => $job->pivot->work_date ?? $job->pivot->assigned_date,
                    'notes' => $job->pivot->notes,
                    'hours' => 0,
                    'rate' => 0,
                    'amount' => 0,
                ])
                ->values();

            return [
                'id' => $casual->id,
                'employee_number' => $casual->casual_number,
                'name' => $casual->name,
                'department_id' => $casual->department_id,
                'department' => $casual->department?->name ?? '',
                'jobs' => $jobs,
                'total_hours' => 0,
                'total_earnings' => 0,
            ];
        })->values();

        return [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'employee_list' => $casuals->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'summary' => [
                'total_employees' => $employees->count(),
                'total_jobs' => $employees->sum(fn ($e) => count($e['jobs'])),
            ],
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Jobs Summary
    |--------------------------------------------------------------------------
    */

    public function jobsSummary(Request $request)
    {
        return response()->json($this->buildJobsSummary(...$this->dateRange($request, 'month')));
    }

    public function jobsSummaryExport(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request, 'month');
        $report = $this->buildJobsSummary($startDate, $endDate);

        $rows = collect($report['jobs'])->map(fn ($job) => [
            $job['job_number'],
            $job['client_name'],
            $job['title'],
            $job['start_date'],
            $job['end_date'],
            ucwords(str_replace('_', ' ', $job['status'] ?? '')),
            $job['worker_count'],
            (float) $job['value'],
            (float) $job['actual_cost'],
            $job['invoiced'] ? 'Yes' : 'No',
        ])->all();

        return $this->download(
            ['Job #', 'Client', 'Title', 'Start Date', 'End Date', 'Status', 'Workers', 'Estimated Cost', 'Actual Cost', 'Invoiced'],
            $rows,
            'Jobs Summary',
            "jobs-summary-{$startDate}-to-{$endDate}",
            $request->input('format')
        );
    }

    protected function buildJobsSummary(string $startDate, string $endDate): array
    {
        $jobs = SiteJob::with(['customer', 'casuals', 'invoices'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->orderBy('start_date', 'desc')
            ->get();

        // Format jobs for frontend
        $formattedJobs = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'client_id' => $job->customer_id,
                'client_name' => $job->customer?->name ?? 'No Client',
                'title' => $job->title,
                'description' => $job->description,
                'date' => $job->start_date?->format('Y-m-d'),
                'start_date' => $job->start_date?->format('Y-m-d'),
                'end_date' => $job->end_date?->format('Y-m-d'),
                'status' => $job->status,
                'worker_count' => $job->casuals->count(),
                'value' => $job->estimated_cost ?? 0,
                'actual_cost' => $job->actual_cost ?? 0,
                'invoiced' => $job->invoices->count() > 0,
            ];
        });

        // Get unique clients for filter dropdown
        $clients = $jobs->map(fn($job) => $job->customer)
            ->filter()
            ->unique('id')
            ->map(fn($customer) => ['id' => $customer->id, 'name' => $customer->name])
            ->values();

        return [
            'jobs' => $formattedJobs,
            'clients' => $clients,
            'summary' => [
                'total_jobs' => $jobs->count(),
                'total_estimated' => $jobs->sum('estimated_cost'),
                'total_actual' => $jobs->sum('actual_cost'),
                'completed' => $jobs->where('status', 'completed')->count(),
                'in_progress' => $jobs->where('status', 'in_progress')->count(),
                'pending' => $jobs->where('status', 'pending')->count(),
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue
    |--------------------------------------------------------------------------
    */

    public function revenue(Request $request)
    {
        return response()->json($this->buildRevenue(...$this->dateRange($request, 'year')));
    }

    public function revenueExport(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request, 'year');
        $report = $this->buildRevenue($startDate, $endDate);

        $rows = collect($report['by_client'])->map(fn ($client) => [
            $client['name'],
            $client['invoice_count'],
            $client['revenue'],
            $client['collected'],
            $client['outstanding'],
            round($client['share'], 1) . '%',
        ])->all();

        $summary = $report['summary'];
        $rows[] = [];
        $rows[] = ['Total', $summary['invoice_count'], $summary['total_revenue'], $summary['collected'], $summary['outstanding'], '100%'];
        $rows[] = [];
        $rows[] = ['Month', 'Revenue'];
        foreach ($report['monthly_trend'] as $month) {
            $rows[] = [$month['label'], $month['revenue']];
        }

        return $this->download(
            ['Client', 'Invoices', 'Revenue', 'Collected', 'Outstanding', 'Share'],
            $rows,
            'Revenue',
            "revenue-{$startDate}-to-{$endDate}",
            $request->input('format')
        );
    }

    protected function buildRevenue(string $startDate, string $endDate): array
    {
        $invoices = Invoice::with(['customer', 'payments'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get();

        $totalRevenue = (float) $invoices->sum('total');
        $collected = (float) $invoices->sum(fn ($invoice) => $invoice->payments->sum('amount'));

        // Compare with the previous period of the same length
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;
        $previousRevenue = (float) Invoice::whereBetween('issue_date', [
            $start->copy()->subDays($days)->format('Y-m-d'),
            $start->copy()->subDay()->format('Y-m-d'),
        ])->sum('total');

        $byClient = $invoices->groupBy('customer_id')->map(function ($group) use ($totalRevenue) {
            $revenue = (float) $group->sum('total');
            $paid = (float) $group->sum(fn ($invoice) => $invoice->payments->sum('amount'));

            return [
                'id' => $group->first()->customer_id,
                'name' => $group->first()->customer?->name ?? 'No Client',
                'invoice_count' => $group->count(),
                'revenue' => $revenue,
                'collected' => $paid,
                'outstanding' => $revenue - $paid,
                'share' => $totalRevenue > 0 ? $revenue / $totalRevenue * 100 : 0,
            ];
        })->sortByDesc('revenue')->values();

        // One bar per month in the range
        $byMonth = $invoices->groupBy(fn ($invoice) => $invoice->issue_date->format('Y-m'));
        $labelFormat = $start->year === $end->year ? 'M' : 'M y';
        $monthlyTrend = [];
        for ($month = $start->copy()->startOfMonth(); $month <= $end; $month->addMonth()) {
            $monthlyTrend[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format($labelFormat),
                'revenue' => (float) ($byMonth->get($month->format('Y-m'))?->sum('total') ?? 0),
            ];
        }

        return [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'collected' => $collected,
                'outstanding' => $totalRevenue - $collected,
                'invoice_count' => $invoices->count(),
                'outstanding_count' => $invoices->where('status', '!=', 'paid')->count(),
                'collection_rate' => $totalRevenue > 0 ? $collected / $totalRevenue * 100 : 0,
                'change' => $previousRevenue > 0 ? ($totalRevenue - $previousRevenue) / $previousRevenue * 100 : 0,
            ],
            'by_client' => $byClient,
            'monthly_trend' => $monthlyTrend,
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Profit & Loss
    |--------------------------------------------------------------------------
    */

    public function profitLoss(Request $request)
    {
        return response()->json($this->buildProfitLoss(...$this->dateRange($request, 'month')));
    }

    public function profitLossExport(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request, 'month');
        $report = $this->buildProfitLoss($startDate, $endDate);

        $rows = [];
        foreach ($report['revenue_items'] as $item) {
            $rows[] = ['Revenue', $item['name'], $item['amount']];
        }
        $rows[] = ['', 'Total Revenue', $report['total_revenue']];
        foreach ($report['cost_of_sales'] as $item) {
            $rows[] = ['Cost of Sales', $item['name'], $item['amount']];
        }
        $rows[] = ['', 'Total Cost of Sales', $report['total_cost_of_sales']];
        $rows[] = ['', 'Gross Profit', $report['gross_profit']];
        foreach ($report['operating_expenses'] as $item) {
            $rows[] = ['Operating Expenses', $item['name'], $item['amount']];
        }
        $rows[] = ['', 'Total Operating Expenses', $report['total_operating_expenses']];
        $rows[] = ['', 'Net Profit', $report['net_profit']];
        $rows[] = ['', 'Profit Margin', round($report['profit_margin'], 1) . '%'];

        return $this->download(
            ['Section', 'Item', 'Amount'],
            $rows,
            'Profit & Loss',
            "profit-loss-{$startDate}-to-{$endDate}",
            $request->input('format')
        );
    }

    protected function buildProfitLoss(string $startDate, string $endDate): array
    {
        // Revenue excludes VAT
        $revenue = (float) Invoice::whereBetween('issue_date', [$startDate, $endDate])->sum('subtotal');

        $expenses = Expense::with('expenseCategory')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'paid'])
            ->get();

        $byCategory = fn ($items) => $items
            ->groupBy(fn ($expense) => $expense->expenseCategory?->name ?? 'Uncategorised')
            ->map(fn ($group, $name) => ['name' => $name, 'amount' => (float) $group->sum('amount')])
            ->sortByDesc('amount')
            ->values();

        // Expenses tied to a site job are direct costs; the rest are overheads
        [$jobExpenses, $overheads] = $expenses->partition(fn ($expense) => $expense->site_job_id !== null);

        $costOfSales = $byCategory($jobExpenses);
        $operatingExpenses = $byCategory($overheads);
        $totalCostOfSales = (float) $costOfSales->sum('amount');
        $totalOperating = (float) $operatingExpenses->sum('amount');
        $totalExpenses = $totalCostOfSales + $totalOperating;
        $grossProfit = $revenue - $totalCostOfSales;
        $netProfit = $grossProfit - $totalOperating;

        $breakdown = $byCategory($expenses)->map(fn ($category, $i) => $category + [
            'percentage' => $totalExpenses > 0 ? $category['amount'] / $totalExpenses * 100 : 0,
            'color' => self::CHART_COLORS[$i % count(self::CHART_COLORS)],
        ]);

        return [
            'total_revenue' => $revenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $revenue > 0 ? $netProfit / $revenue * 100 : 0,
            'revenue_items' => [['name' => 'Sales (excl. VAT)', 'amount' => $revenue]],
            'cost_of_sales' => $costOfSales,
            'total_cost_of_sales' => $totalCostOfSales,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $operatingExpenses,
            'total_operating_expenses' => $totalOperating,
            'expense_breakdown' => $breakdown,
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve the report date range from a "period" (month, quarter, year, custom)
     * or explicit start_date/end_date.
     */
    protected function dateRange(Request $request, string $defaultPeriod): array
    {
        $period = $request->input('period');

        if (($period === null || $period === 'custom') && $request->filled('start_date') && $request->filled('end_date')) {
            return [$request->input('start_date'), $request->input('end_date')];
        }

        $now = now();

        return match ($period ?: $defaultPeriod) {
            'quarter' => [$now->copy()->firstOfQuarter()->format('Y-m-d'), $now->copy()->lastOfQuarter()->format('Y-m-d')],
            'year' => [$now->copy()->startOfYear()->format('Y-m-d'), $now->copy()->endOfYear()->format('Y-m-d')],
            default => [$now->copy()->startOfMonth()->format('Y-m-d'), $now->copy()->endOfMonth()->format('Y-m-d')],
        };
    }

    protected function download(array $headings, array $rows, string $title, string $filename, ?string $format)
    {
        $type = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        return Excel::download(new ArrayExport($headings, $rows, $title), "{$filename}.{$extension}", $type);
    }
}

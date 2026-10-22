<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteJob;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function employeeJobs(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $jobs = DB::table('casual_site_job')
            ->join('casuals', 'casual_site_job.casual_id', '=', 'casuals.id')
            ->join('site_jobs', 'casual_site_job.site_job_id', '=', 'site_jobs.id')
            ->join('customers', 'site_jobs.customer_id', '=', 'customers.id')
            ->whereBetween('casual_site_job.work_date', [$startDate, $endDate])
            ->select(
                'casuals.id as employee_id',
                'casuals.name as employee_name',
                'casuals.employee_number',
                DB::raw('COUNT(DISTINCT site_jobs.id) as total_jobs'),
                DB::raw('COUNT(*) as total_days'),
                DB::raw('SUM(casuals.daily_rate) as total_earnings')
            )
            ->groupBy('casuals.id', 'casuals.name', 'casuals.employee_number')
            ->orderBy('casuals.name')
            ->get();

        return response()->json([
            'data' => $jobs,
            'summary' => [
                'total_employees' => $jobs->count(),
                'total_job_days' => $jobs->sum('total_days'),
                'total_earnings' => $jobs->sum('total_earnings'),
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function jobsSummary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

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

        return response()->json([
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
        ]);
    }

    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $invoices = Invoice::whereBetween('date', [$startDate, $endDate])->get();
        $payments = Payment::whereBetween('date', [$startDate, $endDate])->get();

        // Group by month
        $monthlyRevenue = $invoices->groupBy(function ($inv) {
            return $inv->date->format('Y-m');
        })->map(function ($group) {
            return [
                'invoiced' => $group->sum('total'),
                'count' => $group->count(),
            ];
        });

        $monthlyPayments = $payments->groupBy(function ($pay) {
            return $pay->date->format('Y-m');
        })->map(function ($group) {
            return $group->sum('amount');
        });

        return response()->json([
            'data' => [
                'monthly_invoiced' => $monthlyRevenue,
                'monthly_payments' => $monthlyPayments,
            ],
            'summary' => [
                'total_invoiced' => $invoices->sum('total'),
                'total_collected' => $payments->sum('amount'),
                'outstanding' => $invoices->sum('total') - $payments->sum('amount'),
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function profitLoss(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Revenue from invoices
        $revenue = Invoice::whereBetween('date', [$startDate, $endDate])
            ->sum('total');

        // Expenses
        $expenses = Expense::with('expenseCategory')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->get();

        $expensesByCategory = $expenses->groupBy('expenseCategory.name')->map(function ($group) {
            return $group->sum('amount');
        });

        $totalExpenses = $expenses->sum('amount');

        // Labor costs (casual workers)
        $laborCosts = DB::table('casual_site_job')
            ->join('casuals', 'casual_site_job.casual_id', '=', 'casuals.id')
            ->whereBetween('casual_site_job.work_date', [$startDate, $endDate])
            ->sum('casuals.daily_rate');

        return response()->json([
            'data' => [
                'revenue' => $revenue,
                'expenses' => [
                    'by_category' => $expensesByCategory,
                    'total' => $totalExpenses,
                ],
                'labor_costs' => $laborCosts,
            ],
            'summary' => [
                'gross_revenue' => $revenue,
                'total_expenses' => $totalExpenses,
                'labor_costs' => $laborCosts,
                'net_profit' => $revenue - $totalExpenses - $laborCosts,
                'profit_margin' => $revenue > 0 ? round((($revenue - $totalExpenses - $laborCosts) / $revenue) * 100, 2) : 0,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Casual;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SiteJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected SiteJob $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole('finance');

        $this->customer = Customer::create(['name' => 'Acme Mining']);
        $this->job = SiteJob::create(['customer_id' => $this->customer->id, 'title' => 'Trenching', 'start_date' => '2026-03-01', 'end_date' => '2026-03-20']);

        // 2025: one older invoice (opening balance / previous period)
        $this->invoice('2025-12-01', 500, 0);
        // 2026: invoice paid in full, and invoice part-paid
        $paid = $this->invoice('2026-03-10', 1000, 150);
        $partial = $this->invoice('2026-10-05', 2000, 300);
        Payment::create(['invoice_id' => $paid->id, 'amount' => 1150, 'payment_date' => '2026-04-01', 'payment_method' => 'bank_transfer']);
        Payment::create(['invoice_id' => $partial->id, 'amount' => 800, 'payment_date' => '2026-10-20', 'payment_method' => 'cash']);

        // October 2026 expenses: job cost, overhead, and a pending one that must be ignored
        $materials = ExpenseCategory::create(['name' => 'Materials', 'code' => 'MAT']);
        $rent = ExpenseCategory::create(['name' => 'Rent', 'code' => 'RNT']);
        Expense::create(['expense_category_id' => $materials->id, 'site_job_id' => $this->job->id, 'expense_date' => '2026-10-10', 'description' => 'Cement', 'amount' => 300, 'status' => 'approved', 'payment_method' => 'cash']);
        Expense::create(['expense_category_id' => $rent->id, 'expense_date' => '2026-10-11', 'description' => 'Office', 'amount' => 200, 'status' => 'paid', 'payment_method' => 'cash']);
        Expense::create(['expense_category_id' => $rent->id, 'expense_date' => '2026-10-12', 'description' => 'Pending', 'amount' => 999, 'status' => 'pending', 'payment_method' => 'cash']);
    }

    protected function invoice(string $date, float $subtotal, float $vat): Invoice
    {
        return Invoice::create([
            'customer_id' => $this->customer->id,
            'site_job_id' => $this->job->id,
            'issue_date' => $date,
            'subtotal' => $subtotal,
            'vat_amount' => $vat,
            'tax' => $vat,
            'total' => $subtotal + $vat,
            'status' => 'unpaid',
        ]);
    }

    public function test_revenue_report(): void
    {
        $response = $this->getJson('/api/reports/revenue?period=custom&start_date=2026-01-01&end_date=2026-12-31')->assertOk();

        $response->assertJsonPath('summary.total_revenue', 3450)
            ->assertJsonPath('summary.collected', 1950)
            ->assertJsonPath('summary.outstanding', 1500)
            ->assertJsonPath('summary.invoice_count', 2)
            ->assertJsonPath('summary.outstanding_count', 1)
            // 2025 had 500 invoiced: (3450 - 500) / 500
            ->assertJsonPath('summary.change', 590)
            ->assertJsonPath('by_client.0.name', 'Acme Mining')
            ->assertJsonPath('monthly_trend.2.revenue', 1150)
            ->assertJsonPath('monthly_trend.9.revenue', 2300);
    }

    public function test_profit_and_loss_report(): void
    {
        $this->getJson('/api/reports/profit-loss?period=custom&start_date=2026-10-01&end_date=2026-10-31')
            ->assertOk()
            // Revenue excludes VAT; the pending expense is ignored
            ->assertJsonPath('total_revenue', 2000)
            ->assertJsonPath('total_cost_of_sales', 300)
            ->assertJsonPath('gross_profit', 1700)
            ->assertJsonPath('total_operating_expenses', 200)
            ->assertJsonPath('net_profit', 1500)
            ->assertJsonPath('profit_margin', 75)
            ->assertJsonPath('expense_breakdown.0.name', 'Materials')
            ->assertJsonPath('expense_breakdown.0.percentage', 60);
    }

    public function test_employee_jobs_report_filters_by_work_date(): void
    {
        $worker = Casual::create(['name' => 'Tendai', 'department_id' => Department::create(['name' => 'Site'])->id, 'is_active' => true]);
        $worker->siteJobs()->attach($this->job->id, ['assigned_date' => '2026-10-01', 'work_date' => '2026-10-26']);
        // No work date: falls back to the assigned date
        $worker->siteJobs()->attach($this->job->id, ['assigned_date' => '2026-02-01', 'work_date' => null]);

        $this->getJson('/api/reports/employee-jobs?start_date=2026-01-01&end_date=2026-12-31')
            ->assertOk()
            ->assertJsonPath('employees.0.name', 'Tendai')
            ->assertJsonPath('employees.0.department', 'Site')
            ->assertJsonCount(2, 'employees.0.jobs');

        $this->getJson('/api/reports/employee-jobs?start_date=2026-10-01&end_date=2026-10-31')
            ->assertJsonCount(1, 'employees.0.jobs');
    }

    public function test_jobs_summary_report(): void
    {
        $this->getJson('/api/reports/jobs-summary?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('jobs.0.title', 'Trenching')
            ->assertJsonPath('jobs.0.invoiced', true);
    }

    public function test_customer_statement_balances(): void
    {
        $this->getJson("/api/customers/{$this->customer->id}/statement?from_date=2026-01-01&to_date=2026-12-31")
            ->assertOk()
            ->assertJsonPath('opening_balance', 500)
            ->assertJsonPath('total_invoiced', 3450)
            ->assertJsonPath('total_paid', 1950)
            ->assertJsonPath('closing_balance', 2000);
    }
}

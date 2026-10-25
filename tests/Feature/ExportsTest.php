<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SiteJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExportsTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole('owner');

        $this->customer = Customer::create(['name' => 'Acme Mining']);
        $job = SiteJob::create(['customer_id' => $this->customer->id, 'title' => 'Trenching']);
        $this->invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'site_job_id' => $job->id,
            'issue_date' => '2026-10-01',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'unpaid',
        ]);
    }

    public static function spreadsheetExports(): array
    {
        return [
            'invoices' => ['/api/export/invoices/excel', 'xlsx'],
            'customers' => ['/api/export/customers/excel', 'xlsx'],
            'jobs' => ['/api/export/jobs/excel', 'xlsx'],
            'payments' => ['/api/export/payments/excel', 'xlsx'],
            'casuals' => ['/api/export/casuals/excel', 'xlsx'],
            'casuals report' => ['/api/export/casuals-report/excel', 'xlsx'],
            'import template' => ['/api/export/casuals/import-template', 'xlsx'],
            'employee jobs' => ['/api/reports/employee-jobs/export?format=excel', 'xlsx'],
            'employee jobs csv' => ['/api/reports/employee-jobs/export?format=csv', 'csv'],
            'jobs summary' => ['/api/reports/jobs-summary/export?format=excel', 'xlsx'],
            'revenue' => ['/api/reports/revenue/export?period=year', 'xlsx'],
            'profit and loss' => ['/api/reports/profit-loss/export?period=month', 'xlsx'],
        ];
    }

    #[DataProvider('spreadsheetExports')]
    public function test_spreadsheet_exports_download(string $url, string $extension): void
    {
        $response = $this->get($url)->assertOk();

        $this->assertStringContainsString(".{$extension}", $response->headers->get('content-disposition'));
    }

    public function test_customer_statement_exports(): void
    {
        foreach (['excel' => 'xlsx', 'csv' => 'csv', 'pdf' => 'pdf'] as $format => $extension) {
            $response = $this->get("/api/export/customers/{$this->customer->id}/statement/{$format}?from_date=2026-01-01&to_date=2026-12-31")
                ->assertOk();

            $this->assertStringContainsString("statement-acme-mining-2026-01-01-to-2026-12-31.{$extension}", $response->headers->get('content-disposition'));
        }
    }

    public function test_invoice_pdf_downloads(): void
    {
        $response = $this->get("/api/export/invoices/{$this->invoice->id}/pdf")->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}

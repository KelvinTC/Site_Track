<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\CompanyProfile;
use App\Exports\InvoicesExport;
use App\Exports\CustomersExport;
use App\Exports\JobsExport;
use App\Exports\PaymentsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Download invoice as PDF
     */
    public function invoicePdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'siteJob', 'items', 'payments']);
        $company = CompanyProfile::getProfile();

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Stream invoice PDF (for preview)
     */
    public function invoicePdfPreview(Invoice $invoice)
    {
        $invoice->load(['customer', 'siteJob', 'items', 'payments']);
        $company = CompanyProfile::getProfile();

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Export invoices to Excel
     */
    public function invoicesExcel()
    {
        return Excel::download(new InvoicesExport, 'invoices-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export customers to Excel
     */
    public function customersExcel()
    {
        return Excel::download(new CustomersExport, 'customers-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export jobs to Excel
     */
    public function jobsExcel()
    {
        return Excel::download(new JobsExport, 'jobs-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export payments to Excel
     */
    public function paymentsExcel()
    {
        return Excel::download(new PaymentsExport, 'payments-' . date('Y-m-d') . '.xlsx');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\CompanyProfile;
use App\Exports\InvoicesExport;
use App\Exports\CustomersExport;
use App\Exports\JobsExport;
use App\Exports\PaymentsExport;
use App\Exports\CasualsExport;
use App\Exports\CasualsJobsReportExport;
use App\Imports\CasualsImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

    /**
     * Export casuals to Excel
     */
    public function casualsExcel()
    {
        return Excel::download(new CasualsExport, 'casuals-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export casuals jobs report to Excel
     */
    public function casualsReportExcel(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        return Excel::download(
            new CasualsJobsReportExport($fromDate, $toDate),
            'casuals-jobs-report-' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Import casuals from Excel
     */
    public function importCasuals(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new CasualsImport();
        $import->import($request->file('file'));

        $failures = $import->failures();
        $errors = [];

        foreach ($failures as $failure) {
            $errors[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }

        return response()->json([
            'success' => true,
            'imported' => $import->getRowsImported() - count($failures),
            'failed' => count($failures),
            'errors' => $errors,
        ]);
    }
}

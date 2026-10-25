<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SiteJob;
use App\Models\CompanyProfile;
use App\Mail\InvoiceMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer', 'siteJob', 'items', 'payments']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        return response()->json($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Convert empty strings to null for optional date fields
            $input = $request->all();
            if (isset($input['due_date']) && $input['due_date'] === '') {
                $input['due_date'] = null;
            }

            $validated = validator($input, [
                'site_job_id' => 'required|company_exists:site_jobs,id',
                'issue_date' => 'required|date',
                'due_date' => 'nullable|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.type' => 'required|in:labor,material,other',
                'items.*.quantity' => 'required|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
            ])->validate();

            return DB::transaction(function () use ($validated) {
                $siteJob = SiteJob::findOrFail($validated['site_job_id']);

                // Get VAT rate from company profile
                $companyProfile = CompanyProfile::getProfile();
                $vatRate = $companyProfile->vat_registered ? ($companyProfile->vat_rate ?? 15.00) : 0;

                $subtotal = 0;
                foreach ($validated['items'] as $item) {
                    $subtotal += $item['quantity'] * $item['unit_price'];
                }

                // Calculate VAT
                $vatAmount = round($subtotal * ($vatRate / 100), 2);
                $total = $subtotal + $vatAmount;

                Log::info('Creating invoice with VAT', [
                    'subtotal' => $subtotal,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'total' => $total,
                ]);

                $invoice = Invoice::create([
                    'site_job_id' => $validated['site_job_id'],
                    'customer_id' => $siteJob->customer_id,
                    'issue_date' => $validated['issue_date'],
                    'due_date' => $validated['due_date'] ?? null,
                    'subtotal' => $subtotal,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'tax' => $vatAmount,
                    'total' => $total,
                    'notes' => $validated['notes'] ?? null,
                ]);

            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $invoice->items()->create([
                    'description' => $item['description'],
                    'type' => $item['type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                ]);
            }

            $invoice->load(['customer', 'siteJob', 'items']);

            return response()->json($invoice, 201);
        });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['customer', 'siteJob', 'items', 'payments']);

        $invoice->amount_paid = $invoice->amount_paid;
        $invoice->balance = $invoice->balance;

        return response()->json($invoice);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);
        $invoice->load(['customer', 'siteJob', 'items', 'payments']);

        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    /**
     * Get aging report for unpaid invoices
     */
    public function aging(Request $request): JsonResponse
    {
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $asOf = \Carbon\Carbon::parse($asOfDate);

        $invoices = Invoice::with(['customer', 'siteJob'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($invoice) use ($asOf) {
                $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : null;
                $daysOverdue = $dueDate ? $asOf->diffInDays($dueDate, false) : 0;

                // Determine aging bucket
                if (!$dueDate || $daysOverdue <= 0) {
                    $bucket = 'current';
                    $bucketLabel = 'Current';
                } elseif ($daysOverdue <= 30) {
                    $bucket = '1-30';
                    $bucketLabel = '1-30 Days';
                } elseif ($daysOverdue <= 60) {
                    $bucket = '31-60';
                    $bucketLabel = '31-60 Days';
                } elseif ($daysOverdue <= 90) {
                    $bucket = '61-90';
                    $bucketLabel = '61-90 Days';
                } else {
                    $bucket = '90+';
                    $bucketLabel = '90+ Days';
                }

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer?->name,
                    'site_job_id' => $invoice->site_job_id,
                    'job_number' => $invoice->siteJob?->job_number,
                    'issue_date' => $invoice->issue_date?->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'total' => (float) $invoice->total,
                    'amount_paid' => (float) $invoice->amount_paid,
                    'balance' => (float) $invoice->balance,
                    'days_overdue' => max(0, $daysOverdue),
                    'bucket' => $bucket,
                    'bucket_label' => $bucketLabel,
                    'status' => $invoice->status,
                ];
            });

        // Calculate summary by bucket
        $buckets = ['current', '1-30', '31-60', '61-90', '90+'];
        $summary = [];

        foreach ($buckets as $bucket) {
            $bucketInvoices = $invoices->where('bucket', $bucket);
            $summary[$bucket] = [
                'count' => $bucketInvoices->count(),
                'total' => $bucketInvoices->sum('balance'),
            ];
        }

        // Summary by customer
        $byCustomer = $invoices->groupBy('customer_id')->map(function ($customerInvoices) {
            $first = $customerInvoices->first();
            $bucketTotals = [];
            foreach (['current', '1-30', '31-60', '61-90', '90+'] as $bucket) {
                $bucketTotals[$bucket] = $customerInvoices->where('bucket', $bucket)->sum('balance');
            }

            return [
                'customer_id' => $first['customer_id'],
                'customer_name' => $first['customer_name'],
                'invoice_count' => $customerInvoices->count(),
                'total_balance' => $customerInvoices->sum('balance'),
                'buckets' => $bucketTotals,
            ];
        })->values();

        return response()->json([
            'as_of_date' => $asOfDate,
            'summary' => $summary,
            'total_outstanding' => $invoices->sum('balance'),
            'invoice_count' => $invoices->count(),
            'by_customer' => $byCustomer,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        $invoice->load(['customer', 'siteJob', 'items', 'payments']);
        $company = $request->user()->currentCompany;

        try {
            Mail::to($validated['email'])
                ->send(new InvoiceMail($invoice, $company, $validated['message'] ?? null));

            // Update invoice to track that email was sent
            $invoice->update([
                'last_sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Invoice sent to {$validated['email']}",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again.',
            ], 500);
        }
    }
}

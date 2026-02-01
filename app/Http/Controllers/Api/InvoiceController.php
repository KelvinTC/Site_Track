<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SiteJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
                'site_job_id' => 'required|exists:site_jobs,id',
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

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $invoice = Invoice::create([
                'site_job_id' => $validated['site_job_id'],
                'customer_id' => $siteJob->customer_id,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
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
}

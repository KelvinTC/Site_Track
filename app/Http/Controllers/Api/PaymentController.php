<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['invoice.customer', 'invoice.siteJob']);

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        return response()->json($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,check,bank_transfer,credit_card,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        if ($validated['amount'] > $invoice->balance) {
            return response()->json([
                'message' => 'Payment amount exceeds invoice balance',
                'balance' => $invoice->balance
            ], 422);
        }

        $payment = Payment::create($validated);
        $payment->load('invoice');

        return response()->json($payment, 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['invoice.customer', 'invoice.siteJob']);

        return response()->json($payment);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|in:cash,check,bank_transfer,credit_card,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);
        $payment->load('invoice');

        return response()->json($payment);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        $customers = Customer::with(['siteJobs', 'invoices'])
            ->orderBy('name')
            ->get();

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['siteJobs', 'invoices.payments']);

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function statement(Request $request, Customer $customer): JsonResponse
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Get all invoices for this customer before the from_date to calculate opening balance
        $invoicesBeforePeriod = $customer->invoices()
            ->where('issue_date', '<', $fromDate)
            ->with('payments')
            ->get();

        $openingBalance = 0;
        foreach ($invoicesBeforePeriod as $invoice) {
            $invoiceTotal = (float) $invoice->total;
            $paymentsBefore = $invoice->payments
                ->where('payment_date', '<', $fromDate)
                ->sum('amount');
            $openingBalance += $invoiceTotal - (float) $paymentsBefore;
        }

        // Get invoices in the date range
        $invoicesInPeriod = $customer->invoices()
            ->whereBetween('issue_date', [$fromDate, $toDate])
            ->with(['siteJob', 'payments'])
            ->get();

        // Get payments in the date range (including payments on older invoices)
        $paymentsInPeriod = \App\Models\Payment::whereHas('invoice', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->with('invoice')
            ->get();

        // Build transactions array
        $transactions = [];

        foreach ($invoicesInPeriod as $invoice) {
            $transactions[] = [
                'date' => $invoice->issue_date->format('Y-m-d'),
                'type' => 'invoice',
                'ref' => $invoice->invoice_number,
                'description' => $invoice->siteJob ? 'Job: ' . $invoice->siteJob->title : 'Invoice',
                'debit' => (float) $invoice->total,
                'credit' => null,
            ];
        }

        foreach ($paymentsInPeriod as $payment) {
            $transactions[] = [
                'date' => $payment->payment_date->format('Y-m-d'),
                'type' => 'payment',
                'ref' => $payment->reference_number ?: 'PMT-' . $payment->id,
                'description' => 'Payment - ' . ucfirst($payment->payment_method ?? 'Other'),
                'debit' => null,
                'credit' => (float) $payment->amount,
            ];
        }

        // Sort transactions by date
        usort($transactions, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // Calculate running balances
        $runningBalance = $openingBalance;
        foreach ($transactions as &$transaction) {
            if ($transaction['debit']) {
                $runningBalance += $transaction['debit'];
            }
            if ($transaction['credit']) {
                $runningBalance -= $transaction['credit'];
            }
            $transaction['balance'] = $runningBalance;
        }

        $closingBalance = $runningBalance;
        $totalInvoiced = array_sum(array_column($transactions, 'debit'));
        $totalPaid = array_sum(array_filter(array_column($transactions, 'credit')));

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'zip' => $customer->zip,
            ],
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $closingBalance,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
        ]);
    }
}

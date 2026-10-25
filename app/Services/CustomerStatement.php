<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;

class CustomerStatement
{
    /**
     * Build a customer statement (opening balance, transactions with running balance, totals).
     */
    public static function build(Customer $customer, ?string $fromDate, ?string $toDate): array
    {
        $fromDate = $fromDate ?: now()->startOfYear()->format('Y-m-d');
        $toDate = $toDate ?: now()->format('Y-m-d');

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
        $paymentsInPeriod = Payment::whereHas('invoice', function ($query) use ($customer) {
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
            $invoiceNumber = $payment->invoice ? $payment->invoice->invoice_number : null;
            $transactions[] = [
                'date' => $payment->payment_date->format('Y-m-d'),
                'type' => 'payment',
                'ref' => $payment->reference_number ?: 'PMT-' . $payment->id,
                'description' => 'Payment - ' . ucwords(str_replace('_', ' ', $payment->payment_method ?? 'other')),
                'invoice_number' => $invoiceNumber,
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
        unset($transaction);

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'zip' => $customer->zip,
                'vat_number' => $customer->vat_number,
            ],
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $runningBalance,
            'total_invoiced' => array_sum(array_column($transactions, 'debit')),
            'total_paid' => array_sum(array_filter(array_column($transactions, 'credit'))),
        ];
    }
}

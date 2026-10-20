<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Payment::with(['invoice.customer'])->orderBy('payment_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Payment Date',
            'Invoice #',
            'Customer',
            'Amount',
            'Payment Method',
            'Reference',
            'Notes',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->payment_date?->format('Y-m-d'),
            $payment->invoice?->invoice_number ?? 'N/A',
            $payment->invoice?->customer?->name ?? 'N/A',
            number_format($payment->amount, 2),
            ucfirst(str_replace('_', ' ', $payment->payment_method ?? '')),
            $payment->reference ?? '',
            $payment->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EDE9FE'],
                ],
            ],
        ];
    }
}

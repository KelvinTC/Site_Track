<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Invoice::with(['customer', 'siteJob'])->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Invoice #',
            'Customer',
            'Job',
            'Issue Date',
            'Due Date',
            'Subtotal',
            'VAT Rate',
            'VAT Amount',
            'Total',
            'Status',
            'Amount Paid',
            'Balance',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->customer?->name ?? 'N/A',
            $invoice->siteJob?->title ?? 'N/A',
            $invoice->issue_date?->format('Y-m-d'),
            $invoice->due_date?->format('Y-m-d'),
            number_format($invoice->subtotal, 2),
            $invoice->vat_rate . '%',
            number_format($invoice->vat_amount, 2),
            number_format($invoice->total, 2),
            ucfirst($invoice->status),
            number_format($invoice->amount_paid, 2),
            number_format($invoice->balance, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'],
                ],
            ],
        ];
    }
}

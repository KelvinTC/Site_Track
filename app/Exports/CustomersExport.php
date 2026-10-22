<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CustomersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection(): Collection
    {
        return Customer::withCount(['invoices', 'siteJobs'])->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Address',
            'City',
            'State',
            'VAT Number',
            'Total Jobs',
            'Total Invoices',
            'Status',
            'Notes',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->email ?? '',
            $customer->phone ?? '',
            $customer->address ?? '',
            $customer->city ?? '',
            $customer->state ?? '',
            $customer->vat_number ?? '',
            $customer->site_jobs_count,
            $customer->invoices_count,
            $customer->is_active ? 'Active' : 'Inactive',
            $customer->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'],
                ],
            ],
        ];
    }
}

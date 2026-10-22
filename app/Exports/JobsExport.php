<?php

namespace App\Exports;

use App\Models\SiteJob;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class JobsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection(): Collection
    {
        return SiteJob::with('customer')->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Job #',
            'Title',
            'Customer',
            'Address',
            'Status',
            'Start Date',
            'End Date',
            'Estimated Cost',
            'Actual Cost',
            'Description',
        ];
    }

    public function map($job): array
    {
        return [
            $job->job_number,
            $job->title,
            $job->customer?->name ?? 'N/A',
            $job->address ?? '',
            ucfirst(str_replace('_', ' ', $job->status)),
            $job->start_date?->format('Y-m-d'),
            $job->end_date?->format('Y-m-d'),
            number_format($job->estimated_cost ?? 0, 2),
            number_format($job->actual_cost ?? 0, 2),
            $job->description ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
            ],
        ];
    }
}

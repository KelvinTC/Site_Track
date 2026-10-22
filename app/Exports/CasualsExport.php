<?php

namespace App\Exports;

use App\Models\Casual;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CasualsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection(): Collection
    {
        return Casual::with('department')->withCount('siteJobs')->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Name',
            'Department',
            'Job Title',
            'Phone',
            'Email',
            'Total Jobs',
            'Status',
            'Notes',
        ];
    }

    public function map($casual): array
    {
        return [
            $casual->casual_number,
            $casual->name,
            $casual->department?->name ?? '',
            $casual->job_title ?? '',
            $casual->phone ?? '',
            $casual->email ?? '',
            $casual->site_jobs_count,
            $casual->is_active ? 'Active' : 'Inactive',
            $casual->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
        ];
    }
}

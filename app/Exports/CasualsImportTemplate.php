<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CasualsImportTemplate implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function array(): array
    {
        // Return sample data rows
        return [
            ['John Doe', 'Finance', 'Accountant', '0771234567', 'john@example.com', 'Sample notes', 'yes'],
            ['Jane Smith', 'Transport', 'Driver', '0777654321', 'jane@example.com', '', 'yes'],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'department',
            'job_title',
            'phone',
            'email',
            'notes',
            'is_active',
        ];
    }

    public function title(): string
    {
        return 'Employees';
    }

    public function styles(Worksheet $sheet): array
    {
        // Add a comment with available departments
        $departments = Department::where('is_active', true)->pluck('name')->toArray();
        $deptList = implode(', ', $departments);

        $sheet->getComment('B1')->getText()->createTextRun("Available departments: {$deptList}");

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }
}

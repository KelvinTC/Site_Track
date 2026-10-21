<?php

namespace App\Exports;

use App\Models\Casual;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CasualsJobsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $fromDate;
    protected ?string $toDate;

    public function __construct(?string $fromDate = null, ?string $toDate = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        return Casual::with(['siteJobs' => function ($q) {
            if ($this->fromDate) {
                $q->wherePivot('work_date', '>=', $this->fromDate);
            }
            if ($this->toDate) {
                $q->wherePivot('work_date', '<=', $this->toDate);
            }
            $q->with('customer');
        }])->orderBy('name')->get()->flatMap(function ($casual) {
            if ($casual->siteJobs->isEmpty()) {
                return [[
                    'casual' => $casual,
                    'job' => null,
                ]];
            }

            return $casual->siteJobs->map(function ($job) use ($casual) {
                return [
                    'casual' => $casual,
                    'job' => $job,
                ];
            });
        });
    }

    public function headings(): array
    {
        return [
            'Casual #',
            'Casual Name',
            'Job Title',
            'Phone',
            'Job #',
            'Job Title',
            'Customer',
            'Work Date',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row['casual']->casual_number,
            $row['casual']->name,
            $row['casual']->job_title ?? '',
            $row['casual']->phone ?? '',
            $row['job']?->job_number ?? '',
            $row['job']?->title ?? '',
            $row['job']?->customer?->name ?? '',
            $row['job']?->pivot?->work_date ?? '',
            $row['job']?->pivot?->notes ?? '',
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

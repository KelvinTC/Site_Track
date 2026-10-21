<?php

namespace App\Imports;

use App\Models\Casual;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;

class CasualsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures, Importable;

    private int $rowsImported = 0;

    public function model(array $row)
    {
        $this->rowsImported++;

        return new Casual([
            'name' => $row['name'],
            'job_title' => $row['job_title'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'notes' => $row['notes'] ?? null,
            'is_active' => $this->parseBoolean($row['is_active'] ?? true),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.email' => 'The email must be a valid email address.',
        ];
    }

    public function getRowsImported(): int
    {
        return $this->rowsImported;
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['yes', 'true', '1', 'active']);
        }

        return (bool) $value;
    }
}

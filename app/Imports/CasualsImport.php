<?php

namespace App\Imports;

use App\Models\Casual;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
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
    private array $departmentCache = [];

    public function model(array $row): Model|array|null
    {
        $this->rowsImported++;

        $departmentId = null;
        if (!empty($row['department'])) {
            $departmentId = $this->getDepartmentId($row['department']);
        }

        return new Casual([
            'name' => $row['name'],
            'department_id' => $departmentId,
            'job_title' => $row['job_title'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'notes' => $row['notes'] ?? null,
            'is_active' => $this->parseBoolean($row['is_active'] ?? true),
        ]);
    }

    private function getDepartmentId(string $departmentName): ?int
    {
        $normalizedName = strtolower(trim($departmentName));

        if (isset($this->departmentCache[$normalizedName])) {
            return $this->departmentCache[$normalizedName];
        }

        $department = Department::whereRaw('LOWER(name) = ?', [$normalizedName])->first();

        if ($department) {
            $this->departmentCache[$normalizedName] = $department->id;
            return $department->id;
        }

        // Default to "Casual" department if not found
        $otherDept = Department::where('name', 'Casual')->first();
        if ($otherDept) {
            $this->departmentCache[$normalizedName] = $otherDept->id;
            return $otherDept->id;
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
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

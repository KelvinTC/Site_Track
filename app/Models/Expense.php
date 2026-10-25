<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\NumberGenerator;

class Expense extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->expense_number)) {
                $expense->expense_number = NumberGenerator::generateExpenseNumber($expense->company_id);
            }
        });
    }

    protected $fillable = [
        'company_id',
        'expense_number',
        'expense_category_id',
        'cost_centre_id',
        'department_id',
        'site_job_id',
        'expense_date',
        'description',
        'reference',
        'vendor',
        'amount',
        'payment_method',
        'status',
        'notes',
        'receipt_path',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function siteJob(): BelongsTo
    {
        return $this->belongsTo(SiteJob::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }
}

<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCentre extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'department_id',
        'budget',
        'description',
        'is_active',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['spent', 'remaining'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getSpentAttribute()
    {
        return $this->expenses()->where('status', 'approved')->sum('amount');
    }

    public function getRemainingAttribute()
    {
        return ($this->budget ?? 0) - $this->spent;
    }
}

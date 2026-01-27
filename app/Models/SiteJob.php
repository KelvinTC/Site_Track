<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\NumberGenerator;

class SiteJob extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (SiteJob $job) {
            if (empty($job->job_number)) {
                $job->job_number = NumberGenerator::generateJobNumber();
            }
        });
    }

    protected $fillable = [
        'job_number',
        'customer_id',
        'title',
        'description',
        'status',
        'start_date',
        'end_date',
        'estimated_cost',
        'actual_cost',
        'site_address',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}

<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\NumberGenerator;

class Casual extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected static function booted(): void
    {
        static::creating(function (Casual $casual) {
            if (empty($casual->casual_number)) {
                $casual->casual_number = NumberGenerator::generateCasualNumber($casual->company_id);
            }
        });
    }

    protected $fillable = [
        'company_id',
        'casual_number',
        'department_id',
        'name',
        'job_title',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function siteJobs(): BelongsToMany
    {
        return $this->belongsToMany(SiteJob::class, 'casual_site_job')
            ->withPivot(['assigned_date', 'work_date', 'notes'])
            ->withTimestamps();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

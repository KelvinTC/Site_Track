<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\NumberGenerator;

class Casual extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Casual $casual) {
            if (empty($casual->casual_number)) {
                $casual->casual_number = NumberGenerator::generateCasualNumber();
            }
        });
    }

    protected $fillable = [
        'casual_number',
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
}

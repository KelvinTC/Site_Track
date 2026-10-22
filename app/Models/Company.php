<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'tax_id',
        'vat_number',
        'vat_rate',
        'vat_registered',
        'logo_path',
        'invoice_prefix',
        'job_prefix',
        'employee_prefix',
        'expense_prefix',
        'invoice_notes',
        'invoice_footer',
        'settings',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
        'vat_registered' => 'boolean',
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);

                // Ensure unique slug
                $originalSlug = $company->slug;
                $count = 1;
                while (static::where('slug', $company->slug)->exists()) {
                    $company->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function siteJobs(): HasMany
    {
        return $this->hasMany(SiteJob::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function casuals(): HasMany
    {
        return $this->hasMany(Casual::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function costCentres(): HasMany
    {
        return $this->hasMany(CostCentre::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    // Helper methods
    public function owner(): ?User
    {
        return $this->users()->wherePivot('role', 'owner')->first();
    }

    public function isOwner(User $user): bool
    {
        return $this->users()
            ->wherePivot('role', 'owner')
            ->where('users.id', $user->id)
            ->exists();
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function addUser(User $user, string $role = 'member', bool $isDefault = false): void
    {
        $this->users()->attach($user->id, [
            'role' => $role,
            'is_default' => $isDefault,
        ]);
    }
}

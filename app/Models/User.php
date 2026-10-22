<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'current_company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function getRoleForCompany(?Company $company = null): ?string
    {
        $company = $company ?? $this->currentCompany;

        if (!$company) {
            return null;
        }

        $pivot = $this->companies()->where('company_id', $company->id)->first()?->pivot;

        return $pivot?->role;
    }

    public function isOwner(?Company $company = null): bool
    {
        return $this->getRoleForCompany($company) === 'owner';
    }

    public function isAdmin(?Company $company = null): bool
    {
        $role = $this->getRoleForCompany($company);
        return in_array($role, ['owner', 'admin']);
    }

    public function isFinance(?Company $company = null): bool
    {
        $role = $this->getRoleForCompany($company);
        return in_array($role, ['owner', 'admin', 'finance']);
    }

    public function hasRole(string|array $roles, ?Company $company = null): bool
    {
        $userRole = $this->getRoleForCompany($company);

        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }

        return in_array($userRole, array_map('trim', $roles));
    }

    public function switchCompany(Company $company): bool
    {
        if (!$this->companies()->where('company_id', $company->id)->exists()) {
            return false;
        }

        $this->current_company_id = $company->id;
        return $this->save();
    }

    public function belongsToCompany(Company $company): bool
    {
        return $this->companies()->where('company_id', $company->id)->exists();
    }
}

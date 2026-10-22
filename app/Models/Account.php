<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'sub_type',
        'parent_id',
        'description',
        'is_active',
        'is_parent',
        'level',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_parent' => 'boolean',
        'level' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'gl_account_id');
    }
}

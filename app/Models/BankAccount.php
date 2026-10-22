<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'bank_name',
        'account_type',
        'account_number',
        'opening_balance',
        'gl_account_id',
        'color',
        'is_active',
        'last_reconciled',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'last_reconciled' => 'date',
    ];

    protected $appends = ['balance', 'reconciled_balance'];

    public function glAccount()
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function getBalanceAttribute()
    {
        $transactionsTotal = $this->transactions()->sum(\DB::raw('COALESCE(credit, 0) - COALESCE(debit, 0)'));
        return ($this->opening_balance ?? 0) + $transactionsTotal;
    }

    public function getReconciledBalanceAttribute()
    {
        $transactionsTotal = $this->transactions()->where('is_reconciled', true)->sum(\DB::raw('COALESCE(credit, 0) - COALESCE(debit, 0)'));
        return ($this->opening_balance ?? 0) + $transactionsTotal;
    }
}

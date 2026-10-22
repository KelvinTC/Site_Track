<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'date',
        'description',
        'reference',
        'debit',
        'credit',
        'is_reconciled',
        'reconciled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    protected $appends = ['type', 'amount', 'account_name'];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function getTypeAttribute()
    {
        return $this->credit > 0 ? 'credit' : 'debit';
    }

    public function getAmountAttribute()
    {
        return $this->credit > 0 ? $this->credit : $this->debit;
    }

    public function getAccountNameAttribute()
    {
        return $this->bankAccount?->name;
    }
}

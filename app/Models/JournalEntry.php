<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'entry_number',
        'date',
        'reference',
        'memo',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    protected $appends = ['total_debit', 'total_credit'];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function getTotalDebitAttribute()
    {
        return $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->lines()->sum('credit');
    }

    public static function generateEntryNumber(?int $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->current_company_id;

        $latest = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->latest('id')
            ->first();

        $number = $latest ? intval(substr($latest->entry_number, 3)) + 1 : 1;
        return 'JE-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}

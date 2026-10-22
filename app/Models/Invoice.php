<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\NumberGenerator;

class Invoice extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = NumberGenerator::generateInvoiceNumber($invoice->company_id);
            }
        });
    }

    protected $fillable = [
        'company_id',
        'invoice_number',
        'site_job_id',
        'customer_id',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'tax',
        'total',
        'status',
        'notes',
        'last_sent_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'last_sent_at' => 'datetime',
    ];

    public function calculateVat(): void
    {
        $this->vat_amount = round($this->subtotal * ($this->vat_rate / 100), 2);
        $this->tax = $this->vat_amount;
        $this->total = $this->subtotal + $this->vat_amount;
    }

    public function siteJob(): BelongsTo
    {
        return $this->belongsTo(SiteJob::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->total - $this->amount_paid);
    }

    public function updateStatus(): void
    {
        $paid = $this->amount_paid;

        if ($paid >= $this->total) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}

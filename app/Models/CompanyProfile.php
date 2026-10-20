<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'zip',
        'tax_id',
        'vat_number',
        'vat_rate',
        'vat_registered',
        'logo_path',
        'invoice_notes',
        'invoice_footer',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
        'vat_registered' => 'boolean',
    ];

    public static function getProfile(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            ['company_name' => 'My Company']
        );
    }
}

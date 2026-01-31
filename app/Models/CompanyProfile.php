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
        'logo_path',
        'invoice_notes',
        'invoice_footer',
    ];

    public static function getProfile(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            ['company_name' => 'My Company']
        );
    }
}

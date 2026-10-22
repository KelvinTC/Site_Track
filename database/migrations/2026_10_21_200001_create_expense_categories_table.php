<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default expense categories
        $categories = [
            ['name' => 'Depreciation', 'code' => 'DEP'],
            ['name' => 'Salaries and Wages', 'code' => 'SAL'],
            ['name' => 'Casual Allowances', 'code' => 'CAS'],
            ['name' => 'Fuel and Oils', 'code' => 'FUEL'],
            ['name' => 'Repairs and Maintenance - MV', 'code' => 'REP-MV'],
            ['name' => 'Repairs and Maintenance - PPE', 'code' => 'REP-PPE'],
            ['name' => 'Repairs and Maintenance - Premises', 'code' => 'REP-PREM'],
            ['name' => 'Site Costs', 'code' => 'SITE'],
            ['name' => 'Commission', 'code' => 'COMM'],
            ['name' => 'Site Visits', 'code' => 'VISIT'],
            ['name' => 'Workshop Rent', 'code' => 'RENT'],
            ['name' => 'Director\'s Fee', 'code' => 'DIR'],
            ['name' => 'AM Foundation', 'code' => 'AMF'],
            ['name' => 'WIFI and Connectivity', 'code' => 'WIFI'],
            ['name' => 'ZIMRA, VAT and PAYE', 'code' => 'TAX'],
            ['name' => 'Safety Wear', 'code' => 'SAFE'],
            ['name' => 'Branding and Stationery', 'code' => 'BRAND'],
            ['name' => 'Travelling and Accommodation', 'code' => 'TRAVEL'],
            ['name' => 'Fines and Penalties', 'code' => 'FINE'],
            ['name' => 'Licenses and Subscriptions', 'code' => 'LIC'],
            ['name' => 'Parking Expenses', 'code' => 'PARK'],
        ];

        foreach ($categories as $category) {
            DB::table('expense_categories')->insert([
                'name' => $category['name'],
                'code' => $category['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};

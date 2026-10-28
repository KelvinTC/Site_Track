<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'DEP', 'name' => 'Depreciation'],
            ['code' => 'SAL', 'name' => 'Salaries and Wages'],
            ['code' => 'CAS', 'name' => 'Casual Allowances'],
            ['code' => 'FUEL', 'name' => 'Fuel and Oils'],
            ['code' => 'REP-MV', 'name' => 'Repairs and Maintenance - MV'],
            ['code' => 'REP-PPE', 'name' => 'Repairs and Maintenance - PPE'],
            ['code' => 'REP-PREM', 'name' => 'Repairs and Maintenance - Premises'],
            ['code' => 'SITE', 'name' => 'Site Costs'],
            ['code' => 'COMM', 'name' => 'Commission'],
            ['code' => 'VISIT', 'name' => 'Site Visits'],
            ['code' => 'RENT', 'name' => 'Workshop Rent'],
            ['code' => 'DIR', 'name' => 'Director\'s Fee'],
            ['code' => 'AMF', 'name' => 'AM Foundation'],
            ['code' => 'WIFI', 'name' => 'WIFI and Connectivity'],
            ['code' => 'TAX', 'name' => 'ZIMRA, VAT and PAYE'],
            ['code' => 'SAFE', 'name' => 'Safety Wear'],
            ['code' => 'BRAND', 'name' => 'Branding and Stationery'],
            ['code' => 'TRAVEL', 'name' => 'Travelling and Accommodation'],
            ['code' => 'FINE', 'name' => 'Fines and Penalties'],
            ['code' => 'LIC', 'name' => 'Licenses and Subscriptions'],
            ['code' => 'PARK', 'name' => 'Parking Expenses'],
        ];

        foreach (Company::all() as $company) {
            foreach ($categories as $data) {
                ExpenseCategory::withoutGlobalScopes()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $data['code']],
                    ['name' => $data['name'], 'is_active' => true]
                );
            }
        }
    }
}

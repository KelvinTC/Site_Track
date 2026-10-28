<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CostCentre;
use Illuminate\Database\Seeder;

class CostCentreSeeder extends Seeder
{
    public function run(): void
    {
        $costCentres = [
            ['code' => 'OPS-TRN', 'name' => 'Operations/Trenching Works'],
            ['code' => 'EST-CON', 'name' => 'Estimations/Construction'],
            ['code' => 'EMV', 'name' => 'Earthmoving'],
            ['code' => 'EQH', 'name' => 'Equipment Hire'],
        ];

        foreach (Company::all() as $company) {
            foreach ($costCentres as $data) {
                CostCentre::withoutGlobalScopes()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $data['code']],
                    ['name' => $data['name'], 'is_active' => true]
                );
            }
        }
    }
}

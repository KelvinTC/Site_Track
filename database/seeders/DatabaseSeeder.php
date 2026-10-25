<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['slug' => 'default-company'],
            ['name' => 'Default Company', 'is_active' => true]
        );

        // One user per role, all with password "password"
        $users = [
            ['name' => 'Owner User', 'email' => 'owner@sitetrack.test', 'role' => 'owner'],
            ['name' => 'Admin User', 'email' => 'admin@sitetrack.test', 'role' => 'admin'],
            ['name' => 'Finance User', 'email' => 'finance@sitetrack.test', 'role' => 'finance'],
            ['name' => 'Member User', 'email' => 'member@sitetrack.test', 'role' => 'member'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'is_active' => true,
                    'current_company_id' => $company->id,
                ]
            );

            $company->users()->syncWithoutDetaching([
                $user->id => ['role' => $data['role'], 'is_default' => true],
            ]);
        }
    }
}

<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase drops every table, so refuse to run anywhere but the testing schema.
     */
    protected function beforeRefreshingDatabase()
    {
        $connection = config('database.default');
        $searchPath = config("database.connections.{$connection}.search_path");

        if ($connection !== 'pgsql' || $searchPath !== 'testing') {
            throw new RuntimeException("Refusing to reset the database: tests must use the pgsql 'testing' schema (got {$connection}/{$searchPath}).");
        }
    }

    /**
     * Create a company with a user in the given role, and authenticate as that user.
     */
    protected function actingAsRole(string $role = 'owner', ?Company $company = null): User
    {
        $company ??= Company::create(['name' => 'Test Company ' . uniqid(), 'is_active' => true]);

        $user = User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . '-' . uniqid() . '@example.test',
            'password' => 'password',
            'is_active' => true,
            'current_company_id' => $company->id,
        ]);
        $company->addUser($user, $role, true);

        Sanctum::actingAs($user);
        // Model scopes and the creating hook read the authenticated user
        auth()->setUser($user);

        return $user;
    }
}

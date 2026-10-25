<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_read_customers_and_jobs_but_not_change_them(): void
    {
        $this->actingAsRole('finance');
        $customer = Customer::create(['name' => 'Acme']);

        $this->getJson('/api/customers')->assertOk();
        $this->getJson("/api/customers/{$customer->id}")->assertOk();
        $this->getJson("/api/customers/{$customer->id}/statement")->assertOk();
        $this->getJson('/api/site-jobs')->assertOk();

        $this->postJson('/api/customers', ['name' => 'New'])->assertForbidden();
        $this->putJson("/api/customers/{$customer->id}", ['name' => 'Renamed'])->assertForbidden();
        $this->deleteJson("/api/customers/{$customer->id}")->assertForbidden();
        $this->postJson('/api/site-jobs', ['customer_id' => $customer->id, 'title' => 'Job'])->assertForbidden();
    }

    public function test_member_cannot_access_finance_or_admin_data(): void
    {
        $this->actingAsRole('member');

        $this->getJson('/api/customers')->assertForbidden();
        $this->getJson('/api/invoices')->assertForbidden();
        $this->getJson('/api/reports/revenue')->assertForbidden();
        $this->getJson('/api/export/customers/excel')->assertForbidden();
    }

    public function test_guests_get_a_json_401_not_a_server_error(): void
    {
        $this->getJson('/api/customers')->assertUnauthorized();
        // Browser-style request (no JSON Accept header), as plain download links make
        $this->get('/api/export/customers/excel')->assertUnauthorized();
    }
}

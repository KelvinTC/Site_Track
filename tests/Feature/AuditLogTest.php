<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SiteJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->actingAsRole('owner');
    }

    protected function makeInvoice(): Invoice
    {
        $customer = Customer::create(['name' => 'Acme']);
        $job = SiteJob::create(['customer_id' => $customer->id, 'title' => 'Trenching']);

        return Invoice::create([
            'customer_id' => $customer->id,
            'site_job_id' => $job->id,
            'issue_date' => '2026-10-01',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'unpaid',
        ]);
    }

    public function test_create_update_and_delete_are_recorded_with_user_and_changes(): void
    {
        $this->postJson('/api/customers', ['name' => 'Zesa', 'email' => 'zesa@example.com'])->assertCreated();
        $customer = Customer::where('name', 'Zesa')->first();

        $this->putJson("/api/customers/{$customer->id}", ['name' => 'ZESA Holdings', 'email' => 'zesa@example.com'])->assertOk();
        $this->deleteJson("/api/customers/{$customer->id}")->assertOk();

        $logs = $this->getJson('/api/audit-logs?type=customer')->assertOk()->json('data');

        $this->assertSame(['deleted', 'updated', 'created'], array_column($logs, 'event'));
        $this->assertSame($this->owner->name, $logs[0]['user_name']);
        $this->assertSame('Customer', $logs[0]['type_label']);

        // Only the changed field, with before and after
        $this->assertSame(['name' => 'Zesa'], $logs[1]['old_values']);
        $this->assertSame(['name' => 'ZESA Holdings'], $logs[1]['new_values']);

        // Deletion keeps a snapshot of what was deleted
        $this->assertSame('ZESA Holdings', $logs[0]['old_values']['name']);
        $this->assertArrayNotHasKey('updated_at', $logs[0]['old_values']);
    }

    public function test_deleted_invoice_can_be_restored_from_the_log(): void
    {
        $invoice = $this->makeInvoice();
        $this->deleteJson("/api/invoices/{$invoice->id}")->assertOk();

        $entry = collect($this->getJson('/api/audit-logs?type=invoice&event=deleted')->json('data'))->first();
        $this->assertSame($invoice->invoice_number, $entry['label']);
        $this->assertTrue($entry['can_restore']);

        // Linked records are shown by name, not id
        $this->assertSame('Acme', $entry['old_values']['customer_id']);
        $this->assertSame($invoice->siteJob->job_number, $entry['old_values']['site_job_id']);

        $this->postJson("/api/audit-logs/{$entry['id']}/restore")->assertOk();
        $this->assertNotNull(Invoice::find($invoice->id));

        // The restore is itself recorded, and can't be repeated
        $this->assertSame('restored', $this->getJson('/api/audit-logs?type=invoice')->json('data.0.event'));
        $this->postJson("/api/audit-logs/{$entry['id']}/restore")->assertUnprocessable();
    }

    public function test_payment_records_the_invoice_status_change(): void
    {
        $invoice = $this->makeInvoice();

        $this->postJson('/api/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'payment_date' => '2026-10-05',
            'payment_method' => 'cash',
        ])->assertCreated();

        $this->getJson('/api/audit-logs?type=payment')->assertJsonPath('data.0.event', 'created');
        $statusChange = $this->getJson("/api/audit-logs?type=invoice&record_id={$invoice->id}&event=updated")->json('data.0');
        $this->assertSame(['status' => 'unpaid'], $statusChange['old_values']);
        $this->assertSame(['status' => 'paid'], $statusChange['new_values']);
    }

    public function test_team_role_changes_are_recorded_without_passwords(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Tendai', 'email' => 'tendai@example.com', 'password' => 'secret123', 'role' => 'member',
        ])->assertCreated();
        $userId = $response->json('data.id');

        $this->putJson("/api/users/{$userId}", ['role' => 'finance', 'password' => 'newsecret1'])->assertOk();

        $update = $this->getJson('/api/audit-logs?type=team&event=updated')->json('data.0');
        $this->assertSame('member', $update['old_values']['role']);
        $this->assertSame('finance', $update['new_values']['role']);
        $this->assertTrue($update['new_values']['password_changed']);
        $this->assertStringNotContainsString('newsecret1', json_encode($update));
        $this->assertStringNotContainsString('secret123', json_encode($this->getJson('/api/audit-logs')->json()));
    }

    public function test_log_is_private_to_each_company_and_admins(): void
    {
        $this->makeInvoice();

        // Another company's owner sees only their own company's history
        $this->actingAsRole('owner', Company::create(['name' => 'Other Co', 'is_active' => true]));
        $logs = $this->getJson('/api/audit-logs')->json('data');
        $this->assertSame([['company', 'Other Co']], array_map(fn ($l) => [$l['type'], $l['label']], $logs));

        // Finance users can't open the audit log
        $this->actingAsRole('finance', $this->owner->currentCompany);
        $this->getJson('/api/audit-logs')->assertForbidden();
    }

    public function test_cannot_restore_another_companys_record(): void
    {
        $invoice = $this->makeInvoice();
        $invoice->delete();
        $entry = AuditLog::where('event', 'deleted')->first();

        $this->actingAsRole('owner', Company::create(['name' => 'Other Co', 'is_active' => true]));
        $this->postJson("/api/audit-logs/{$entry->id}/restore")->assertNotFound();
        $this->assertNull(Invoice::withoutGlobalScopes()->find($invoice->id)->restored_at ?? null);
        $this->assertNotNull(Invoice::withoutGlobalScopes()->withTrashed()->find($invoice->id)->deleted_at);
    }
}

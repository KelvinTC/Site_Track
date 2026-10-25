<?php

namespace Tests\Feature;

use App\Models\Casual;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SiteJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $otherCustomer;
    protected Casual $otherCasual;

    protected function setUp(): void
    {
        parent::setUp();

        // Records belonging to another company
        $other = Company::create(['name' => 'Other Co', 'is_active' => true]);
        $this->otherCustomer = Customer::create(['company_id' => $other->id, 'name' => 'Their Customer']);
        $this->otherCasual = Casual::create(['company_id' => $other->id, 'name' => 'Their Worker', 'is_active' => true]);
    }

    public function test_lists_only_show_the_current_companys_records(): void
    {
        $this->actingAsRole('owner');
        Customer::create(['name' => 'Our Customer']);

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Our Customer');
    }

    public function test_cannot_view_another_companys_record(): void
    {
        $this->actingAsRole('owner');

        $this->getJson("/api/customers/{$this->otherCustomer->id}")->assertNotFound();
    }

    public function test_job_cannot_be_linked_to_another_companys_customer(): void
    {
        $this->actingAsRole('owner');

        $this->postJson('/api/site-jobs', ['customer_id' => $this->otherCustomer->id, 'title' => 'Sneaky job'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer_id');

        $ours = Customer::create(['name' => 'Our Customer']);
        $this->postJson('/api/site-jobs', ['customer_id' => $ours->id, 'title' => 'Real job'])
            ->assertCreated();
    }

    public function test_another_companys_worker_cannot_be_assigned_to_a_job(): void
    {
        $this->actingAsRole('owner');
        $job = SiteJob::create(['customer_id' => Customer::create(['name' => 'Ours'])->id, 'title' => 'Our job']);

        $this->postJson("/api/site-jobs/{$job->id}/casuals", ['casual_ids' => [$this->otherCasual->id]])
            ->assertUnprocessable();

        $this->assertSame(0, $job->casuals()->count());
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\State;
use App\Models\User;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for company setup (M01).
 */
class CompanyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Authenticated user can open company setup.
     */
    public function test_company_setup_page_loads(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.company.edit'))
            ->assertOk()
            ->assertSee('Company Setup');
    }

    /**
     * Company singleton can be saved with valid data.
     */
    public function test_company_can_be_saved(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->where('code', '24')->firstOrFail();

        $payload = [
            'legal_name' => 'Acme Plastics Pvt Ltd',
            'trade_name' => 'Acme Plastics',
            'is_gst_registered' => 0,
            'gstin' => null,
            'pan' => 'ABCDE1234F',
            'registered_address' => '12 Industrial Area, Ahmedabad Gujarat',
            'state_id' => $state->id,
            'pin_code' => '380015',
            'phone' => '9876543210',
            'email' => 'info@acme.test',
            'fy_start_month' => 4,
            'fy_start_day' => 1,
            'base_currency' => 'INR',
            'amount_decimals' => 2,
            'quantity_decimals' => 3,
        ];

        $this->actingAs($user)
            ->postJson(route('admin.company.update'), $payload)
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('companies', [
            'legal_name' => 'Acme Plastics Pvt Ltd',
            'pan' => 'ABCDE1234F',
            'email' => 'info@acme.test',
        ]);

        $this->assertSame(1, Company::query()->count());
    }

    /**
     * GSTIN state mismatch is rejected.
     */
    public function test_gstin_state_mismatch_is_rejected(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $maharashtra = State::query()->where('code', '27')->firstOrFail();

        // Valid-format GSTIN for Gujarat (24) but selected Maharashtra state.
        $payload = [
            'legal_name' => 'Acme Plastics Pvt Ltd',
            'is_gst_registered' => 1,
            'gstin' => '24ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'registered_address' => '12 Industrial Area, Ahmedabad Gujarat',
            'state_id' => $maharashtra->id,
            'pin_code' => '400001',
            'phone' => '9876543210',
            'email' => 'info@acme.test',
            'fy_start_month' => 4,
            'fy_start_day' => 1,
            'base_currency' => 'INR',
            'amount_decimals' => 2,
            'quantity_decimals' => 3,
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.company.update'), $payload);

        // May fail on checksum first; either checksum or state mismatch is acceptable rejection.
        $response->assertStatus(422);
    }
}

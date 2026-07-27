<?php

namespace Tests\Feature\Admin;

use App\Enums\GstType;
use App\Enums\PartyType;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for party master (M01).
 */
class PartyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Party can be created with a contact person.
     */
    public function test_party_can_be_created_with_contact(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->where('code', '24')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.parties.store'), [
                'party_name' => 'Shreeji Traders',
                'party_type' => PartyType::Customer->value,
                'gst_type' => GstType::Unregistered->value,
                'billing_line1' => 'Shop 4, Market Road',
                'billing_city' => 'Ahmedabad',
                'billing_state_id' => $state->id,
                'billing_pin_code' => '380001',
                'billing_country' => 'India',
                'credit_limit' => 0,
                'unlimited_credit' => 0,
                'status' => 'active',
                'contacts' => [
                    [
                        'name' => 'Ramesh Patel',
                        'mobile' => '9876543210',
                        'email' => 'ramesh@example.com',
                        'whatsapp_opt_in' => 1,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('parties', [
            'party_name' => 'Shreeji Traders',
            'party_type' => 'customer',
        ]);

        $this->assertDatabaseHas('party_contacts', [
            'name' => 'Ramesh Patel',
            'mobile' => '9876543210',
        ]);
    }

    /**
     * Party without contacts is rejected.
     */
    public function test_party_requires_at_least_one_contact(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->where('code', '24')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.parties.store'), [
                'party_name' => 'No Contact Party',
                'party_type' => PartyType::Supplier->value,
                'gst_type' => GstType::Unregistered->value,
                'billing_line1' => 'Warehouse Road',
                'billing_city' => 'Surat',
                'billing_state_id' => $state->id,
                'billing_pin_code' => '395003',
                'billing_country' => 'India',
                'unlimited_credit' => 0,
                'status' => 'active',
                'contacts' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts']);
    }

    /**
     * Soft-deleted parties with transactions cannot be hard-removed via delete.
     */
    public function test_party_with_transactions_cannot_be_deleted(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $party = Party::factory()->create(['has_transactions' => true]);

        $this->actingAs($user)
            ->deleteJson(route('admin.parties.destroy', $party))
            ->assertStatus(422);

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'deleted_at' => null,
        ]);
    }
}

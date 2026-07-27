<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\OpportunityStage;
use App\Enums\PartyType;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the CRM module: leads, opportunities and follow-ups (M05).
 */
class CrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_can_be_captured_and_qualified(): void
    {
        $user = User::factory()->superAdmin()->create();

        $leadId = (int) $this->actingAs($user)
            ->postJson(route('admin.leads.store'), [
                'lead_date' => now()->toDateString(),
                'company_name' => 'Shakti Polymers',
                'contact_person' => 'Rakesh Shah',
                'mobile' => '9876543210',
                'email' => 'rakesh@shaktipolymers.test',
                'source' => LeadSource::Exhibition->value,
                'estimated_value' => 250000,
                'requirement' => 'Monthly supply of 5000 crates',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('leads', ['id' => $leadId, 'status' => LeadStatus::New->value]);

        $this->actingAs($user)
            ->postJson(route('admin.leads.status', $leadId), ['status' => LeadStatus::Qualified->value])
            ->assertOk()
            ->assertJsonPath('data.status', LeadStatus::Qualified->value);
    }

    public function test_marking_a_lead_lost_requires_a_reason(): void
    {
        $user = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create();

        $this->actingAs($user)
            ->postJson(route('admin.leads.status', $lead), ['status' => LeadStatus::Lost->value])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('admin.leads.status', $lead), [
                'status' => LeadStatus::Lost->value,
                'lost_reason' => 'Budget not approved',
            ])
            ->assertOk();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => LeadStatus::Lost->value,
            'lost_reason' => 'Budget not approved',
        ]);
    }

    public function test_logging_a_follow_up_advances_a_new_lead_and_sets_the_next_date(): void
    {
        $user = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create();

        $this->actingAs($user)
            ->postJson(route('admin.leads.follow-up', $lead), [
                'follow_up_date' => now()->toDateString(),
                'mode' => 'call',
                'summary' => 'Shared the product catalogue and pricing.',
                'next_follow_up_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertCreated();

        $lead->refresh();

        $this->assertSame(LeadStatus::Contacted, $lead->status);
        $this->assertSame(now()->addDays(7)->toDateString(), $lead->next_follow_up_date->toDateString());
        $this->assertDatabaseHas('crm_follow_ups', [
            'followupable_type' => Lead::class,
            'followupable_id' => $lead->id,
            'mode' => 'call',
        ]);
    }

    public function test_converting_a_lead_creates_a_customer_and_an_opportunity(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->qualified()->create(['company_name' => 'Nova Moulders']);

        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.convert', $lead), [
                'gst_type' => 'unregistered',
                'billing_line1' => '12 GIDC Estate',
                'billing_city' => 'Ahmedabad',
                'billing_state_id' => State::query()->value('id'),
                'billing_pin_code' => '382445',
                'create_opportunity' => true,
                'opportunity_title' => 'Crate supply contract',
            ])
            ->assertOk();

        $partyId = (int) $response->json('data.party_id');
        $opportunityId = (int) $response->json('data.opportunity_id');

        $party = Party::query()->findOrFail($partyId);
        $this->assertSame('Nova Moulders', $party->party_name);
        $this->assertSame(PartyType::Customer, $party->party_type);
        $this->assertDatabaseHas('party_contacts', ['party_id' => $partyId, 'name' => $lead->contact_person]);

        $lead->refresh();
        $this->assertSame(LeadStatus::Converted, $lead->status);
        $this->assertSame($partyId, $lead->converted_party_id);

        $opportunity = Opportunity::query()->findOrFail($opportunityId);
        $this->assertSame($partyId, $opportunity->party_id);
        $this->assertSame(OpportunityStage::Qualification, $opportunity->stage);
    }

    public function test_a_converted_lead_is_locked(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->qualified()->create();

        $this->actingAs($user)
            ->postJson(route('admin.leads.convert', $lead), [
                'gst_type' => 'unregistered',
                'billing_line1' => '9 Ring Road',
                'billing_city' => 'Rajkot',
                'billing_state_id' => State::query()->value('id'),
                'billing_pin_code' => '360001',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->putJson(route('admin.leads.update', $lead), [
                'lead_date' => now()->toDateString(),
                'company_name' => 'Renamed',
                'contact_person' => $lead->contact_person,
                'mobile' => $lead->mobile,
                'source' => $lead->source->value,
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->deleteJson(route('admin.leads.destroy', $lead))
            ->assertStatus(422);
    }

    public function test_opportunity_needs_a_lead_or_a_customer(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->postJson(route('admin.opportunities.store'), [
                'opportunity_date' => now()->toDateString(),
                'title' => 'Floating opportunity',
                'expected_value' => 1000,
            ])
            ->assertStatus(422);
    }

    public function test_opportunity_stage_moves_set_probability_and_close_the_record(): void
    {
        $user = User::factory()->superAdmin()->create();
        $party = Party::factory()->create(['party_type' => PartyType::Customer]);

        $opportunityId = (int) $this->actingAs($user)
            ->postJson(route('admin.opportunities.store'), [
                'opportunity_date' => now()->toDateString(),
                'title' => 'Annual crate rate contract',
                'party_id' => $party->id,
                'expected_value' => 400000,
                'expected_close_date' => now()->addDays(20)->toDateString(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.opportunities.stage', $opportunityId), ['stage' => OpportunityStage::Negotiation->value])
            ->assertOk()
            ->assertJsonPath('data.probability_percent', OpportunityStage::Negotiation->defaultProbability());

        $this->actingAs($user)
            ->postJson(route('admin.opportunities.stage', $opportunityId), ['stage' => OpportunityStage::Won->value])
            ->assertOk()
            ->assertJsonPath('data.stage', OpportunityStage::Won->value);

        // A closed opportunity accepts no further changes.
        $this->actingAs($user)
            ->postJson(route('admin.opportunities.stage', $opportunityId), ['stage' => OpportunityStage::Lost->value, 'lost_reason' => 'x'])
            ->assertStatus(422);
    }

    public function test_won_stage_requires_a_customer_on_the_opportunity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->qualified()->create();

        $opportunityId = (int) $this->actingAs($user)
            ->postJson(route('admin.opportunities.store'), [
                'opportunity_date' => now()->toDateString(),
                'title' => 'Lead-only opportunity',
                'lead_id' => $lead->id,
                'expected_value' => 90000,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.opportunities.stage', $opportunityId), ['stage' => OpportunityStage::Won->value])
            ->assertStatus(422);
    }

    public function test_pipeline_board_totals_only_open_stages(): void
    {
        $user = User::factory()->superAdmin()->create();

        Opportunity::factory()->create(['expected_value' => 100000, 'probability_percent' => 50, 'stage' => OpportunityStage::Proposal]);
        Opportunity::factory()->create(['expected_value' => 200000, 'probability_percent' => 100, 'stage' => OpportunityStage::Won]);

        $pipeline = app(\App\Services\OpportunityService::class)->pipeline();

        $this->assertEqualsWithDelta(100000.0, $pipeline['total_value'], 0.01);
        $this->assertEqualsWithDelta(50000.0, $pipeline['total_weighted'], 0.01);
        $this->assertSame(1, $pipeline['stages'][OpportunityStage::Won->value]['count']);

        $this->actingAs($user)->get(route('admin.opportunities.pipeline'))->assertOk();
    }

    public function test_crm_pages_render(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)->get(route('admin.leads.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.leads.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.leads.edit', $lead))->assertOk();
        $this->actingAs($user)->get(route('admin.opportunities.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.opportunities.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.opportunities.edit', $opportunity))->assertOk();
    }
}

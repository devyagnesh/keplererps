<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Enums\PartyType;
use App\Enums\TrackingType;
use App\Models\Item;
use App\Models\NotificationRule;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\CatalogueNotification;
use App\Services\DocumentShareService;
use App\Services\NotificationDispatchService;
use App\Services\WhatsAppService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Remaining priority slices: WhatsApp, shares, portal, C4/C5/C7.
 */
class RemainingPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_channel_is_supported_and_dry_runs_without_credentials(): void
    {
        $this->seed(SystemSettingSeeder::class);
        Notification::fake();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->superAdmin()->create(['mobile' => '9876543210']);

        NotificationRule::factory()->create([
            'event' => NotificationEvent::LeadConverted,
            'channel' => NotificationChannel::WhatsApp,
            'recipient_type' => NotificationRecipientType::Role,
            'recipient_value' => 'super-admin',
            'subject_template' => 'Lead {{document_no}}',
            'body_template' => 'Converted',
            'is_active' => true,
        ]);

        $this->assertTrue(NotificationChannel::WhatsApp->isSupported());
        $this->assertTrue(NotificationChannel::Firebase->isSupported());

        $sent = app(NotificationDispatchService::class)->dispatch(
            NotificationEvent::LeadConverted,
            ['document_no' => 'LD-1']
        );

        $this->assertSame(1, $sent);
        Notification::assertSentTo($user, CatalogueNotification::class);

        $result = app(WhatsAppService::class)->sendText('9876543210', 'Hello');
        $this->assertSame('skipped', $result['status']);
    }

    public function test_quotation_whatsapp_share_and_print_template_and_portal(): void
    {
        Http::fake();
        $this->seed(SystemSettingSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create(['code' => '24', 'name' => 'Gujarat', 'is_active' => true]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'selling_price' => 100,
            'gst_rate' => 18,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.print-templates.store'), [
                'code' => 'Q1',
                'name' => 'Default Quotation',
                'document_type' => 'sales_quotation',
                'is_default' => true,
                'footer_html' => 'Thank you',
            ])
            ->assertCreated();

        $this->actingAs($admin)
            ->postJson(route('admin.terms-blocks.store'), [
                'code' => 'DOM',
                'name' => 'Domestic terms',
                'body' => 'Payment within 30 days.',
            ])
            ->assertCreated();

        $this->actingAs($admin)
            ->postJson(route('admin.ui-labels.store'), [
                'locale' => 'en',
                'label_key' => 'work_order',
                'label_value' => 'Job Card',
            ])
            ->assertCreated();

        $quotationId = $this->actingAs($admin)->postJson(route('admin.sales-quotations.store'), [
            'document_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 2,
                'rate' => 100,
                'gst_rate' => 18,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin)
            ->postJson(route('admin.sales-quotations.whatsapp', $quotationId), [
                'mobile' => '9876543210',
            ])
            ->assertOk()
            ->assertJsonPath('data.channel', 'whatsapp');

        $share = app(DocumentShareService::class)->share('sales_quotation', (int) $quotationId);
        $this->get($share->public_url)->assertOk()->assertSee('Quotation', false);

        $portalUser = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
            'party_id' => $customer->id,
            'is_active' => true,
        ]);

        auth()->logout();
        $this->flushSession();

        $this->post(route('portal.login.submit'), [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard'));

        $this->actingAs($portalUser)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($customer->party_name);
    }
}

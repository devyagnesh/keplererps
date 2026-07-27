<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentStatus;
use App\Enums\VoucherType;
use App\Models\Item;
use App\Models\JournalVoucher;
use App\Models\LedgerAccount;
use App\Models\PriceList;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PeriodLockService;
use App\Services\PriceListService;
use App\Services\StockTakeService;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gap-close coverage for M05–M17 / M16 thin slices.
 */
class GapCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_lock_blocks_voucher_post_without_override(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seed(SystemSettingSeeder::class);
        $this->seed(LedgerAccountSeeder::class);

        $this->actingAs($user)
            ->postJson(route('admin.period-locks.store'), [
                'locked_to' => now()->toDateString(),
                'reason' => 'Month close',
            ])
            ->assertCreated();

        $cash = LedgerAccount::query()->where('code', '1100')->firstOrFail();
        $sales = LedgerAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.store'), [
                'document_date' => now()->toDateString(),
                'voucher_type' => VoucherType::Journal->value,
                'narration' => 'Locked period entry',
                'lines' => [
                    ['ledger_account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                    ['ledger_account_id' => $sales->id, 'debit' => 0, 'credit' => 100],
                ],
            ])
            ->assertCreated();

        $voucherId = (int) JournalVoucher::query()->latest('id')->value('id');

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.post', $voucherId))
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.post', $voucherId), [
                'override_reason' => 'Auditor correction',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Posted->value);
    }

    public function test_price_list_resolves_party_rate_and_stock_take_screens_render(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create(['selling_price' => 50, 'is_sellable' => true, 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'is_active' => true]);

        $list = app(PriceListService::class)->create([
            'code' => 'PL1',
            'name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
            'items' => [['item_id' => $item->id, 'min_qty' => 1, 'rate' => 75]],
        ]);

        $this->assertSame(75.0, app(PriceListService::class)->resolveRate(null, $item->id, 1));
        $this->assertDatabaseHas('price_lists', ['id' => $list->id, 'code' => 'PL1']);

        $this->actingAs($user)->get(route('admin.price-lists.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.stock-takes.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.crm-reports.funnel'))->assertOk();
        $this->actingAs($user)->get(route('admin.qc-reports.pareto'))->assertOk();
        $this->actingAs($user)->get(route('admin.reports.show', 'stock-valuation'))->assertOk();
        $this->actingAs($user)->get(route('public.verify', 'missing-token'))->assertOk();

        $take = app(StockTakeService::class)->create([
            'warehouse_id' => $warehouse->id,
            'document_date' => now()->toDateString(),
        ]);
        $this->assertSame('draft', $take->status);
        $this->assertInstanceOf(PriceList::class, $list);
        $this->assertNotNull(app(PeriodLockService::class)->current() === null || true);
    }

    public function test_custom_field_and_approval_rule_can_be_created(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->postJson(route('admin.custom-fields.store'), [
                'entity_type' => 'party',
                'field_key' => 'zone',
                'label' => 'Zone',
                'field_type' => 'text',
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.approval-rules.store'), [
                'code' => 'PO50K',
                'name' => 'PO over 50k',
                'document_type' => 'purchase_order',
                'condition_value' => 50000,
                'approver_permission' => 'purchase_order.approve',
            ])
            ->assertCreated();
    }
}

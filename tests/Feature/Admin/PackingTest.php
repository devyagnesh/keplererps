<?php

namespace Tests\Feature\Admin;

use App\Enums\PackageStatus;
use App\Enums\PartyType;
use App\Enums\TrackingType;
use App\Enums\TransportMode;
use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\PackageLabel;
use App\Models\PackingUnit;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PackageLabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M17 packing units, package labels and gate scanning.
 */
class PackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_nested_packing_unit_resolves_base_quantity(): void
    {
        $carton = PackingUnit::factory()->create(['quantity' => 5]);
        $box = PackingUnit::factory()->nestedIn($carton)->create(['quantity' => 50]);

        // A carton of 5 boxes of 50 pieces holds 250 pieces.
        $this->assertSame(250.0, $box->baseQuantity());
        $this->assertSame(5.0, $carton->baseQuantity());
    }

    public function test_packing_unit_cannot_nest_inside_itself_or_its_own_child(): void
    {
        $user = User::factory()->superAdmin()->create();
        $uom = Uom::query()->first() ?? Uom::factory()->create();
        $carton = PackingUnit::factory()->create(['quantity' => 5, 'uom_id' => $uom->id]);
        $box = PackingUnit::factory()->nestedIn($carton)->create(['quantity' => 50]);

        $this->actingAs($user)->putJson(route('admin.packing-units.update', $carton), [
            'code' => $carton->code,
            'name' => $carton->name,
            'uom_id' => $uom->id,
            'quantity' => 5,
            'parent_id' => $carton->id,
        ])->assertStatus(422);

        $this->actingAs($user)->putJson(route('admin.packing-units.update', $carton), [
            'code' => $carton->code,
            'name' => $carton->name,
            'uom_id' => $uom->id,
            'quantity' => 5,
            'parent_id' => $box->id,
        ])->assertStatus(422);
    }

    public function test_packing_unit_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->challanContext($user);

        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 1,
        ])->assertCreated();

        $this->actingAs($user)
            ->deleteJson(route('admin.packing-units.destroy', $context['packing_unit']))
            ->assertStatus(422);
    }

    public function test_labels_are_generated_with_qr_payload_and_cannot_exceed_challan_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->challanContext($user);

        // Challan line is 10; two labels of 5 fill it exactly.
        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 2,
            'quantity_per_package' => 5,
        ])->assertCreated();

        $labels = PackageLabel::query()->where('delivery_challan_id', $context['challan_id'])->get();
        $this->assertCount(2, $labels);
        $this->assertStringStartsWith(
            PackageLabelService::PAYLOAD_PREFIX.'|'.$labels->first()->label_no.'|'.$context['item']->item_code,
            $labels->first()->qr_payload
        );
        $this->assertSame(PackageStatus::Packed, $labels->first()->status);

        // One more label would overshoot the line quantity.
        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 1,
            'quantity_per_package' => 5,
        ])->assertStatus(422);
    }

    public function test_scan_verifies_a_package_once_and_rejects_unknown_codes(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->challanContext($user);

        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 1,
            'quantity_per_package' => 10,
        ])->assertCreated();

        $package = PackageLabel::query()->where('delivery_challan_id', $context['challan_id'])->firstOrFail();

        // Full QR payload scan verifies the package.
        $this->actingAs($user)
            ->postJson(route('admin.packages.scan'), ['code' => $package->qr_payload, 'confirm' => true])
            ->assertOk()
            ->assertJsonPath('data.package.status', PackageStatus::Verified->value);

        // A second confirm on the same label is rejected.
        $this->actingAs($user)
            ->postJson(route('admin.packages.scan'), ['code' => $package->label_no, 'confirm' => true])
            ->assertStatus(422);

        // Look-up without confirm still works.
        $this->actingAs($user)
            ->postJson(route('admin.packages.scan'), ['code' => $package->label_no])
            ->assertOk()
            ->assertJsonPath('data.package.label_no', $package->label_no);

        $this->actingAs($user)
            ->postJson(route('admin.packages.scan'), ['code' => 'PKG-DOES-NOT-EXIST'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('admin.packages.scan'), ['code' => 'OTHER|PKG-00001'])
            ->assertStatus(422);
    }

    public function test_challan_dispatch_marks_packages_dispatched_and_blocks_cancellation(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->challanContext($user);

        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 1,
            'quantity_per_package' => 10,
        ])->assertCreated();

        $package = PackageLabel::query()->where('delivery_challan_id', $context['challan_id'])->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.delivery-challans.dispatch', $context['challan_id']))
            ->assertOk();

        $package->refresh();
        $this->assertSame(PackageStatus::Dispatched, $package->status);
        $this->assertNotNull($package->dispatched_at);

        $this->actingAs($user)
            ->deleteJson(route('admin.packages.destroy', $package))
            ->assertStatus(422);
    }

    public function test_packing_summary_tracks_open_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->challanContext($user);

        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 1,
            'quantity_per_package' => 4,
        ])->assertCreated();

        $summary = $this->actingAs($user)
            ->getJson(route('admin.packages.summary', $context['challan_id']))
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(10.0, (float) $summary[0]['challan_qty'], 0.0001);
        $this->assertEqualsWithDelta(4.0, (float) $summary[0]['packed_qty'], 0.0001);
        $this->assertEqualsWithDelta(6.0, (float) $summary[0]['open_qty'], 0.0001);
        $this->assertSame(1, $summary[0]['package_count']);
    }

    public function test_packing_screens_render(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->challanContext($user);

        $this->actingAs($user)->postJson(route('admin.packages.store'), [
            'delivery_challan_id' => $context['challan_id'],
            'delivery_challan_item_id' => $context['challan_item_id'],
            'packing_unit_id' => $context['packing_unit']->id,
            'package_count' => 1,
            'quantity_per_package' => 10,
        ])->assertCreated();

        $this->actingAs($user)->get(route('admin.packing-units.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.packing-units.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.packing-units.edit', $context['packing_unit']))->assertOk();
        $this->actingAs($user)->get(route('admin.packages.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.packages.scan-form'))->assertOk();
        $this->actingAs($user)
            ->get(route('admin.packages.pack', ['delivery_challan_id' => $context['challan_id']]))
            ->assertOk()
            ->assertSee($context['packing_unit']->code);
        $this->actingAs($user)
            ->get(route('admin.packages.print', ['delivery_challan_id' => $context['challan_id']]))
            ->assertOk()
            ->assertSee(PackageLabelService::PAYLOAD_PREFIX.'|', false)
            ->assertSee('data-qr-payload=', false)
            ->assertSee('qrcode.min.js', false);
    }

    /**
     * Build a confirmed sales order plus a draft delivery challan of 10 units.
     *
     * @return array{challan_id: int, challan_item_id: int, item: Item, packing_unit: PackingUnit}
     */
    protected function challanContext(User $user): array
    {
        $state = State::query()->first() ?? State::query()->create(['code' => '24', 'name' => 'Gujarat', 'is_active' => true]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => false]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'selling_price' => 100,
            'gst_rate' => 0,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 100,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 8000,
        ]);

        $orderId = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 10,
                'rate' => 100,
                'gst_rate' => 0,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $challanId = $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01AB1234',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 10]],
        ])->assertCreated()->json('data.id');

        return [
            'challan_id' => (int) $challanId,
            'challan_item_id' => (int) DeliveryChallanItem::query()
                ->where('delivery_challan_id', $challanId)
                ->value('id'),
            'item' => $item,
            'packing_unit' => PackingUnit::factory()->create([
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 5,
            ]),
        ];
    }
}

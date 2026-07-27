<?php

namespace Tests\Feature\Admin;

use App\Enums\MatchStatus;
use App\Enums\PartyType;
use App\Enums\PurchaseBillStatus;
use App\Enums\TrackingType;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Party;
use App\Models\Permission;
use App\Models\PurchaseBill;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for purchase bills and three-way match (US-M07-04).
 */
class PurchaseBillTest extends TestCase
{
    use RefreshDatabase;

    public function test_matched_bill_can_be_approved(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($user, orderedQty: 100, rate: 10, receivedQty: 40);
        $grnLine = $grn->items->first();

        $create = $this->actingAs($user)
            ->postJson(route('admin.purchase-bills.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'supplier_bill_no' => 'SUP-BILL-1',
                'supplier_bill_date' => now()->toDateString(),
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'uom_id' => $grnLine->purchaseOrderItem->uom_id,
                    'quantity' => 40,
                    'rate' => 10,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $billId = (int) $create->json('data.id');
        $bill = PurchaseBill::query()->with('items')->findOrFail($billId);

        $this->assertSame(MatchStatus::Matched, $bill->match_status);
        $this->assertEqualsWithDelta(400.0, (float) $bill->subtotal, 0.01);
        $this->assertEqualsWithDelta(472.0, (float) $bill->grand_total, 0.01);
        $this->assertSame(MatchStatus::Matched, $bill->items->first()->match_status);

        $this->actingAs($user)
            ->postJson(route('admin.purchase-bills.approve', $billId))
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseBillStatus::Approved->value);
    }

    public function test_rate_and_qty_mismatch_is_flagged_on_the_bill(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($user, orderedQty: 100, rate: 10, receivedQty: 40);
        $grnLine = $grn->items->first();

        $create = $this->actingAs($user)
            ->postJson(route('admin.purchase-bills.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'supplier_bill_no' => 'SUP-BILL-2',
                'supplier_bill_date' => now()->toDateString(),
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'uom_id' => $grnLine->purchaseOrderItem->uom_id,
                    'quantity' => 45,
                    'rate' => 12,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated();

        $bill = PurchaseBill::query()->with('items')->findOrFail((int) $create->json('data.id'));

        $this->assertSame(MatchStatus::RateAndQtyMismatch, $bill->match_status);
        $this->assertEqualsWithDelta(20.0, (float) $bill->items->first()->rate_variance_percent, 0.0001);
        $this->assertEqualsWithDelta(5.0, (float) $bill->items->first()->qty_variance, 0.0001);
    }

    public function test_mismatched_bill_requires_reason_and_override_permission(): void
    {
        $approver = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($approver, orderedQty: 100, rate: 10, receivedQty: 40);
        $grnLine = $grn->items->first();

        $billId = (int) $this->actingAs($approver)
            ->postJson(route('admin.purchase-bills.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'supplier_bill_no' => 'SUP-BILL-3',
                'supplier_bill_date' => now()->toDateString(),
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'uom_id' => $grnLine->purchaseOrderItem->uom_id,
                    'quantity' => 40,
                    'rate' => 12,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($approver)
            ->postJson(route('admin.purchase-bills.approve', $billId))
            ->assertStatus(422)
            ->assertJsonPath('errors.mismatch_reason.0', 'A reason is required to approve a bill outside match tolerance.');

        $restricted = $this->userWithoutMismatchOverride();

        $this->actingAs($restricted)
            ->postJson(route('admin.purchase-bills.approve', $billId), [
                'mismatch_reason' => 'Supplier raised rate as agreed on call.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->actingAs($approver)
            ->postJson(route('admin.purchase-bills.approve', $billId), [
                'mismatch_reason' => 'Supplier raised rate as agreed on call.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseBillStatus::Approved->value);

        $this->assertDatabaseHas('purchase_bills', [
            'id' => $billId,
            'mismatch_reason' => 'Supplier raised rate as agreed on call.',
        ]);
    }

    public function test_draft_bill_cannot_be_created_for_unposted_goods_receipt(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = GoodsReceipt::factory()->create();

        $this->actingAs($user)
            ->postJson(route('admin.purchase-bills.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'supplier_bill_no' => 'SUP-BILL-4',
                'supplier_bill_date' => now()->toDateString(),
                'items' => [[
                    'goods_receipt_item_id' => 1,
                    'uom_id' => 1,
                    'quantity' => 1,
                    'rate' => 1,
                ]],
            ])
            ->assertStatus(422);
    }

    public function test_approved_bill_cannot_be_edited(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($user, orderedQty: 50, rate: 20, receivedQty: 25);
        $grnLine = $grn->items->first();

        $billId = (int) $this->actingAs($user)
            ->postJson(route('admin.purchase-bills.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'supplier_bill_no' => 'SUP-BILL-5',
                'supplier_bill_date' => now()->toDateString(),
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'uom_id' => $grnLine->purchaseOrderItem->uom_id,
                    'quantity' => 25,
                    'rate' => 20,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.purchase-bills.approve', $billId))->assertOk();

        $this->actingAs($user)
            ->putJson(route('admin.purchase-bills.update', $billId), [
                'document_date' => now()->toDateString(),
                'supplier_bill_no' => 'SUP-BILL-5',
                'supplier_bill_date' => now()->toDateString(),
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'uom_id' => $grnLine->purchaseOrderItem->uom_id,
                    'quantity' => 25,
                    'rate' => 21,
                ]],
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->deleteJson(route('admin.purchase-bills.destroy', $billId))
            ->assertStatus(422);
    }

    public function test_purchase_bill_index_and_create_pages_render(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('admin.purchase-bills.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.purchase-bills.create'))->assertOk();
    }

    /**
     * Approve a PO, receive part of it and post the GRN.
     */
    protected function postedGoodsReceipt(User $user, float $orderedQty, float $rate, float $receivedQty): GoodsReceipt
    {
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_purchasable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'standard_cost' => $rate,
        ]);

        $poId = (int) $this->actingAs($user)
            ->postJson(route('admin.purchase-orders.store'), [
                'document_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'expected_delivery_date' => now()->addDays(5)->toDateString(),
                'items' => [[
                    'item_id' => $item->id,
                    'uom_id' => $item->stock_uom_id,
                    'quantity' => $orderedQty,
                    'rate' => $rate,
                    'gst_rate' => 18,
                    'tolerance_percent' => 5,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();

        $poLineId = (int) \App\Models\PurchaseOrder::query()->findOrFail($poId)->items()->first()->id;

        $grnId = (int) $this->actingAs($user)
            ->postJson(route('admin.goods-receipts.store'), [
                'document_date' => now()->toDateString(),
                'purchase_order_id' => $poId,
                'supplier_invoice_no' => 'SUP-INV-'.fake()->unique()->numerify('#####'),
                'supplier_invoice_date' => now()->toDateString(),
                'items' => [[
                    'purchase_order_item_id' => $poLineId,
                    'received_qty' => $receivedQty,
                    'accepted_qty' => $receivedQty,
                    'rejected_qty' => 0,
                    'rate' => $rate,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.goods-receipts.post', $grnId))->assertOk();

        return GoodsReceipt::query()->with('items.purchaseOrderItem')->findOrFail($grnId);
    }

    /**
     * User holding every purchase bill permission except the mismatch override.
     */
    protected function userWithoutMismatchOverride(): User
    {
        $this->seed(PermissionSeeder::class);

        $role = Role::factory()->create();
        $role->permissions()->sync(
            Permission::query()->where('name', '!=', 'purchase_bill.approve_mismatch')->pluck('id')
        );

        $user = User::factory()->create();
        $user->syncRoles([(int) $role->id]);

        return $user;
    }
}

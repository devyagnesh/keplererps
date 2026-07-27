<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentStatus;
use App\Enums\PartyType;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for purchase and sales return documents (stock loop closure).
 */
class ReturnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_return_posts_stock_out_and_cannot_exceed_received_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($user, receivedQty: 40, rate: 10);
        $grnLine = $grn->items->first();

        $this->actingAs($user)
            ->postJson(route('admin.purchase-returns.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'reason' => 'Damaged material received',
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'quantity' => 41,
                    'rate' => 10,
                ]],
            ])
            ->assertStatus(422);

        $returnId = (int) $this->actingAs($user)
            ->postJson(route('admin.purchase-returns.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'reason' => 'Damaged material received',
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'quantity' => 15,
                    'rate' => 10,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.purchase-returns.post', $returnId))
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Posted->value);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $grnLine->item_id,
            'warehouse_id' => $grn->warehouse_id,
            'transaction_type' => StockTransactionType::PurchaseReturn->value,
            'qty_out' => 15,
        ]);

        $balance = StockBalance::query()
            ->where('item_id', $grnLine->item_id)
            ->where('warehouse_id', $grn->warehouse_id)
            ->where('batch_key', 0)
            ->firstOrFail();

        $this->assertEqualsWithDelta(25.0, (float) $balance->qty, 0.0001);
    }

    public function test_returned_quantity_reduces_the_open_returnable_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($user, receivedQty: 40, rate: 10);
        $grnLine = $grn->items->first();

        $this->actingAs($user)
            ->postJson(route('admin.purchase-returns.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'reason' => 'Short supply adjustment',
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'quantity' => 30,
                    'rate' => 10,
                ]],
            ])
            ->assertCreated();

        $lines = $this->actingAs($user)
            ->getJson(route('admin.purchase-returns.returnable-lines', $grn))
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(10.0, (float) $lines[0]['open_qty'], 0.0001);
    }

    public function test_cancelling_a_posted_purchase_return_reverses_the_ledger(): void
    {
        $user = User::factory()->superAdmin()->create();
        $grn = $this->postedGoodsReceipt($user, receivedQty: 20, rate: 5);
        $grnLine = $grn->items->first();

        $returnId = (int) $this->actingAs($user)
            ->postJson(route('admin.purchase-returns.store'), [
                'document_date' => now()->toDateString(),
                'goods_receipt_id' => $grn->id,
                'reason' => 'Wrong grade supplied',
                'items' => [[
                    'goods_receipt_item_id' => $grnLine->id,
                    'quantity' => 5,
                    'rate' => 5,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.purchase-returns.post', $returnId))->assertOk();
        $this->actingAs($user)->postJson(route('admin.purchase-returns.cancel', $returnId))->assertOk();

        $balance = StockBalance::query()
            ->where('item_id', $grnLine->item_id)
            ->where('warehouse_id', $grn->warehouse_id)
            ->where('batch_key', 0)
            ->firstOrFail();

        $this->assertEqualsWithDelta(20.0, (float) $balance->qty, 0.0001);
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => StockTransactionType::Reversal->value,
            'qty_in' => 5,
        ]);
    }

    public function test_sales_return_posts_stock_back_into_the_warehouse(): void
    {
        $user = User::factory()->superAdmin()->create();
        [$invoice, $invoiceLine] = $this->confirmedInvoice($user, quantity: 10, rate: 50);

        $returnId = (int) $this->actingAs($user)
            ->postJson(route('admin.sales-returns.store'), [
                'document_date' => now()->toDateString(),
                'sales_invoice_id' => $invoice->id,
                'warehouse_id' => $invoice->warehouse_id,
                'reason' => 'Customer rejected the lot',
                'items' => [[
                    'sales_invoice_item_id' => $invoiceLine->id,
                    'item_id' => $invoiceLine->item_id,
                    'uom_id' => $invoiceLine->uom_id,
                    'quantity' => 4,
                    'rate' => 50,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.sales-returns.post', $returnId))
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Posted->value);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $invoiceLine->item_id,
            'warehouse_id' => $invoice->warehouse_id,
            'transaction_type' => StockTransactionType::SalesReturn->value,
            'qty_in' => 4,
        ]);
    }

    public function test_sales_return_cannot_exceed_invoiced_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        [$invoice, $invoiceLine] = $this->confirmedInvoice($user, quantity: 10, rate: 50);

        $this->actingAs($user)
            ->postJson(route('admin.sales-returns.store'), [
                'document_date' => now()->toDateString(),
                'sales_invoice_id' => $invoice->id,
                'warehouse_id' => $invoice->warehouse_id,
                'reason' => 'Customer rejected the lot',
                'items' => [[
                    'sales_invoice_item_id' => $invoiceLine->id,
                    'item_id' => $invoiceLine->item_id,
                    'uom_id' => $invoiceLine->uom_id,
                    'quantity' => 11,
                    'rate' => 50,
                ]],
            ])
            ->assertStatus(422);
    }

    public function test_return_listing_pages_render(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('admin.purchase-returns.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.purchase-returns.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.sales-returns.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.sales-returns.create'))->assertOk();
    }

    /**
     * Approve a PO, receive it and post the GRN.
     */
    protected function postedGoodsReceipt(User $user, float $receivedQty, float $rate): GoodsReceipt
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
                    'quantity' => $receivedQty,
                    'rate' => $rate,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();
        $poLineId = (int) PurchaseOrder::query()->findOrFail($poId)->items()->first()->id;

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
     * Confirm a sales order and raise a confirmed invoice against it.
     *
     * @return array{0: SalesInvoice, 1: \App\Models\SalesInvoiceItem}
     */
    protected function confirmedInvoice(User $user, float $quantity, float $rate): array
    {
        $customer = Party::factory()->create(['party_type' => PartyType::Customer]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'selling_price' => $rate,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => $quantity * 2,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => $quantity * 2 * $rate,
        ]);

        $orderId = (int) $this->actingAs($user)
            ->postJson(route('admin.sales-orders.store'), [
                'document_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'place_of_supply_state_id' => State::query()->value('id'),
                'expected_delivery_date' => now()->addDays(5)->toDateString(),
                'items' => [[
                    'item_id' => $item->id,
                    'uom_id' => $item->stock_uom_id,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = (int) SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $invoiceId = (int) $this->actingAs($user)
            ->postJson(route('admin.sales-invoices.store'), [
                'document_date' => now()->toDateString(),
                'sales_order_id' => $orderId,
                'items' => [[
                    'sales_order_item_id' => $soLineId,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-invoices.confirm', $invoiceId))->assertOk();

        $invoice = SalesInvoice::query()->with('items')->findOrFail($invoiceId);

        return [$invoice, $invoice->items->first()];
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\PartyType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QcDisposition;
use App\Enums\QcParameterType;
use App\Enums\SamplingPlanType;
use App\Enums\TrackingType;
use App\Enums\WarehouseType;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\QcInspection;
use App\Models\QcTemplate;
use App\Models\QcTemplateParameter;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M10 Quality Control (incoming GRN inspection).
 */
class QualityControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_post_for_inspected_item_lands_in_quarantine_and_creates_inspection(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $store = Warehouse::factory()->create(['is_leaf' => true, 'warehouse_type' => WarehouseType::Store]);
        $item = Item::factory()->create([
            'is_purchasable' => true,
            'tracking_type' => TrackingType::None,
            'requires_inspection' => true,
            'standard_cost' => 10,
            'gst_rate' => 18,
        ]);

        $template = QcTemplate::factory()->create([
            'inspection_type' => InspectionType::Incoming,
            'item_id' => $item->id,
            'sampling_plan' => SamplingPlanType::Fixed,
            'sampling_value' => 5,
        ]);
        QcTemplateParameter::query()->create([
            'qc_template_id' => $template->id,
            'name' => 'Diameter',
            'parameter_type' => QcParameterType::Numeric,
            'min_value' => 9,
            'max_value' => 11,
            'is_critical' => true,
            'sort_order' => 0,
        ]);

        $create = $this->actingAs($user)->postJson(route('admin.purchase-orders.store'), [
            'document_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $store->id,
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 100,
                'rate' => 10,
                'gst_rate' => 18,
                'tolerance_percent' => 0,
            ]],
        ])->assertCreated();

        $poId = $create->json('data.id');
        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();
        $poLineId = PurchaseOrder::query()->findOrFail($poId)->items()->first()->id;

        $this->assertTrue((bool) PurchaseOrder::query()->findOrFail($poId)->items()->first()->requires_inspection);

        $grn = $this->actingAs($user)->postJson(route('admin.goods-receipts.store'), [
            'document_date' => now()->toDateString(),
            'purchase_order_id' => $poId,
            'supplier_invoice_no' => 'QC-INV-1',
            'supplier_invoice_date' => now()->toDateString(),
            'items' => [[
                'purchase_order_item_id' => $poLineId,
                'received_qty' => 100,
                'accepted_qty' => 100,
                'rejected_qty' => 0,
                'rate' => 10,
            ]],
        ])->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.goods-receipts.post', $grn->json('data.id')))
            ->assertOk();

        $quarantine = Warehouse::query()
            ->where('warehouse_type', WarehouseType::Quarantine)
            ->where('branch_id', $store->branch_id)
            ->first();

        $this->assertNotNull($quarantine);
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $quarantine->id,
            'qty' => 100,
        ]);
        $this->assertDatabaseMissing('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $store->id,
            'qty' => 100,
        ]);

        $inspection = QcInspection::query()->where('item_id', $item->id)->first();
        $this->assertNotNull($inspection);
        $this->assertSame(InspectionStatus::Pending, $inspection->status);
        $this->assertEqualsWithDelta(5.0, (float) $inspection->sample_size, 0.0001);
        $this->assertSame($quarantine->id, $inspection->quarantine_warehouse_id);
        $this->assertSame($store->id, $inspection->target_warehouse_id);
        $this->assertCount(1, $inspection->readings);
    }

    public function test_completing_pass_inspection_moves_stock_from_quarantine_to_store(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $store = Warehouse::factory()->create(['is_leaf' => true, 'warehouse_type' => WarehouseType::Store]);
        $item = Item::factory()->create([
            'is_purchasable' => true,
            'tracking_type' => TrackingType::None,
            'requires_inspection' => true,
            'standard_cost' => 10,
        ]);

        $template = QcTemplate::factory()->create([
            'inspection_type' => InspectionType::Incoming,
            'item_id' => $item->id,
            'sampling_plan' => SamplingPlanType::SqrtPlusOne,
        ]);
        QcTemplateParameter::query()->create([
            'qc_template_id' => $template->id,
            'name' => 'Visual',
            'parameter_type' => QcParameterType::PassFail,
            'is_critical' => true,
            'sort_order' => 0,
        ]);

        $create = $this->actingAs($user)->postJson(route('admin.purchase-orders.store'), [
            'document_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $store->id,
            'expected_delivery_date' => now()->addDays(2)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 50,
                'rate' => 10,
                'gst_rate' => 18,
            ]],
        ])->assertCreated();

        $poId = $create->json('data.id');
        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();
        $poLineId = PurchaseOrder::query()->findOrFail($poId)->items()->first()->id;

        $grn = $this->actingAs($user)->postJson(route('admin.goods-receipts.store'), [
            'document_date' => now()->toDateString(),
            'purchase_order_id' => $poId,
            'supplier_invoice_no' => 'QC-INV-2',
            'supplier_invoice_date' => now()->toDateString(),
            'items' => [[
                'purchase_order_item_id' => $poLineId,
                'received_qty' => 50,
                'accepted_qty' => 50,
                'rejected_qty' => 0,
                'rate' => 10,
            ]],
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('admin.goods-receipts.post', $grn->json('data.id')))->assertOk();

        $inspection = QcInspection::query()->with('readings')->where('item_id', $item->id)->firstOrFail();
        $readingId = $inspection->readings->first()->id;

        $this->actingAs($user)->postJson(route('admin.qc-inspections.complete', $inspection), [
            'disposition' => QcDisposition::Accept->value,
            'accepted_qty' => 50,
            'rejected_qty' => 0,
            'rework_qty' => 0,
            'readings' => [[
                'id' => $readingId,
                'pass_fail_value' => 'pass',
            ]],
        ])->assertOk();

        $quarantine = Warehouse::query()
            ->where('warehouse_type', WarehouseType::Quarantine)
            ->where('branch_id', $store->branch_id)
            ->firstOrFail();

        $this->assertEqualsWithDelta(
            0.0,
            (float) StockBalance::query()->where('item_id', $item->id)->where('warehouse_id', $quarantine->id)->value('qty'),
            0.0001
        );
        $this->assertEqualsWithDelta(
            50.0,
            (float) StockBalance::query()->where('item_id', $item->id)->where('warehouse_id', $store->id)->value('qty'),
            0.0001
        );

        $inspection->refresh();
        $this->assertSame(InspectionStatus::Completed, $inspection->status);
        $this->assertSame(QcDisposition::Accept, $inspection->disposition);
    }

    public function test_in_process_inspection_can_be_raised_manually_with_template_readings(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create(['tracking_type' => TrackingType::None]);

        $template = QcTemplate::factory()->create([
            'inspection_type' => InspectionType::InProcess,
            'item_id' => $item->id,
            'sampling_plan' => SamplingPlanType::Fixed,
            'sampling_value' => 3,
        ]);
        QcTemplateParameter::query()->create([
            'qc_template_id' => $template->id,
            'name' => 'Wall thickness',
            'parameter_type' => QcParameterType::Numeric,
            'min_value' => 2,
            'max_value' => 4,
            'is_critical' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)->get(route('admin.qc-inspections.create'))->assertOk();

        $response = $this->actingAs($user)->postJson(route('admin.qc-inspections.store'), [
            'document_date' => now()->toDateString(),
            'inspection_type' => InspectionType::InProcess->value,
            'item_id' => $item->id,
            'lot_quantity' => 40,
            'remarks' => 'Shift A sampling',
        ])->assertCreated()->assertJsonPath('status', true);

        $inspection = QcInspection::query()->with('readings')->findOrFail($response->json('data.id'));

        $this->assertSame(InspectionType::InProcess, $inspection->inspection_type);
        $this->assertSame(InspectionStatus::Pending, $inspection->status);
        $this->assertSame($template->id, $inspection->qc_template_id);
        $this->assertEqualsWithDelta(3.0, (float) $inspection->sample_size, 0.0001);
        $this->assertCount(1, $inspection->readings);
        $this->assertNull($inspection->quarantine_warehouse_id);
    }

    public function test_manual_inspection_rejects_incoming_stage(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create();

        $this->actingAs($user)->postJson(route('admin.qc-inspections.store'), [
            'document_date' => now()->toDateString(),
            'inspection_type' => InspectionType::Incoming->value,
            'item_id' => $item->id,
            'lot_quantity' => 10,
        ])->assertStatus(422);
    }

    public function test_manual_inspection_requires_reason_when_sample_size_is_overridden(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create();

        $template = QcTemplate::factory()->create([
            'inspection_type' => InspectionType::PreDispatch,
            'item_id' => $item->id,
            'sampling_plan' => SamplingPlanType::Fixed,
            'sampling_value' => 5,
        ]);
        QcTemplateParameter::query()->create([
            'qc_template_id' => $template->id,
            'name' => 'Label check',
            'parameter_type' => QcParameterType::PassFail,
            'is_critical' => false,
            'sort_order' => 0,
        ]);

        $payload = [
            'document_date' => now()->toDateString(),
            'inspection_type' => InspectionType::PreDispatch->value,
            'item_id' => $item->id,
            'lot_quantity' => 100,
            'sample_size' => 2,
        ];

        $this->actingAs($user)->postJson(route('admin.qc-inspections.store'), $payload)->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('admin.qc-inspections.store'), $payload + ['sample_override_reason' => 'Customer waiver'])
            ->assertCreated();
    }

    public function test_qc_template_can_be_created_via_admin(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create();

        $this->actingAs($user)->postJson(route('admin.qc-templates.store'), [
            'code' => 'INCOMING-DIM',
            'name' => 'Incoming dimension check',
            'inspection_type' => InspectionType::Incoming->value,
            'item_id' => $item->id,
            'sampling_plan' => SamplingPlanType::Percentage->value,
            'sampling_value' => 10,
            'is_active' => true,
            'parameters' => [[
                'name' => 'Width',
                'parameter_type' => QcParameterType::Numeric->value,
                'min_value' => 1,
                'max_value' => 5,
                'is_critical' => true,
            ]],
        ])->assertCreated()->assertJsonPath('status', true);

        $this->assertDatabaseHas('qc_templates', ['code' => 'INCOMING-DIM']);
        $this->assertDatabaseHas('qc_template_parameters', ['name' => 'Width']);
    }
}

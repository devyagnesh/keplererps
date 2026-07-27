<?php

namespace Database\Factories;

use App\Enums\GrnStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $po = PurchaseOrder::factory()->create();

        return [
            'document_no' => 'GRN-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'document_date' => now()->toDateString(),
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'warehouse_id' => $po->warehouse_id,
            'supplier_invoice_no' => 'INV-'.fake()->unique()->numerify('####'),
            'supplier_invoice_date' => now()->toDateString(),
            'status' => GrnStatus::Draft,
        ];
    }
}

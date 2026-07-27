<?php

namespace Database\Factories;

use App\Enums\PartyType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_no' => 'PO-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'document_date' => now()->toDateString(),
            'supplier_id' => Party::factory()->state(['party_type' => PartyType::Supplier]),
            'warehouse_id' => Warehouse::factory()->create(['is_leaf' => true])->id,
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'status' => PurchaseOrderStatus::Draft,
            'subtotal' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
        ];
    }
}

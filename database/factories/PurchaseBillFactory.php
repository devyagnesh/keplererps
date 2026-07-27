<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Enums\PurchaseBillStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseBill>
 */
class PurchaseBillFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grn = GoodsReceipt::factory()->create();

        return [
            'document_no' => 'PB-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'document_date' => now()->toDateString(),
            'supplier_id' => $grn->supplier_id,
            'purchase_order_id' => $grn->purchase_order_id,
            'goods_receipt_id' => $grn->id,
            'supplier_bill_no' => 'SB-'.fake()->unique()->numerify('#####'),
            'supplier_bill_date' => now()->toDateString(),
            'status' => PurchaseBillStatus::Draft,
            'match_status' => MatchStatus::Matched,
        ];
    }

    /**
     * Approved bill state.
     */
    public function approved(): self
    {
        return $this->state(fn (): array => [
            'status' => PurchaseBillStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}

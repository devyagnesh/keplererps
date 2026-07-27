<?php

namespace Database\Factories;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\SamplingPlanType;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\QcInspection;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QcInspection>
 */
class QcInspectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = Item::factory()->create();
        $quarantine = Warehouse::factory()->create([
            'warehouse_type' => 'quarantine',
            'is_leaf' => true,
            'is_system' => true,
        ]);
        $target = Warehouse::factory()->create(['is_leaf' => true]);
        $grn = GoodsReceipt::factory()->create(['warehouse_id' => $target->id]);

        return [
            'document_no' => 'QCI-'.fake()->unique()->numerify('#####'),
            'document_date' => now()->toDateString(),
            'inspection_type' => InspectionType::Incoming,
            'status' => InspectionStatus::Pending,
            'source_type' => $grn->getMorphClass(),
            'source_id' => $grn->id,
            'item_id' => $item->id,
            'batch_id' => null,
            'qc_template_id' => null,
            'quarantine_warehouse_id' => $quarantine->id,
            'target_warehouse_id' => $target->id,
            'lot_quantity' => 100,
            'sample_size' => 11,
            'sampling_plan' => SamplingPlanType::SqrtPlusOne,
        ];
    }
}

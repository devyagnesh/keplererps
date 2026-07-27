<?php

namespace Database\Factories;

use App\Models\PackingUnit;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackingUnit>
 */
class PackingUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PU-####')),
            'name' => fake()->randomElement(['Box', 'Carton', 'Bag', 'Crate']).' '.fake()->numberBetween(1, 99),
            'item_id' => null,
            'parent_id' => null,
            'uom_id' => Uom::query()->value('id') ?? Uom::factory(),
            'quantity' => 10,
            'is_active' => true,
            'remarks' => null,
        ];
    }

    /**
     * A unit that nests inside another packing unit.
     */
    public function nestedIn(PackingUnit $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->id,
            'uom_id' => $parent->uom_id,
        ]);
    }
}

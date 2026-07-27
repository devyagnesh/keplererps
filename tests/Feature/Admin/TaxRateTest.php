<?php

namespace Tests\Feature\Admin;

use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for tax rate master.
 */
class TaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_rate_can_be_created(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->postJson(route('admin.tax-rates.store'), [
                'code' => 'GST18',
                'name' => 'GST 18%',
                'cgst_rate' => 9,
                'sgst_rate' => 9,
                'igst_rate' => 18,
                'cess_rate' => 0,
                'is_active' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('tax_rates', ['code' => 'GST18', 'igst_rate' => 18]);
    }

    public function test_tax_rate_with_transactions_cannot_be_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $tax = TaxRate::factory()->create(['has_transactions' => true]);

        $this->actingAs($user)
            ->deleteJson(route('admin.tax-rates.destroy', $tax))
            ->assertStatus(422);
    }
}

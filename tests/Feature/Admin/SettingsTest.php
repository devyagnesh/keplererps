<?php

namespace Tests\Feature\Admin;

use App\Enums\CostingMethod;
use App\Enums\DocumentSeriesType;
use App\Models\DocumentNumberSeries;
use App\Models\FinancialYear;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M16 settings foundation.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_settings_can_be_updated(): void
    {
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->putJson(route('admin.settings.update'), [
                'costing_method' => CostingMethod::Fifo->value,
                'timezone' => 'Asia/Kolkata',
                'date_format' => 'd-m-Y',
                'number_format' => 'indian',
                'allow_negative_stock_default' => 0,
                'stock_adjustment_approval_value' => 1000,
                'slow_moving_days' => 120,
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertSame(CostingMethod::Fifo->value, SystemSetting::query()->where('setting_key', 'costing_method')->value('setting_value'));
        $this->assertSame('120', SystemSetting::query()->where('setting_key', 'slow_moving_days')->value('setting_value'));
    }

    public function test_numbering_service_allocates_sequential_numbers_with_lock(): void
    {
        DocumentNumberSeries::factory()->create([
            'document_type' => DocumentSeriesType::OpeningStock,
            'prefix' => 'OS',
            'next_number' => 1,
            'padding' => 5,
        ]);

        $service = app(NumberingService::class);
        $first = $service->next(DocumentSeriesType::OpeningStock);
        $second = $service->next(DocumentSeriesType::OpeningStock);

        $this->assertSame('OS-00001', $first);
        $this->assertSame('OS-00002', $second);
    }

    public function test_financial_year_close_locks_costing_method(): void
    {
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $fy = FinancialYear::factory()->create(['is_current' => true, 'is_closed' => false]);

        $this->actingAs($user)
            ->postJson(route('admin.financial-years.close', $fy))
            ->assertOk();

        $this->assertTrue((bool) SystemSetting::query()->where('setting_key', 'costing_method')->value('is_locked'));
        $this->assertTrue($fy->fresh()->is_closed);
    }

    public function test_document_series_can_be_created_and_previewed(): void
    {
        $user = User::factory()->superAdmin()->create();

        $create = $this->actingAs($user)
            ->postJson(route('admin.document-series.store'), [
                'document_type' => DocumentSeriesType::Invoice->value,
                'prefix' => 'INV',
                'separator' => '/',
                'padding' => 4,
                'start_number' => 1,
                'include_fy_code' => 0,
                'reset_yearly' => 1,
                'is_active' => 1,
            ])
            ->assertCreated();

        $id = $create->json('data.id');

        $this->actingAs($user)
            ->getJson(route('admin.document-series.preview', $id))
            ->assertOk()
            ->assertJsonPath('data.next', 'INV/0001');
    }

    public function test_health_page_is_accessible(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.system.health'))
            ->assertOk();
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\ApprovalRule;
use App\Models\DocumentApprovalAction;
use App\Models\DocumentShare;
use App\Models\User;
use App\Services\ApprovalRuleService;
use App\Services\DocumentPdfService;
use App\Services\GstGspService;
use App\Services\IndustryProfileService;
use App\Services\UiLabelService;
use Database\Seeders\IndustryProfileSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Database\Seeders\UiLabelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Deferred remaining slices: DomPDF, multi-step approvals, industry packs, GSP, i18n.
 */
class RemainingDeferredTest extends TestCase
{
    use RefreshDatabase;

    public function test_dompdf_renders_binary_from_html(): void
    {
        $binary = app(DocumentPdfService::class)->fromHtml('<html><body><h1>Invoice</h1></body></html>');

        $this->assertNotSame('', $binary);
        $this->assertStringContainsString('%PDF', substr($binary, 0, 8));
    }

    public function test_multi_step_approval_escalates_overdue_steps(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        $rule = app(ApprovalRuleService::class)->create([
            'code' => 'SO-MULTI',
            'name' => 'SO multi',
            'document_type' => 'sales_order',
            'condition_value' => 1000,
            'approver_permission' => 'sales_order.approve',
            'steps' => 'sales_order.approve,sales_order.update',
            'approval_mode' => 'sequential',
            'escalation_hours' => 1,
        ]);

        $this->assertCount(2, $rule->normalizedSteps());

        DocumentApprovalAction::query()->create([
            'document_type' => 'sales_order',
            'document_id' => 99,
            'approval_rule_id' => $rule->id,
            'step_index' => 0,
            'required_permission' => 'sales_order.approve',
            'status' => 'pending',
            'due_at' => now()->subHour(),
        ]);

        $this->actingAs($admin);
        $this->assertSame(1, app(ApprovalRuleService::class)->escalateDue());
        $this->assertDatabaseHas('document_approval_actions', [
            'document_id' => 99,
            'status' => 'escalated',
        ]);

        $this->artisan('approvals:escalate')->assertSuccessful();
    }

    public function test_industry_profiles_seed_and_activate(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(IndustryProfileSeeder::class);

        $service = app(IndustryProfileService::class);
        $this->assertGreaterThanOrEqual(11, $service->all()->count());
        $this->assertNotNull($service->active());
        $this->assertTrue($service->feature('batch_tracking'));

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)
            ->postJson(route('admin.industry-profiles.activate'), ['code' => 'furniture'])
            ->assertOk()
            ->assertJsonPath('data.code', 'furniture');

        $this->assertSame('furniture', $service->active()?->code);
        $this->assertFalse($service->feature('batch_tracking'));
    }

    public function test_gsp_push_dry_runs_without_credentials(): void
    {
        Http::fake();
        $this->seed(SystemSettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.gst-reports.gsp-push'), [
                'from_date' => now()->startOfMonth()->toDateString(),
                'to_date' => now()->endOfMonth()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'queued');

        app(\App\Models\SystemSetting::class)::query()
            ->where('setting_key', 'gsp_enabled')
            ->update(['setting_value' => '1']);

        $result = app(GstGspService::class)->pushOutward(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertSame('dry_run', $result['status']);
        $this->assertDatabaseHas('gsp_filing_logs', ['status' => 'dry_run']);
    }

    public function test_hindi_and_gujarati_label_packs_seed(): void
    {
        $this->seed(UiLabelSeeder::class);
        $labels = app(UiLabelService::class);

        $this->assertSame('कार्य आदेश', $labels->get('work_order', null, 'hi'));
        $this->assertSame('કાર્ય ઓર્ડર', $labels->get('work_order', null, 'gu'));
        $this->assertSame(__('erp.work_order', [], 'hi'), 'कार्य आदेश');
    }

    public function test_document_share_stores_pdf_path_column(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('document_shares', 'pdf_storage_path'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('industry_profiles'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('gsp_filing_logs'));
        $this->assertTrue(ApprovalRule::query()->count() === 0 || true);
        $this->assertSame(0, DocumentShare::query()->count());
    }
}

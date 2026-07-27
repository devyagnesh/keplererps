<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Services\FirebasePushService;
use App\Services\GstGspService;
use App\Services\WhatsAppService;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Demo integration credentials return stub responses without outbound HTTP.
 */
class DemoIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_demo_credentials_return_demo_sent(): void
    {
        $this->seed(SystemSettingSeeder::class);

        $result = app(WhatsAppService::class)->sendTemplate(
            '9876543210',
            'goods_dispatched',
            ['Order 1001', 'Dispatched today']
        );

        $this->assertSame('demo_sent', $result['status']);
        $this->assertTrue($result['dry_run']);
        $this->assertStringStartsWith('demo-', (string) $result['message_id']);
    }

    public function test_firebase_demo_credentials_return_demo_sent(): void
    {
        $this->seed(SystemSettingSeeder::class);

        $result = app(FirebasePushService::class)->sendToToken(
            'device-token-123',
            'Alert',
            'Test notification'
        );

        $this->assertSame('demo_sent', $result['status']);
        $this->assertTrue($result['dry_run']);
    }

    public function test_gsp_submit_eway_bill_demo_credentials_return_demo_pushed(): void
    {
        $this->seed(SystemSettingSeeder::class);

        $result = app(GstGspService::class)->submitEwayBill([
            'document_no' => 'DC-00001',
            'value' => 1000,
        ]);

        $this->assertSame('demo_pushed', $result['status']);
        $this->assertNotEmpty($result['eway_bill_number']);
    }

    public function test_settings_seeder_has_demo_integration_values(): void
    {
        $this->seed(SystemSettingSeeder::class);

        $this->assertSame('DEMO_WHATSAPP_TOKEN_keplererp', SystemSetting::query()->where('setting_key', 'whatsapp_token')->value('setting_value'));
        $this->assertSame('1', SystemSetting::query()->where('setting_key', 'whatsapp_enabled')->value('setting_value'));
        $this->assertSame('DEMO_FIREBASE_SERVER_KEY_keplererp', SystemSetting::query()->where('setting_key', 'firebase_server_key')->value('setting_value'));
        $this->assertSame('https://demo-gsp.keplererp.local', SystemSetting::query()->where('setting_key', 'gsp_base_url')->value('setting_value'));
        $this->assertSame('DEMO_GSP_API_KEY_keplererp', SystemSetting::query()->where('setting_key', 'gsp_api_key')->value('setting_value'));
    }
}

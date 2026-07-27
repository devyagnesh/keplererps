<?php

namespace Database\Seeders;

use App\Enums\CostingMethod;
use App\Enums\NumberFormat;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds default M16 system settings.
 */
class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['group_key' => 'industry', 'setting_key' => 'costing_method', 'setting_value' => CostingMethod::WeightedAverage->value, 'value_type' => 'string', 'label' => 'Costing method', 'is_locked' => false],
            ['group_key' => 'inventory', 'setting_key' => 'allow_negative_stock_default', 'setting_value' => '0', 'value_type' => 'boolean', 'label' => 'Default allow negative stock', 'is_locked' => false],
            ['group_key' => 'inventory', 'setting_key' => 'stock_adjustment_approval_value', 'setting_value' => '0', 'value_type' => 'string', 'label' => 'Adjustment approval threshold', 'is_locked' => false],
            ['group_key' => 'inventory', 'setting_key' => 'slow_moving_days', 'setting_value' => '90', 'value_type' => 'integer', 'label' => 'Slow-moving days', 'is_locked' => false],
            ['group_key' => 'purchase', 'setting_key' => 'purchase_bill_rate_tolerance_percent', 'setting_value' => '1', 'value_type' => 'string', 'label' => 'Purchase bill rate tolerance %', 'is_locked' => false],
            ['group_key' => 'purchase', 'setting_key' => 'purchase_bill_qty_tolerance_percent', 'setting_value' => '0', 'value_type' => 'string', 'label' => 'Purchase bill quantity tolerance %', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_receivable', 'setting_value' => '1200', 'value_type' => 'string', 'label' => 'Accounts receivable control account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_payable', 'setting_value' => '2100', 'value_type' => 'string', 'label' => 'Accounts payable control account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_sales', 'setting_value' => '4100', 'value_type' => 'string', 'label' => 'Sales revenue account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_purchase', 'setting_value' => '5100', 'value_type' => 'string', 'label' => 'Purchases account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_output_cgst', 'setting_value' => '2210', 'value_type' => 'string', 'label' => 'Output CGST account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_output_sgst', 'setting_value' => '2220', 'value_type' => 'string', 'label' => 'Output SGST account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_output_igst', 'setting_value' => '2230', 'value_type' => 'string', 'label' => 'Output IGST account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_input_cgst', 'setting_value' => '1310', 'value_type' => 'string', 'label' => 'Input CGST account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_input_sgst', 'setting_value' => '1320', 'value_type' => 'string', 'label' => 'Input SGST account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_input_igst', 'setting_value' => '1330', 'value_type' => 'string', 'label' => 'Input IGST account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_round_off', 'setting_value' => '5900', 'value_type' => 'string', 'label' => 'Round-off account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_salary_expense', 'setting_value' => '5300', 'value_type' => 'string', 'label' => 'Salary expense account code', 'is_locked' => false],
            ['group_key' => 'finance', 'setting_key' => 'control_account_salary_payable', 'setting_value' => '2300', 'value_type' => 'string', 'label' => 'Salary payable account code', 'is_locked' => false],
            ['group_key' => 'localisation', 'setting_key' => 'timezone', 'setting_value' => 'Asia/Kolkata', 'value_type' => 'string', 'label' => 'Timezone', 'is_locked' => false],
            ['group_key' => 'localisation', 'setting_key' => 'date_format', 'setting_value' => 'd-m-Y', 'value_type' => 'string', 'label' => 'Date format', 'is_locked' => false],
            ['group_key' => 'localisation', 'setting_key' => 'number_format', 'setting_value' => NumberFormat::Indian->value, 'value_type' => 'string', 'label' => 'Number format', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_enabled', 'setting_value' => '1', 'value_type' => 'boolean', 'label' => 'Enable WhatsApp', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_token', 'setting_value' => 'DEMO_WHATSAPP_TOKEN_keplererp', 'value_type' => 'string', 'label' => 'WhatsApp access token', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_phone_number_id', 'setting_value' => 'DEMO_PHONE_ID_100000000000000', 'value_type' => 'string', 'label' => 'WhatsApp phone number ID', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_api_version', 'setting_value' => 'v19.0', 'value_type' => 'string', 'label' => 'WhatsApp Graph API version', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_verify_token', 'setting_value' => 'kepler-whatsapp-verify', 'value_type' => 'string', 'label' => 'WhatsApp webhook verify token', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'firebase_enabled', 'setting_value' => '1', 'value_type' => 'boolean', 'label' => 'Enable Firebase push', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'firebase_server_key', 'setting_value' => 'DEMO_FIREBASE_SERVER_KEY_keplererp', 'value_type' => 'string', 'label' => 'Firebase server key', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'einvoice_enabled', 'setting_value' => '1', 'value_type' => 'boolean', 'label' => 'Enable e-invoice push', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'einvoice_base_url', 'setting_value' => 'https://demo-einvoice.keplererp.local', 'value_type' => 'string', 'label' => 'E-invoice API base URL', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'einvoice_api_key', 'setting_value' => 'DEMO_EINVOICE_API_KEY_keplererp', 'value_type' => 'string', 'label' => 'E-invoice API key', 'is_locked' => false],
            ['group_key' => 'dashboard', 'setting_key' => 'dashboard_show_pending_approvals', 'setting_value' => '1', 'value_type' => 'boolean', 'label' => 'Show pending approvals widget', 'is_locked' => false],
            ['group_key' => 'dashboard', 'setting_key' => 'dashboard_show_overdue_crm', 'setting_value' => '1', 'value_type' => 'boolean', 'label' => 'Show overdue CRM widget', 'is_locked' => false],
            ['group_key' => 'industry', 'setting_key' => 'industry_profile_code', 'setting_value' => 'pvc_pipes', 'value_type' => 'string', 'label' => 'Active industry profile code', 'is_locked' => false],
            ['group_key' => 'localisation', 'setting_key' => 'ui_locale', 'setting_value' => 'en', 'value_type' => 'string', 'label' => 'UI locale (en|hi|gu)', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'gsp_enabled', 'setting_value' => '1', 'value_type' => 'boolean', 'label' => 'Enable GST GSP filing', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'gsp_base_url', 'setting_value' => 'https://demo-gsp.keplererp.local', 'value_type' => 'string', 'label' => 'GSP API base URL', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'gsp_api_key', 'setting_value' => 'DEMO_GSP_API_KEY_keplererp', 'value_type' => 'string', 'label' => 'GSP API key', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'gsp_gstin', 'setting_value' => '24AABCU9603R1ZM', 'value_type' => 'string', 'label' => 'Company GSTIN for GSP', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_template_dispatch', 'setting_value' => 'goods_dispatched', 'value_type' => 'string', 'label' => 'WhatsApp template for dispatch alerts', 'is_locked' => false],
            ['group_key' => 'integrations', 'setting_key' => 'whatsapp_template_salary_slip', 'setting_value' => 'salary_slip', 'value_type' => 'string', 'label' => 'WhatsApp template for salary slips', 'is_locked' => false],
        ];

        foreach ($rows as $row) {
            SystemSetting::query()->updateOrCreate(
                ['setting_key' => $row['setting_key']],
                $row
            );
        }
    }
}

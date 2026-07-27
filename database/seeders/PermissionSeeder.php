<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeds module.action permissions for every ERP module shipped to date (M01–M17).
 *
 * New modules must append their permissions here and map them in EnsureUserHasPermission.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'company' => ['view', 'update'],
            'branch' => ['view', 'create', 'update', 'delete'],
            'warehouse' => ['view', 'create', 'update', 'delete'],
            'party' => ['view', 'create', 'update', 'delete', 'export'],
            'tax_rate' => ['view', 'create', 'update', 'delete'],
            'uom' => ['view', 'create', 'update', 'delete'],
            'category' => ['view', 'create', 'update', 'delete'],
            'transporter' => ['view', 'create', 'update', 'delete'],
            'hsn_code' => ['view', 'create', 'update', 'delete'],
            'item' => ['view', 'create', 'update', 'delete'],
            'bom' => ['view', 'create', 'update', 'delete'],
            'purchase_order' => ['view', 'create', 'update', 'delete', 'approve'],
            'goods_receipt' => ['view', 'create', 'update', 'delete', 'post'],
            'purchase_bill' => ['view', 'create', 'update', 'delete', 'approve', 'approve_mismatch'],
            'purchase_return' => ['view', 'create', 'update', 'delete', 'post'],
            'sales_return' => ['view', 'create', 'update', 'delete', 'post'],
            'purchase_suggestion' => ['view'],
            'purchase_indent' => ['view', 'create', 'update', 'approve'],
            'purchase_rfq' => ['view', 'create', 'update', 'approve'],
            'lead' => ['view', 'create', 'update', 'delete'],
            'opportunity' => ['view', 'create', 'update', 'delete'],
            'sales_quotation' => ['view', 'create', 'update', 'delete'],
            'sales_order' => ['view', 'create', 'update', 'delete', 'approve'],
            'sales_invoice' => ['view', 'create', 'update', 'delete'],
            'delivery_challan' => ['view', 'create', 'update', 'delete'],
            'packing_unit' => ['view', 'create', 'update', 'delete'],
            'package' => ['view', 'create', 'delete', 'scan'],
            'production_plan' => ['view', 'create', 'update', 'delete', 'post'],
            'work_order' => ['view', 'create', 'update', 'delete'],
            'shop_floor' => ['view'],
            'production_entry' => ['view', 'create', 'update', 'delete', 'post'],
            'qc_template' => ['view', 'create', 'update', 'delete'],
            'qc_inspection' => ['view', 'create', 'update', 'delete', 'approve_deviation'],
            'work_centre' => ['view', 'create', 'update', 'delete'],
            'maintenance_order' => ['view', 'create', 'update', 'delete'],
            'opening_stock' => ['view', 'create', 'update', 'delete', 'post'],
            'stock_adjustment' => ['view', 'create', 'update', 'delete', 'post'],
            'stock_transfer' => ['view', 'create', 'update', 'delete', 'post'],
            'stock_balance' => ['view'],
            'stock_ledger' => ['view'],
            'ledger_account' => ['view', 'create', 'update', 'delete'],
            'journal_voucher' => ['view', 'create', 'update', 'delete', 'post'],
            'voucher_allocation' => ['view', 'update'],
            'period_lock' => ['view', 'create', 'override'],
            'price_list' => ['view', 'create', 'update', 'delete'],
            'stock_take' => ['view', 'create', 'update', 'post'],
            'supplier_rating' => ['view', 'create'],
            'crm_report' => ['view'],
            'qc_report' => ['view'],
            'custom_field' => ['view', 'create', 'update', 'delete'],
            'approval_rule' => ['view', 'create', 'update', 'delete'],
            'print_template' => ['view', 'create', 'update', 'delete'],
            'terms_block' => ['view', 'create', 'update', 'delete'],
            'ui_label' => ['view', 'create', 'update'],
            'industry_profile' => ['view', 'update'],
            'integration' => ['view', 'update'],
            'batch_recall' => ['update'],
            'finance_report' => ['view'],
            'gst_report' => ['view', 'create', 'update'],
            'bank_reconciliation' => ['view', 'update'],
            'holiday' => ['view', 'create', 'update'],
            'backup' => ['view', 'create', 'update'],
            'recycle_bin' => ['view', 'update'],
            'report' => ['view'],
            'scheduled_report' => ['view', 'create', 'delete'],
            'scan_exception' => ['view', 'update'],
            'shift' => ['view', 'create', 'update', 'delete'],
            'employee' => ['view', 'create', 'update', 'delete'],
            'attendance' => ['view', 'create'],
            'salary_run' => ['view', 'create', 'update', 'delete', 'post'],
            'notification_rule' => ['view', 'create', 'update', 'delete'],
            'activity_log' => ['view'],
            'setting' => ['view', 'update'],
            'financial_year' => ['view', 'create', 'update', 'delete', 'close'],
            'document_series' => ['view', 'create', 'update', 'delete'],
            'system' => ['view', 'maintain'],
            'user' => ['view', 'create', 'update', 'delete'],
            'role' => ['view', 'create', 'update', 'delete'],
        ];

        $sort = 0;
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $sort++;
                $name = $module.'.'.$action;
                Permission::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'module_group' => $module,
                        'label' => ucfirst(str_replace('_', ' ', $module)).' '.ucfirst($action),
                        'sort_order' => $sort,
                        'is_dangerous' => in_array($action, ['delete', 'export'], true),
                    ]
                );
            }
        }
    }
}

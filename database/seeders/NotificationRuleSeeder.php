<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Models\NotificationRule;
use Illuminate\Database\Seeder;

/**
 * Seeds the default in-app notification rule catalogue (M16).
 */
class NotificationRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'code' => 'PO_APPROVED_ADMIN',
                'name' => 'PO approved — admins',
                'event' => NotificationEvent::PurchaseOrderApproved,
                'recipient_type' => NotificationRecipientType::Role,
                'recipient_value' => 'admin',
                'subject_template' => 'PO {{document_no}} approved',
                'body_template' => 'Purchase order {{document_no}} for {{party_name}} was approved.',
                'sort_order' => 10,
            ],
            [
                'code' => 'GRN_POSTED_PURCHASE',
                'name' => 'GRN posted — purchase viewers',
                'event' => NotificationEvent::GoodsReceiptPosted,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'goods_receipt.view',
                'subject_template' => 'GRN {{document_no}} posted',
                'body_template' => 'Goods receipt {{document_no}} was posted into stock.',
                'sort_order' => 20,
            ],
            [
                'code' => 'INV_CONFIRMED_SALES',
                'name' => 'Invoice confirmed — sales viewers',
                'event' => NotificationEvent::SalesInvoiceConfirmed,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'sales_invoice.view',
                'subject_template' => 'Invoice {{document_no}} confirmed',
                'body_template' => 'Sales invoice {{document_no}} for {{party_name}} was confirmed.',
                'sort_order' => 30,
            ],
            [
                'code' => 'CHALLAN_DISPATCHED',
                'name' => 'Challan dispatched — sales viewers',
                'event' => NotificationEvent::DeliveryChallanDispatched,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'delivery_challan.view',
                'subject_template' => 'Challan {{document_no}} dispatched',
                'body_template' => 'Delivery challan {{document_no}} was marked dispatched.',
                'sort_order' => 40,
            ],
            [
                'code' => 'WO_COMPLETED',
                'name' => 'WO completed — production viewers',
                'event' => NotificationEvent::WorkOrderCompleted,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'work_order.view',
                'subject_template' => 'Work order {{document_no}} completed',
                'body_template' => 'Work order {{document_no}} for item {{item_code}} is complete.',
                'sort_order' => 50,
            ],
            [
                'code' => 'QC_FAILED',
                'name' => 'QC failed — QC viewers',
                'event' => NotificationEvent::QcInspectionFailed,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'qc_inspection.view',
                'subject_template' => 'QC failed on {{document_no}}',
                'body_template' => 'Inspection {{document_no}} failed and may need disposition.',
                'sort_order' => 60,
            ],
            [
                'code' => 'MAINT_OPENED',
                'name' => 'Breakdown opened — maintenance viewers',
                'event' => NotificationEvent::MaintenanceOrderOpened,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'maintenance_order.view',
                'subject_template' => 'Maintenance {{document_no}} opened',
                'body_template' => 'A maintenance order was opened for {{asset_code}}.',
                'sort_order' => 70,
            ],
            [
                'code' => 'SALARY_POSTED',
                'name' => 'Salary posted — finance viewers',
                'event' => NotificationEvent::SalaryRunPosted,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'salary_run.view',
                'subject_template' => 'Salary run {{document_no}} posted',
                'body_template' => 'Payroll for {{period}} was posted. Net payable {{net_total}}.',
                'sort_order' => 80,
            ],
            [
                'code' => 'LEAD_CONVERTED',
                'name' => 'Lead converted — CRM viewers',
                'event' => NotificationEvent::LeadConverted,
                'recipient_type' => NotificationRecipientType::Permission,
                'recipient_value' => 'lead.view',
                'subject_template' => 'Lead {{lead_name}} converted',
                'body_template' => 'Lead {{lead_name}} was converted to customer {{party_name}}.',
                'sort_order' => 90,
            ],
        ];

        foreach ($rules as $rule) {
            NotificationRule::query()->updateOrCreate(
                ['code' => $rule['code']],
                [
                    'name' => $rule['name'],
                    'event' => $rule['event']->value,
                    'channel' => NotificationChannel::InApp->value,
                    'recipient_type' => $rule['recipient_type']->value,
                    'recipient_value' => $rule['recipient_value'],
                    'subject_template' => $rule['subject_template'],
                    'body_template' => $rule['body_template'],
                    'is_active' => true,
                    'is_system' => true,
                    'sort_order' => $rule['sort_order'],
                ]
            );
        }
    }
}

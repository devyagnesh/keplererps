<?php

namespace App\Enums;

/**
 * Catalogue of business events that can raise in-app notifications (M16).
 */
enum NotificationEvent: string
{
    case PurchaseOrderApproved = 'purchase_order.approved';
    case GoodsReceiptPosted = 'goods_receipt.posted';
    case SalesInvoiceConfirmed = 'sales_invoice.confirmed';
    case DeliveryChallanDispatched = 'delivery_challan.dispatched';
    case WorkOrderCompleted = 'work_order.completed';
    case QcInspectionFailed = 'qc_inspection.failed';
    case MaintenanceOrderOpened = 'maintenance_order.opened';
    case SalaryRunPosted = 'salary_run.posted';
    case LeadConverted = 'lead.converted';
    case GoodsDispatchedCustomer = 'goods.dispatched';
    case SalarySlipGenerated = 'salary_slip.generated';

    /**
     * Human-readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::PurchaseOrderApproved => 'Purchase order approved',
            self::GoodsReceiptPosted => 'Goods receipt posted',
            self::SalesInvoiceConfirmed => 'Sales invoice confirmed',
            self::DeliveryChallanDispatched => 'Delivery challan dispatched',
            self::WorkOrderCompleted => 'Work order completed',
            self::QcInspectionFailed => 'QC inspection failed',
            self::MaintenanceOrderOpened => 'Maintenance order opened',
            self::SalaryRunPosted => 'Salary run posted',
            self::LeadConverted => 'Lead converted',
            self::GoodsDispatchedCustomer => 'Goods dispatched (customer)',
            self::SalarySlipGenerated => 'Salary slip generated',
        };
    }

    /**
     * Module group for filtering on the catalogue screen.
     */
    public function module(): string
    {
        return match ($this) {
            self::PurchaseOrderApproved, self::GoodsReceiptPosted => 'Purchase',
            self::SalesInvoiceConfirmed, self::DeliveryChallanDispatched, self::GoodsDispatchedCustomer => 'Sales',
            self::WorkOrderCompleted => 'Production',
            self::QcInspectionFailed => 'Quality',
            self::MaintenanceOrderOpened => 'Maintenance',
            self::SalaryRunPosted, self::SalarySlipGenerated => 'HR',
            self::LeadConverted => 'CRM',
        };
    }
}

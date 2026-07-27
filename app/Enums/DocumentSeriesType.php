<?php

namespace App\Enums;

/**
 * Document types that allocate numbers through the numbering engine (C2).
 */
enum DocumentSeriesType: string
{
    case Item = 'item';
    case Party = 'party';
    case OpeningStock = 'opening_stock';
    case StockAdjustment = 'stock_adjustment';
    case StockTransfer = 'stock_transfer';
    case SalesOrder = 'sales_order';
    case Quotation = 'quotation';
    case PurchaseOrder = 'purchase_order';
    case Grn = 'grn';
    case PurchaseBill = 'purchase_bill';
    case PurchaseReturn = 'purchase_return';
    case SalesReturn = 'sales_return';
    case Invoice = 'invoice';
    case DeliveryChallan = 'delivery_challan';
    case WorkOrder = 'work_order';
    case ProductionPlan = 'production_plan';
    case ProductionEntry = 'production_entry';
    case Bom = 'bom';
    case QcInspection = 'qc_inspection';
    case MaintenanceOrder = 'maintenance_order';
    case JournalVoucher = 'journal_voucher';
    case Receipt = 'receipt';
    case Payment = 'payment';
    case Lead = 'lead';
    case Opportunity = 'opportunity';
    case PackageLabel = 'package_label';
    case Employee = 'employee';
    case SalaryRun = 'salary_run';
    case PurchaseIndent = 'purchase_indent';
    case PurchaseRfq = 'purchase_rfq';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Item => 'Item',
            self::Party => 'Party',
            self::OpeningStock => 'Opening Stock',
            self::StockAdjustment => 'Stock Adjustment',
            self::StockTransfer => 'Stock Transfer',
            self::SalesOrder => 'Sales Order',
            self::Quotation => 'Quotation',
            self::PurchaseOrder => 'Purchase Order',
            self::Grn => 'GRN',
            self::PurchaseBill => 'Purchase Bill',
            self::PurchaseReturn => 'Purchase Return',
            self::SalesReturn => 'Sales Return',
            self::Invoice => 'Invoice',
            self::DeliveryChallan => 'Delivery Challan',
            self::WorkOrder => 'Work Order',
            self::ProductionPlan => 'Production Plan',
            self::ProductionEntry => 'Production Entry',
            self::Bom => 'BOM',
            self::QcInspection => 'QC Inspection',
            self::MaintenanceOrder => 'Maintenance Order',
            self::JournalVoucher => 'Journal Voucher',
            self::Receipt => 'Receipt Voucher',
            self::Payment => 'Payment Voucher',
            self::Lead => 'Lead',
            self::Opportunity => 'Opportunity',
            self::PackageLabel => 'Package Label',
            self::Employee => 'Employee',
            self::SalaryRun => 'Salary Run',
            self::PurchaseIndent => 'Purchase Indent',
            self::PurchaseRfq => 'Purchase RFQ',
        };
    }

    public function defaultPrefix(): string
    {
        return match ($this) {
            self::Item => 'ITM',
            self::Party => 'PTY',
            self::OpeningStock => 'OS',
            self::StockAdjustment => 'ADJ',
            self::StockTransfer => 'TRF',
            self::SalesOrder => 'SO',
            self::Quotation => 'QT',
            self::PurchaseOrder => 'PO',
            self::Grn => 'GRN',
            self::PurchaseBill => 'PB',
            self::PurchaseReturn => 'PR',
            self::SalesReturn => 'SR',
            self::Invoice => 'INV',
            self::DeliveryChallan => 'DC',
            self::WorkOrder => 'WO',
            self::ProductionPlan => 'PP',
            self::ProductionEntry => 'PE',
            self::Bom => 'BOM',
            self::QcInspection => 'QCI',
            self::MaintenanceOrder => 'MO',
            self::JournalVoucher => 'JV',
            self::Receipt => 'RV',
            self::Payment => 'PV',
            self::Lead => 'LD',
            self::Opportunity => 'OPP',
            self::PackageLabel => 'PKG',
            self::Employee => 'EMP',
            self::SalaryRun => 'PAY',
            self::PurchaseIndent => 'IND',
            self::PurchaseRfq => 'RFQ',
        };
    }
}

<?php

namespace App\Enums;

/**
 * Source transaction types that post stock ledger rows.
 */
enum StockTransactionType: string
{
    case OpeningStock = 'opening_stock';
    case GoodsReceipt = 'goods_receipt';
    case PurchaseReturn = 'purchase_return';
    case MaterialIssue = 'material_issue';
    case ProductionReceipt = 'production_receipt';
    case ScrapReceipt = 'scrap_receipt';
    case MaterialReturn = 'material_return';
    case Delivery = 'delivery';
    case SalesReturn = 'sales_return';
    case StockTransferOut = 'stock_transfer_out';
    case StockTransferIn = 'stock_transfer_in';
    case StockAdjustment = 'stock_adjustment';
    case JobWorkIssue = 'job_work_issue';
    case JobWorkReceipt = 'job_work_receipt';
    case MaintenanceIssue = 'maintenance_issue';
    case Reversal = 'reversal';

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
            self::OpeningStock => 'Opening Stock',
            self::GoodsReceipt => 'Goods Receipt',
            self::PurchaseReturn => 'Purchase Return',
            self::MaterialIssue => 'Material Issue',
            self::ProductionReceipt => 'Production Receipt',
            self::ScrapReceipt => 'Scrap Receipt',
            self::MaterialReturn => 'Material Return',
            self::Delivery => 'Delivery / Invoice',
            self::SalesReturn => 'Sales Return',
            self::StockTransferOut => 'Stock Transfer Out',
            self::StockTransferIn => 'Stock Transfer In',
            self::StockAdjustment => 'Stock Adjustment',
            self::JobWorkIssue => 'Job Work Issue',
            self::JobWorkReceipt => 'Job Work Receipt',
            self::MaintenanceIssue => 'Maintenance Issue',
            self::Reversal => 'Reversal',
        };
    }
}

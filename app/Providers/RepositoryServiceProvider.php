<?php

namespace App\Providers;

use App\Repositories\Eloquent\BomRepository;
use App\Repositories\Eloquent\BranchRepository;
use App\Repositories\Eloquent\DeliveryChallanRepository;
use App\Repositories\Eloquent\MaintenanceOrderRepository;
use App\Repositories\Eloquent\NotificationRuleRepository;
use App\Repositories\Eloquent\ProductionEntryRepository;
use App\Repositories\Eloquent\JournalVoucherRepository;
use App\Repositories\Eloquent\LeadRepository;
use App\Repositories\Eloquent\LedgerAccountRepository;
use App\Repositories\Eloquent\OpportunityRepository;
use App\Repositories\Eloquent\PackageLabelRepository;
use App\Repositories\Eloquent\PackingUnitRepository;
use App\Repositories\Eloquent\EmployeeRepository;
use App\Repositories\Eloquent\SalaryRunRepository;
use App\Repositories\Eloquent\ProductionPlanRepository;
use App\Repositories\Eloquent\QcInspectionRepository;
use App\Repositories\Eloquent\QcTemplateRepository;
use App\Repositories\Eloquent\WorkCentreRepository;
use App\Repositories\Eloquent\WorkOrderRepository;
use App\Repositories\Eloquent\GoodsReceiptRepository;
use App\Repositories\Eloquent\PurchaseBillRepository;
use App\Repositories\Eloquent\PurchaseReturnRepository;
use App\Repositories\Eloquent\SalesReturnRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CompanyRepository;
use App\Repositories\Eloquent\DocumentNumberSeriesRepository;
use App\Repositories\Eloquent\FinancialYearRepository;
use App\Repositories\Eloquent\HsnCodeRepository;
use App\Repositories\Eloquent\ItemRepository;
use App\Repositories\Eloquent\OpeningStockRepository;
use App\Repositories\Eloquent\PartyRepository;
use App\Repositories\Eloquent\PurchaseOrderRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\SalesInvoiceRepository;
use App\Repositories\Eloquent\SalesOrderRepository;
use App\Repositories\Eloquent\SalesQuotationRepository;
use App\Repositories\Eloquent\StockAdjustmentRepository;
use App\Repositories\Eloquent\StockBalanceRepository;
use App\Repositories\Eloquent\StockTransferRepository;
use App\Repositories\Eloquent\SystemSettingRepository;
use App\Repositories\Eloquent\TaxRateRepository;
use App\Repositories\Eloquent\TransporterRepository;
use App\Repositories\Eloquent\UomRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use App\Repositories\Interfaces\BomRepositoryInterface;
use App\Repositories\Interfaces\BranchRepositoryInterface;
use App\Repositories\Interfaces\DeliveryChallanRepositoryInterface;
use App\Repositories\Interfaces\MaintenanceOrderRepositoryInterface;
use App\Repositories\Interfaces\NotificationRuleRepositoryInterface;
use App\Repositories\Interfaces\ProductionEntryRepositoryInterface;
use App\Repositories\Interfaces\JournalVoucherRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Repositories\Interfaces\LedgerAccountRepositoryInterface;
use App\Repositories\Interfaces\OpportunityRepositoryInterface;
use App\Repositories\Interfaces\PackageLabelRepositoryInterface;
use App\Repositories\Interfaces\PackingUnitRepositoryInterface;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use App\Repositories\Interfaces\SalaryRunRepositoryInterface;
use App\Repositories\Interfaces\ProductionPlanRepositoryInterface;
use App\Repositories\Interfaces\QcInspectionRepositoryInterface;
use App\Repositories\Interfaces\QcTemplateRepositoryInterface;
use App\Repositories\Interfaces\WorkCentreRepositoryInterface;
use App\Repositories\Interfaces\WorkOrderRepositoryInterface;
use App\Repositories\Interfaces\GoodsReceiptRepositoryInterface;
use App\Repositories\Interfaces\PurchaseBillRepositoryInterface;
use App\Repositories\Interfaces\PurchaseReturnRepositoryInterface;
use App\Repositories\Interfaces\SalesReturnRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use App\Repositories\Interfaces\DocumentNumberSeriesRepositoryInterface;
use App\Repositories\Interfaces\FinancialYearRepositoryInterface;
use App\Repositories\Interfaces\HsnCodeRepositoryInterface;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use App\Repositories\Interfaces\OpeningStockRepositoryInterface;
use App\Repositories\Interfaces\PartyRepositoryInterface;
use App\Repositories\Interfaces\PurchaseOrderRepositoryInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\SalesInvoiceRepositoryInterface;
use App\Repositories\Interfaces\SalesOrderRepositoryInterface;
use App\Repositories\Interfaces\SalesQuotationRepositoryInterface;
use App\Repositories\Interfaces\StockAdjustmentRepositoryInterface;
use App\Repositories\Interfaces\StockBalanceRepositoryInterface;
use App\Repositories\Interfaces\StockTransferRepositoryInterface;
use App\Repositories\Interfaces\SystemSettingRepositoryInterface;
use App\Repositories\Interfaces\TaxRateRepositoryInterface;
use App\Repositories\Interfaces\TransporterRepositoryInterface;
use App\Repositories\Interfaces\UomRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository interfaces to their Eloquent implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(BranchRepositoryInterface::class, BranchRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(PartyRepositoryInterface::class, PartyRepository::class);
        $this->app->bind(TaxRateRepositoryInterface::class, TaxRateRepository::class);
        $this->app->bind(UomRepositoryInterface::class, UomRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(TransporterRepositoryInterface::class, TransporterRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(HsnCodeRepositoryInterface::class, HsnCodeRepository::class);
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
        $this->app->bind(BomRepositoryInterface::class, BomRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(GoodsReceiptRepositoryInterface::class, GoodsReceiptRepository::class);
        $this->app->bind(PurchaseBillRepositoryInterface::class, PurchaseBillRepository::class);
        $this->app->bind(PurchaseReturnRepositoryInterface::class, PurchaseReturnRepository::class);
        $this->app->bind(SalesReturnRepositoryInterface::class, SalesReturnRepository::class);
        $this->app->bind(SalesQuotationRepositoryInterface::class, SalesQuotationRepository::class);
        $this->app->bind(SalesOrderRepositoryInterface::class, SalesOrderRepository::class);
        $this->app->bind(SalesInvoiceRepositoryInterface::class, SalesInvoiceRepository::class);
        $this->app->bind(DeliveryChallanRepositoryInterface::class, DeliveryChallanRepository::class);
        $this->app->bind(WorkOrderRepositoryInterface::class, WorkOrderRepository::class);
        $this->app->bind(ProductionPlanRepositoryInterface::class, ProductionPlanRepository::class);
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(OpportunityRepositoryInterface::class, OpportunityRepository::class);
        $this->app->bind(PackingUnitRepositoryInterface::class, PackingUnitRepository::class);
        $this->app->bind(PackageLabelRepositoryInterface::class, PackageLabelRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(SalaryRunRepositoryInterface::class, SalaryRunRepository::class);
        $this->app->bind(LedgerAccountRepositoryInterface::class, LedgerAccountRepository::class);
        $this->app->bind(JournalVoucherRepositoryInterface::class, JournalVoucherRepository::class);
        $this->app->bind(ProductionEntryRepositoryInterface::class, ProductionEntryRepository::class);
        $this->app->bind(QcTemplateRepositoryInterface::class, QcTemplateRepository::class);
        $this->app->bind(QcInspectionRepositoryInterface::class, QcInspectionRepository::class);
        $this->app->bind(WorkCentreRepositoryInterface::class, WorkCentreRepository::class);
        $this->app->bind(MaintenanceOrderRepositoryInterface::class, MaintenanceOrderRepository::class);
        $this->app->bind(OpeningStockRepositoryInterface::class, OpeningStockRepository::class);
        $this->app->bind(StockAdjustmentRepositoryInterface::class, StockAdjustmentRepository::class);
        $this->app->bind(StockTransferRepositoryInterface::class, StockTransferRepository::class);
        $this->app->bind(StockBalanceRepositoryInterface::class, StockBalanceRepository::class);
        $this->app->bind(SystemSettingRepositoryInterface::class, SystemSettingRepository::class);
        $this->app->bind(FinancialYearRepositoryInterface::class, FinancialYearRepository::class);
        $this->app->bind(DocumentNumberSeriesRepositoryInterface::class, DocumentNumberSeriesRepository::class);
        $this->app->bind(NotificationRuleRepositoryInterface::class, NotificationRuleRepository::class);
    }
}

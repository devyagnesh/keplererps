<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\BomController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\Gstr2bImportController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\PurchaseIndentController;
use App\Http\Controllers\Admin\RecycleBinController;
use App\Http\Controllers\Admin\CrmReportController;
use App\Http\Controllers\Admin\CustomizationController;
use App\Http\Controllers\Admin\GoodsReceiptController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DashboardWidgetController;
use App\Http\Controllers\Admin\DocumentNumberSeriesController;
use App\Http\Controllers\Admin\FinanceReportController;
use App\Http\Controllers\Admin\FinancialYearController;
use App\Http\Controllers\Admin\GstReportController;
use App\Http\Controllers\Admin\IndustryProfileController;
use App\Http\Controllers\Admin\JournalVoucherController;
use App\Http\Controllers\Admin\LedgerAccountController;
use App\Http\Controllers\Admin\HsnCodeController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\OpeningStockController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PackingUnitController;
use App\Http\Controllers\Admin\PartyController;
use App\Http\Controllers\Admin\PartyImportController;
use App\Http\Controllers\Admin\PeriodLockController;
use App\Http\Controllers\Admin\PriceListController;
use App\Http\Controllers\Admin\PublicVerifyController;
use App\Http\Controllers\Admin\PurchaseBillController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseRfqController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\RemainingPriorityController;
use App\Http\Controllers\Admin\SalesReturnController;
use App\Http\Controllers\Admin\PurchaseSuggestionController;
use App\Http\Controllers\Admin\QcReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\SalesQuotationController;
use App\Http\Controllers\Admin\DeliveryChallanController;
use App\Http\Controllers\Admin\EinvoiceController;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\PublicDocumentShareController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\Admin\MaintenanceOrderController;
use App\Http\Controllers\Admin\NotificationRuleController;
use App\Http\Controllers\Admin\InboxNotificationController;
use App\Http\Controllers\Admin\ProductionEntryController;
use App\Http\Controllers\Admin\ProductionPlanController;
use App\Http\Controllers\Admin\QcInspectionController;
use App\Http\Controllers\Admin\RegisterReportController;
use App\Http\Controllers\Admin\ScanExceptionController;
use App\Http\Controllers\Admin\ScheduledReportController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\SalaryRunController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\QcTemplateController;
use App\Http\Controllers\Admin\StockTakeController;
use App\Http\Controllers\Admin\SupplierRatingController;
use App\Http\Controllers\Admin\VoucherAllocationController;
use App\Http\Controllers\Admin\WorkCentreController;
use App\Http\Controllers\Admin\WorkOrderController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockBalanceController;
use App\Http\Controllers\Admin\StockLedgerController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\SystemUtilityController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\TransporterController;
use App\Http\Controllers\Admin\UomController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\ShopFloorController;
use App\Http\Controllers\Admin\TallyExportController;
use App\Http\Controllers\Admin\MobileAttendanceController;
use App\Http\Controllers\Admin\InstallerController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('install', [InstallerController::class, 'show'])->name('install.show');
Route::post('install', [InstallerController::class, 'run'])->middleware('throttle:10,1')->name('install.run');
Route::get('install/update', [InstallerController::class, 'showUpdate'])->name('install.update.show');
Route::post('install/update', [InstallerController::class, 'update'])->middleware('throttle:10,1')->name('install.update.run');

Route::get('verify/{token}', [PublicVerifyController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('public.verify');

Route::get('share/{token}', [PublicDocumentShareController::class, 'show'])
    ->middleware(['signed', 'throttle:60,1'])
    ->name('public.document-share');

Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp.verify');

Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp.handle');

Route::prefix('portal')->name('portal.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [PortalAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [PortalAuthController::class, 'login'])->middleware('throttle:20,1')->name('login.submit');
    });

    Route::middleware(['auth', EnsureUserIsActive::class, 'portal.customer'])->group(function (): void {
        Route::post('logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
    });
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])
            ->middleware('throttle:60,1')
            ->name('login.submit');
    });

    Route::middleware(['auth', EnsureUserIsActive::class])->group(function (): void {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Inbox is available to every authenticated active user.
        Route::get('notifications', [InboxNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/mark-all-read', [InboxNotificationController::class, 'markAllRead'])
            ->middleware('throttle:60,1')
            ->name('notifications.mark-all-read');
        Route::post('notifications/{notification}/mark-read', [InboxNotificationController::class, 'markRead'])
            ->middleware('throttle:60,1')
            ->name('notifications.mark-read');

        Route::middleware(['permission', 'industry.feature'])->group(function (): void {
            Route::get('company', [CompanyController::class, 'edit'])->name('company.edit');
            Route::post('company', [CompanyController::class, 'update'])->middleware('throttle:120,1')->name('company.update');

            Route::post('branches/data', [BranchController::class, 'data'])->middleware('throttle:120,1')->name('branches.data');
            Route::resource('branches', BranchController::class)->except(['show']);

            Route::post('warehouses/data', [WarehouseController::class, 'data'])->middleware('throttle:120,1')->name('warehouses.data');
            Route::resource('warehouses', WarehouseController::class)->except(['show']);

            Route::get('parties/import', [PartyImportController::class, 'index'])->name('parties.import.index');
            Route::get('parties/import/template', [PartyImportController::class, 'template'])->name('parties.import.template');
            Route::post('parties/import/preview', [PartyImportController::class, 'preview'])->middleware('throttle:30,1')->name('parties.import.preview');
            Route::get('parties/import/{import}', [PartyImportController::class, 'show'])->name('parties.import.show');
            Route::post('parties/import/{import}/commit', [PartyImportController::class, 'commit'])->middleware('throttle:30,1')->name('parties.import.commit');
            Route::get('parties/import/{import}/status', [PartyImportController::class, 'status'])->name('parties.import.status');
            Route::get('parties/import/{import}/errors', [PartyImportController::class, 'downloadErrors'])->name('parties.import.errors');

            Route::post('parties/data', [PartyController::class, 'data'])->middleware('throttle:120,1')->name('parties.data');
            Route::get('parties/gstin-lookup', [PartyController::class, 'gstinLookup'])->middleware('throttle:120,1')->name('parties.gstin-lookup');
            Route::resource('parties', PartyController::class)->except(['show']);

            Route::post('tax-rates/data', [TaxRateController::class, 'data'])->middleware('throttle:120,1')->name('tax-rates.data');
            Route::resource('tax-rates', TaxRateController::class)->except(['show']);

            Route::post('uoms/data', [UomController::class, 'data'])->middleware('throttle:120,1')->name('uoms.data');
            Route::resource('uoms', UomController::class)->except(['show']);

            Route::post('categories/data', [CategoryController::class, 'data'])->middleware('throttle:120,1')->name('categories.data');
            Route::resource('categories', CategoryController::class)->except(['show']);

            Route::post('transporters/data', [TransporterController::class, 'data'])->middleware('throttle:120,1')->name('transporters.data');
            Route::resource('transporters', TransporterController::class)->except(['show']);

            Route::post('hsn-codes/data', [HsnCodeController::class, 'data'])->middleware('throttle:120,1')->name('hsn-codes.data');
            Route::resource('hsn-codes', HsnCodeController::class)->except(['show']);

            Route::post('items/data', [ItemController::class, 'data'])->middleware('throttle:120,1')->name('items.data');
            Route::resource('items', ItemController::class)->except(['show']);

            Route::post('boms/data', [BomController::class, 'data'])->middleware('throttle:120,1')->name('boms.data');
            Route::post('boms/{bom}/new-version', [BomController::class, 'newVersion'])->middleware('throttle:60,1')->name('boms.new-version');
            Route::post('boms/{bom}/explode', [BomController::class, 'explode'])->middleware('throttle:120,1')->name('boms.explode');
            Route::resource('boms', BomController::class)->except(['show']);

            Route::get('purchase-suggestions', [PurchaseSuggestionController::class, 'index'])->name('purchase-suggestions.index');
            Route::get('purchase-suggestions/data', [PurchaseSuggestionController::class, 'data'])->middleware('throttle:120,1')->name('purchase-suggestions.data');
            Route::get('purchase-indents', [PurchaseIndentController::class, 'index'])->name('purchase-indents.index');
            Route::post('purchase-indents', [PurchaseIndentController::class, 'store'])->middleware('throttle:60,1')->name('purchase-indents.store');
            Route::get('purchase-indents/{purchase_indent}', [PurchaseIndentController::class, 'show'])->name('purchase-indents.show');
            Route::post('purchase-indents/{purchase_indent}/approve', [PurchaseIndentController::class, 'approve'])->middleware('throttle:60,1')->name('purchase-indents.approve');
            Route::post('purchase-indents/{purchase_indent}/cancel', [PurchaseIndentController::class, 'cancel'])->middleware('throttle:60,1')->name('purchase-indents.cancel');
            Route::post('purchase-indents/{purchase_indent}/convert', [PurchaseIndentController::class, 'convert'])->middleware('throttle:60,1')->name('purchase-indents.convert');
            Route::post('purchase-indents/{purchase_indent}/rfq', [PurchaseRfqController::class, 'storeFromIndent'])->middleware('throttle:60,1')->name('purchase-indents.rfq');

            Route::get('purchase-rfqs', [PurchaseRfqController::class, 'index'])->name('purchase-rfqs.index');
            Route::get('purchase-rfqs/{purchase_rfq}', [PurchaseRfqController::class, 'show'])->name('purchase-rfqs.show');
            Route::post('purchase-rfqs/{purchase_rfq}/mark-sent', [PurchaseRfqController::class, 'markSent'])->middleware('throttle:60,1')->name('purchase-rfqs.mark-sent');
            Route::post('purchase-rfqs/{purchase_rfq}/add-quote', [PurchaseRfqController::class, 'addQuote'])->middleware('throttle:60,1')->name('purchase-rfqs.add-quote');
            Route::post('purchase-rfqs/{purchase_rfq}/quotes/{quote}/award', [PurchaseRfqController::class, 'award'])->middleware('throttle:60,1')->name('purchase-rfqs.award');

            Route::post('purchase-orders/data', [PurchaseOrderController::class, 'data'])->middleware('throttle:120,1')->name('purchase-orders.data');
            Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->middleware('throttle:60,1')->name('purchase-orders.approve');
            Route::post('purchase-orders/{purchase_order}/mark-sent', [PurchaseOrderController::class, 'markSent'])->middleware('throttle:60,1')->name('purchase-orders.mark-sent');
            Route::get('purchase-orders/{purchase_order}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
            Route::resource('purchase-orders', PurchaseOrderController::class)->except(['show']);

            Route::post('goods-receipts/data', [GoodsReceiptController::class, 'data'])->middleware('throttle:120,1')->name('goods-receipts.data');
            Route::get('goods-receipts/pending-lines/{purchase_order}', [GoodsReceiptController::class, 'pendingLines'])->middleware('throttle:120,1')->name('goods-receipts.pending-lines');
            Route::post('goods-receipts/{goods_receipt}/post', [GoodsReceiptController::class, 'post'])->middleware('throttle:60,1')->name('goods-receipts.post');
            Route::resource('goods-receipts', GoodsReceiptController::class)->except(['show']);

            Route::post('purchase-bills/data', [PurchaseBillController::class, 'data'])->middleware('throttle:120,1')->name('purchase-bills.data');
            Route::get('purchase-bills/billable-lines/{goods_receipt}', [PurchaseBillController::class, 'billableLines'])->middleware('throttle:120,1')->name('purchase-bills.billable-lines');
            Route::post('purchase-bills/{purchase_bill}/approve', [PurchaseBillController::class, 'approve'])->middleware('throttle:60,1')->name('purchase-bills.approve');
            Route::post('purchase-bills/{purchase_bill}/cancel', [PurchaseBillController::class, 'cancel'])->middleware('throttle:60,1')->name('purchase-bills.cancel');
            Route::resource('purchase-bills', PurchaseBillController::class)->except(['show']);

            Route::post('purchase-returns/data', [PurchaseReturnController::class, 'data'])->middleware('throttle:120,1')->name('purchase-returns.data');
            Route::get('purchase-returns/returnable-lines/{goods_receipt}', [PurchaseReturnController::class, 'returnableLines'])->middleware('throttle:120,1')->name('purchase-returns.returnable-lines');
            Route::post('purchase-returns/{purchase_return}/post', [PurchaseReturnController::class, 'post'])->middleware('throttle:60,1')->name('purchase-returns.post');
            Route::post('purchase-returns/{purchase_return}/cancel', [PurchaseReturnController::class, 'cancel'])->middleware('throttle:60,1')->name('purchase-returns.cancel');
            Route::resource('purchase-returns', PurchaseReturnController::class)->except(['show']);

            Route::post('leads/data', [LeadController::class, 'data'])->middleware('throttle:120,1')->name('leads.data');
            Route::post('leads/import', [LeadController::class, 'import'])->middleware('throttle:20,1')->name('leads.import');
            Route::post('leads/{lead}/status', [LeadController::class, 'status'])->middleware('throttle:60,1')->name('leads.status');
            Route::post('leads/{lead}/follow-up', [LeadController::class, 'followUp'])->middleware('throttle:60,1')->name('leads.follow-up');
            Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->middleware('throttle:60,1')->name('leads.convert');
            Route::resource('leads', LeadController::class)->except(['show']);

            Route::get('opportunities/pipeline', [OpportunityController::class, 'pipeline'])->name('opportunities.pipeline');
            Route::post('opportunities/data', [OpportunityController::class, 'data'])->middleware('throttle:120,1')->name('opportunities.data');
            Route::post('opportunities/{opportunity}/stage', [OpportunityController::class, 'stage'])->middleware('throttle:60,1')->name('opportunities.stage');
            Route::post('opportunities/{opportunity}/quotation', [OpportunityController::class, 'attachQuotation'])->middleware('throttle:60,1')->name('opportunities.quotation');
            Route::post('opportunities/{opportunity}/follow-up', [OpportunityController::class, 'followUp'])->middleware('throttle:60,1')->name('opportunities.follow-up');
            Route::resource('opportunities', OpportunityController::class)->except(['show']);

            Route::post('sales-quotations/data', [SalesQuotationController::class, 'data'])->middleware('throttle:120,1')->name('sales-quotations.data');
            Route::post('sales-quotations/{sales_quotation}/mark-sent', [SalesQuotationController::class, 'markSent'])->middleware('throttle:60,1')->name('sales-quotations.mark-sent');
            Route::post('sales-quotations/{sales_quotation}/accept', [SalesQuotationController::class, 'accept'])->middleware('throttle:60,1')->name('sales-quotations.accept');
            Route::post('sales-quotations/{sales_quotation}/convert', [SalesQuotationController::class, 'convert'])->middleware('throttle:60,1')->name('sales-quotations.convert');
            Route::post('sales-quotations/{sales_quotation}/whatsapp', [RemainingPriorityController::class, 'sendQuotationWhatsApp'])->middleware('throttle:30,1')->name('sales-quotations.whatsapp');
            Route::get('sales-quotations/{sales_quotation}/print', [SalesQuotationController::class, 'print'])->name('sales-quotations.print');
            Route::resource('sales-quotations', SalesQuotationController::class)->except(['show']);

            Route::post('sales-orders/data', [SalesOrderController::class, 'data'])->middleware('throttle:120,1')->name('sales-orders.data');
            Route::post('sales-orders/{sales_order}/confirm', [SalesOrderController::class, 'confirm'])->middleware('throttle:60,1')->name('sales-orders.confirm');
            Route::post('sales-orders/{sales_order}/cancel', [SalesOrderController::class, 'cancel'])->middleware('throttle:60,1')->name('sales-orders.cancel');
            Route::resource('sales-orders', SalesOrderController::class)->except(['show']);

            Route::post('sales-invoices/data', [SalesInvoiceController::class, 'data'])->middleware('throttle:120,1')->name('sales-invoices.data');
            Route::get('sales-invoices/pending-lines/{sales_order}', [SalesInvoiceController::class, 'pendingLines'])->middleware('throttle:120,1')->name('sales-invoices.pending-lines');
            Route::get('sales-invoices/challan-lines/{delivery_challan}', [SalesInvoiceController::class, 'challanLines'])->middleware('throttle:120,1')->name('sales-invoices.challan-lines');
            Route::post('sales-invoices/{sales_invoice}/confirm', [SalesInvoiceController::class, 'confirm'])->middleware('throttle:60,1')->name('sales-invoices.confirm');
            Route::post('sales-invoices/{sales_invoice}/einvoice', [EinvoiceController::class, 'push'])->middleware('throttle:10,1')->name('sales-invoices.einvoice');
            Route::get('sales-invoices/{sales_invoice}/print', [SalesInvoiceController::class, 'print'])->name('sales-invoices.print');
            Route::resource('sales-invoices', SalesInvoiceController::class)->except(['show']);

            Route::post('delivery-challans/data', [DeliveryChallanController::class, 'data'])->middleware('throttle:120,1')->name('delivery-challans.data');
            Route::get('delivery-challans/pending-lines/{sales_order}', [DeliveryChallanController::class, 'pendingLines'])->middleware('throttle:120,1')->name('delivery-challans.pending-lines');
            Route::post('delivery-challans/{delivery_challan}/dispatch', [DeliveryChallanController::class, 'dispatch'])->middleware('throttle:60,1')->name('delivery-challans.dispatch');
            Route::post('delivery-challans/{delivery_challan}/mark-delivered', [DeliveryChallanController::class, 'markDelivered'])->middleware('throttle:60,1')->name('delivery-challans.mark-delivered');
            Route::get('delivery-challans/{delivery_challan}/eway-payload', [DeliveryChallanController::class, 'ewayPayload'])->middleware('throttle:120,1')->name('delivery-challans.eway-payload');
            Route::post('delivery-challans/{delivery_challan}/eway-submit', [DeliveryChallanController::class, 'submitEway'])->middleware('throttle:60,1')->name('delivery-challans.eway-submit');
            Route::get('delivery-challans/{delivery_challan}/print', [DeliveryChallanController::class, 'print'])->name('delivery-challans.print');
            Route::resource('delivery-challans', DeliveryChallanController::class)->except(['show']);

            Route::post('packing-units/data', [PackingUnitController::class, 'data'])->middleware('throttle:120,1')->name('packing-units.data');
            Route::resource('packing-units', PackingUnitController::class)->except(['show']);

            Route::get('packages/pack', [PackageController::class, 'pack'])->name('packages.pack');
            Route::get('packages/print', [PackageController::class, 'print'])->name('packages.print');
            Route::get('packages/scan', [PackageController::class, 'scanForm'])->name('packages.scan-form');
            Route::post('packages/scan', [PackageController::class, 'scan'])->middleware('throttle:240,1')->name('packages.scan');
            Route::post('packages/replay-offline', [PackageController::class, 'replayOffline'])->middleware('throttle:60,1')->name('packages.replay-offline');
            Route::post('packages/data', [PackageController::class, 'data'])->middleware('throttle:120,1')->name('packages.data');
            Route::get('packages/summary/{delivery_challan}', [PackageController::class, 'summary'])->middleware('throttle:120,1')->name('packages.summary');
            Route::post('packages', [PackageController::class, 'store'])->middleware('throttle:120,1')->name('packages.store');
            Route::post('packages/{package}/reprint', [PackageController::class, 'reprint'])->middleware('throttle:60,1')->name('packages.reprint');
            Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
            Route::get('packages', [PackageController::class, 'index'])->name('packages.index');

            Route::post('sales-returns/data', [SalesReturnController::class, 'data'])->middleware('throttle:120,1')->name('sales-returns.data');
            Route::get('sales-returns/returnable-lines/{sales_invoice}', [SalesReturnController::class, 'returnableLines'])->middleware('throttle:120,1')->name('sales-returns.returnable-lines');
            Route::post('sales-returns/{sales_return}/post', [SalesReturnController::class, 'post'])->middleware('throttle:60,1')->name('sales-returns.post');
            Route::post('sales-returns/{sales_return}/cancel', [SalesReturnController::class, 'cancel'])->middleware('throttle:60,1')->name('sales-returns.cancel');
            Route::resource('sales-returns', SalesReturnController::class)->except(['show']);

            Route::post('production-plans/data', [ProductionPlanController::class, 'data'])->middleware('throttle:120,1')->name('production-plans.data');
            Route::get('production-plans/demand', [ProductionPlanController::class, 'demand'])->middleware('throttle:120,1')->name('production-plans.demand');
            Route::post('production-plans/{production_plan}/generate', [ProductionPlanController::class, 'generate'])->middleware('throttle:60,1')->name('production-plans.generate');
            Route::post('production-plans/{production_plan}/cancel', [ProductionPlanController::class, 'cancel'])->middleware('throttle:60,1')->name('production-plans.cancel');
            Route::resource('production-plans', ProductionPlanController::class)->except(['show']);

            Route::post('work-orders/data', [WorkOrderController::class, 'data'])->middleware('throttle:120,1')->name('work-orders.data');
            Route::get('work-orders/boms/{item}', [WorkOrderController::class, 'bomsForItem'])->middleware('throttle:120,1')->name('work-orders.boms');
            Route::get('work-orders/{work_order}/availability', [WorkOrderController::class, 'availability'])->middleware('throttle:120,1')->name('work-orders.availability');
            Route::post('work-orders/{work_order}/release', [WorkOrderController::class, 'release'])->middleware('throttle:60,1')->name('work-orders.release');
            Route::post('work-orders/{work_order}/issue-materials', [WorkOrderController::class, 'issueMaterials'])->middleware('throttle:60,1')->name('work-orders.issue-materials');
            Route::post('work-orders/{work_order}/close', [WorkOrderController::class, 'close'])->middleware('throttle:60,1')->name('work-orders.close');
            Route::resource('work-orders', WorkOrderController::class)->except(['show']);

            Route::post('production-entries/data', [ProductionEntryController::class, 'data'])->middleware('throttle:120,1')->name('production-entries.data');
            Route::post('production-entries/{production_entry}/post', [ProductionEntryController::class, 'post'])->middleware('throttle:60,1')->name('production-entries.post');
            Route::resource('production-entries', ProductionEntryController::class)->except(['show', 'update']);

            Route::get('shop-floor/operator', [ShopFloorController::class, 'operator'])->name('shop-floor.operator');
            Route::get('shop-floor/capacity', [ShopFloorController::class, 'capacity'])->name('shop-floor.capacity');
            Route::get('shop-floor/costing/{workOrder}', [ShopFloorController::class, 'costing'])->name('shop-floor.costing');

            Route::post('qc-templates/data', [QcTemplateController::class, 'data'])->middleware('throttle:120,1')->name('qc-templates.data');
            Route::resource('qc-templates', QcTemplateController::class)->except(['show']);

            Route::post('qc-inspections/data', [QcInspectionController::class, 'data'])->middleware('throttle:120,1')->name('qc-inspections.data');
            Route::post('qc-inspections/{qc_inspection}/complete', [QcInspectionController::class, 'complete'])->middleware('throttle:60,1')->name('qc-inspections.complete');
            Route::get('qc-inspections/{qc_inspection}/coa', [QcInspectionController::class, 'coa'])->name('qc-inspections.coa');
            Route::resource('qc-inspections', QcInspectionController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

            Route::get('work-centres/due', [WorkCentreController::class, 'due'])->name('work-centres.due');
            Route::get('work-centres/status-board', [WorkCentreController::class, 'statusBoard'])->name('work-centres.status-board');
            Route::post('work-centres/data', [WorkCentreController::class, 'data'])->middleware('throttle:120,1')->name('work-centres.data');
            Route::resource('work-centres', WorkCentreController::class)->except(['show']);

            Route::post('maintenance-orders/data', [MaintenanceOrderController::class, 'data'])->middleware('throttle:120,1')->name('maintenance-orders.data');
            Route::post('maintenance-orders/{maintenance_order}/issue-parts', [MaintenanceOrderController::class, 'issueParts'])->middleware('throttle:60,1')->name('maintenance-orders.issue-parts');
            Route::post('maintenance-orders/{maintenance_order}/close', [MaintenanceOrderController::class, 'close'])->middleware('throttle:60,1')->name('maintenance-orders.close');
            Route::resource('maintenance-orders', MaintenanceOrderController::class)->except(['show']);

            Route::post('opening-stocks/data', [OpeningStockController::class, 'data'])->middleware('throttle:120,1')->name('opening-stocks.data');
            Route::post('opening-stocks/{opening_stock}/post', [OpeningStockController::class, 'post'])->middleware('throttle:60,1')->name('opening-stocks.post');
            Route::resource('opening-stocks', OpeningStockController::class)->except(['show']);

            Route::post('stock-adjustments/data', [StockAdjustmentController::class, 'data'])->middleware('throttle:120,1')->name('stock-adjustments.data');
            Route::post('stock-adjustments/{stock_adjustment}/post', [StockAdjustmentController::class, 'post'])->middleware('throttle:60,1')->name('stock-adjustments.post');
            Route::resource('stock-adjustments', StockAdjustmentController::class)->except(['show']);

            Route::post('stock-transfers/data', [StockTransferController::class, 'data'])->middleware('throttle:120,1')->name('stock-transfers.data');
            Route::post('stock-transfers/{stock_transfer}/post', [StockTransferController::class, 'post'])->middleware('throttle:60,1')->name('stock-transfers.post');
            Route::resource('stock-transfers', StockTransferController::class)->except(['show']);

            Route::get('stock-balances', [StockBalanceController::class, 'index'])->name('stock-balances.index');
            Route::post('stock-balances/data', [StockBalanceController::class, 'data'])->middleware('throttle:120,1')->name('stock-balances.data');
            Route::get('stock-balances/summary', [StockBalanceController::class, 'summary'])->middleware('throttle:120,1')->name('stock-balances.summary');
            Route::get('stock-balances/availability', [StockBalanceController::class, 'availability'])->middleware('throttle:120,1')->name('stock-balances.availability');

            Route::get('stock-ledger', [StockLedgerController::class, 'index'])->name('stock-ledger.index');
            Route::post('stock-ledger/data', [StockLedgerController::class, 'data'])->middleware('throttle:120,1')->name('stock-ledger.data');

            Route::post('ledger-accounts/data', [LedgerAccountController::class, 'data'])->middleware('throttle:120,1')->name('ledger-accounts.data');
            Route::resource('ledger-accounts', LedgerAccountController::class)->except(['show']);

            Route::post('journal-vouchers/data', [JournalVoucherController::class, 'data'])->middleware('throttle:120,1')->name('journal-vouchers.data');
            Route::post('journal-vouchers/{journal_voucher}/post', [JournalVoucherController::class, 'post'])->middleware('throttle:60,1')->name('journal-vouchers.post');
            Route::post('journal-vouchers/{journal_voucher}/cancel', [JournalVoucherController::class, 'cancel'])->middleware('throttle:60,1')->name('journal-vouchers.cancel');
            Route::resource('journal-vouchers', JournalVoucherController::class)->except(['show']);

            Route::get('voucher-allocations/open-documents', [VoucherAllocationController::class, 'openDocuments'])->middleware('throttle:120,1')->name('voucher-allocations.open-documents');
            Route::get('voucher-allocations/{journal_voucher}', [VoucherAllocationController::class, 'edit'])->name('voucher-allocations.edit');
            Route::post('voucher-allocations/{journal_voucher}', [VoucherAllocationController::class, 'sync'])->middleware('throttle:60,1')->name('voucher-allocations.sync');

            Route::get('period-locks', [PeriodLockController::class, 'index'])->name('period-locks.index');
            Route::post('period-locks', [PeriodLockController::class, 'store'])->middleware('throttle:30,1')->name('period-locks.store');

            Route::get('price-lists/resolve-rate', [PriceListController::class, 'resolveRate'])->middleware('throttle:120,1')->name('price-lists.resolve-rate');
            Route::resource('price-lists', PriceListController::class)->except(['show']);

            Route::post('stock-takes/{stock_take}/seed', [StockTakeController::class, 'seed'])->middleware('throttle:60,1')->name('stock-takes.seed');
            Route::post('stock-takes/{stock_take}/scan', [StockTakeController::class, 'scan'])->middleware('throttle:240,1')->name('stock-takes.scan');
            Route::post('stock-takes/{stock_take}/save-lines', [StockTakeController::class, 'saveLines'])->middleware('throttle:60,1')->name('stock-takes.save-lines');
            Route::post('stock-takes/{stock_take}/post', [StockTakeController::class, 'post'])->middleware('throttle:60,1')->name('stock-takes.post');
            Route::resource('stock-takes', StockTakeController::class)->only(['index', 'create', 'store', 'edit']);

            Route::get('supplier-ratings', [SupplierRatingController::class, 'index'])->name('supplier-ratings.index');
            Route::post('supplier-ratings/recompute', [SupplierRatingController::class, 'recompute'])->middleware('throttle:30,1')->name('supplier-ratings.recompute');

            Route::get('crm-reports/funnel', [CrmReportController::class, 'funnel'])->name('crm-reports.funnel');
            Route::get('crm-reports/overdue', [CrmReportController::class, 'overdueFollowUps'])->name('crm-reports.overdue');
            Route::post('crm-reports/duplicates', [CrmReportController::class, 'duplicates'])->middleware('throttle:120,1')->name('crm-reports.duplicates');

            Route::get('qc-reports/pareto', [QcReportController::class, 'pareto'])->name('qc-reports.pareto');

            Route::get('custom-fields', [CustomizationController::class, 'customFields'])->name('custom-fields.index');
            Route::post('custom-fields', [CustomizationController::class, 'storeCustomField'])->middleware('throttle:60,1')->name('custom-fields.store');
            Route::delete('custom-fields/{custom_field}', [CustomizationController::class, 'destroyCustomField'])->name('custom-fields.destroy');

            Route::get('approval-rules', [CustomizationController::class, 'approvalRules'])->name('approval-rules.index');
            Route::post('approval-rules', [CustomizationController::class, 'storeApprovalRule'])->middleware('throttle:60,1')->name('approval-rules.store');
            Route::delete('approval-rules/{approval_rule}', [CustomizationController::class, 'destroyApprovalRule'])->name('approval-rules.destroy');

            Route::get('print-templates', [RemainingPriorityController::class, 'printTemplates'])->name('print-templates.index');
            Route::post('print-templates', [RemainingPriorityController::class, 'storePrintTemplate'])->middleware('throttle:60,1')->name('print-templates.store');
            Route::delete('print-templates/{print_template}', [RemainingPriorityController::class, 'destroyPrintTemplate'])->name('print-templates.destroy');

            Route::get('terms-blocks', [RemainingPriorityController::class, 'termsBlocks'])->name('terms-blocks.index');
            Route::post('terms-blocks', [RemainingPriorityController::class, 'storeTermsBlock'])->middleware('throttle:60,1')->name('terms-blocks.store');
            Route::delete('terms-blocks/{terms_block}', [RemainingPriorityController::class, 'destroyTermsBlock'])->name('terms-blocks.destroy');

            Route::get('ui-labels', [RemainingPriorityController::class, 'uiLabels'])->name('ui-labels.index');
            Route::post('ui-labels', [RemainingPriorityController::class, 'storeUiLabel'])->middleware('throttle:60,1')->name('ui-labels.store');

            Route::get('industry-profiles', [IndustryProfileController::class, 'index'])->name('industry-profiles.index');
            Route::post('industry-profiles/activate', [IndustryProfileController::class, 'activate'])->middleware('throttle:30,1')->name('industry-profiles.activate');

            Route::post('integrations/whatsapp-test', [RemainingPriorityController::class, 'testWhatsApp'])->middleware('throttle:10,1')->name('integrations.whatsapp-test');

            Route::post('batches/{batch}/recall', [CustomizationController::class, 'recallBatch'])->middleware('throttle:30,1')->name('batches.recall');

            Route::get('finance-reports/ageing', [FinanceReportController::class, 'ageing'])->name('finance-reports.ageing');
            Route::get('finance-reports/ageing/data', [FinanceReportController::class, 'ageingData'])->middleware('throttle:120,1')->name('finance-reports.ageing-data');
            Route::get('finance-reports/ageing/export', [FinanceReportController::class, 'ageingExport'])->middleware('throttle:30,1')->name('finance-reports.ageing-export');
            Route::get('finance-reports/statement', [FinanceReportController::class, 'statement'])->name('finance-reports.statement');
            Route::get('finance-reports/trial-balance', [FinanceReportController::class, 'trialBalance'])->name('finance-reports.trial-balance');
            Route::get('finance-reports/profit-and-loss', [FinanceReportController::class, 'profitAndLoss'])->name('finance-reports.profit-and-loss');
            Route::get('finance-reports/balance-sheet', [FinanceReportController::class, 'balanceSheet'])->name('finance-reports.balance-sheet');

            Route::get('tally/export', [TallyExportController::class, 'export'])->middleware('throttle:30,1')->name('tally.export');

            Route::get('gst-reports', [GstReportController::class, 'index'])->name('gst-reports.index');
            Route::get('gst-reports/export', [GstReportController::class, 'export'])->middleware('throttle:30,1')->name('gst-reports.export');
            Route::post('gst-reports/gsp-push', [GstReportController::class, 'pushGsp'])->middleware('throttle:10,1')->name('gst-reports.gsp-push');
            Route::get('gstr2b', [Gstr2bImportController::class, 'index'])->name('gstr2b.index');
            Route::post('gstr2b', [Gstr2bImportController::class, 'store'])->middleware('throttle:20,1')->name('gstr2b.store');

            Route::get('bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
            Route::post('bank-reconciliation/reconcile', [BankReconciliationController::class, 'reconcile'])->middleware('throttle:60,1')->name('bank-reconciliation.reconcile');

            Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
            Route::post('holidays', [HolidayController::class, 'storeHoliday'])->middleware('throttle:60,1')->name('holidays.store');
            Route::post('leave-balances', [HolidayController::class, 'storeLeaveBalance'])->middleware('throttle:60,1')->name('leave-balances.store');

            Route::get('scheduled-reports', [ScheduledReportController::class, 'index'])->name('scheduled-reports.index');
            Route::post('scheduled-reports', [ScheduledReportController::class, 'store'])->middleware('throttle:60,1')->name('scheduled-reports.store');
            Route::delete('scheduled-reports/{scheduledReport}', [ScheduledReportController::class, 'destroy'])->name('scheduled-reports.destroy');

            Route::get('scan-exceptions', [ScanExceptionController::class, 'index'])->name('scan-exceptions.index');
            Route::post('scan-exceptions/{scanException}/resolve', [ScanExceptionController::class, 'resolve'])->middleware('throttle:60,1')->name('scan-exceptions.resolve');

            Route::post('locale', [LocaleController::class, 'update'])->middleware('throttle:60,1')->name('locale.update');

            Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [BackupController::class, 'store'])->middleware('throttle:10,1')->name('backups.store');
            Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
            Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->middleware('throttle:10,1')->name('backups.restore');

            Route::get('recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
            Route::post('recycle-bin/restore', [RecycleBinController::class, 'restore'])->middleware('throttle:60,1')->name('recycle-bin.restore');

            Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
            Route::post('shifts', [ShiftController::class, 'store'])->middleware('throttle:60,1')->name('shifts.store');
            Route::put('shifts/{shift}', [ShiftController::class, 'update'])->middleware('throttle:60,1')->name('shifts.update');
            Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

            Route::post('employees/data', [EmployeeController::class, 'data'])->middleware('throttle:120,1')->name('employees.data');
            Route::resource('employees', EmployeeController::class)->except(['show']);

            Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('attendance', [AttendanceController::class, 'store'])->middleware('throttle:120,1')->name('attendance.store');
            Route::post('attendance/mobile-punch', [MobileAttendanceController::class, 'punch'])->middleware('throttle:120,1')->name('attendance.mobile-punch');
            Route::get('attendance/import/template', [AttendanceController::class, 'importTemplate'])->name('attendance.import.template');
            Route::post('attendance/import', [AttendanceController::class, 'import'])->middleware('throttle:30,1')->name('attendance.import');

            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

            Route::post('salary-runs/data', [SalaryRunController::class, 'data'])->middleware('throttle:120,1')->name('salary-runs.data');
            Route::post('salary-runs/{salary_run}/recalculate', [SalaryRunController::class, 'recalculate'])->middleware('throttle:60,1')->name('salary-runs.recalculate');
            Route::post('salary-runs/{salary_run}/post', [SalaryRunController::class, 'post'])->middleware('throttle:60,1')->name('salary-runs.post');
            Route::post('salary-runs/{salary_run}/cancel', [SalaryRunController::class, 'cancel'])->middleware('throttle:60,1')->name('salary-runs.cancel');
            Route::get('salary-runs/{salary_run}/print', [SalaryRunController::class, 'print'])->name('salary-runs.print');
            Route::resource('salary-runs', SalaryRunController::class)->except(['show']);

            Route::get('reports/{register}/data', [RegisterReportController::class, 'data'])->middleware('throttle:120,1')->name('reports.data');
            Route::get('reports/{register}/export', [RegisterReportController::class, 'export'])->middleware('throttle:30,1')->name('reports.export');
            Route::get('reports/{register}', [RegisterReportController::class, 'show'])->name('reports.show');

            Route::get('settings', [SystemSettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SystemSettingController::class, 'update'])->middleware('throttle:60,1')->name('settings.update');
            Route::get('dashboard-widgets', [DashboardWidgetController::class, 'index'])->name('dashboard-widgets.index');
            Route::post('dashboard-widgets', [DashboardWidgetController::class, 'save'])->middleware('throttle:60,1')->name('dashboard-widgets.save');

            Route::post('financial-years/data', [FinancialYearController::class, 'data'])->middleware('throttle:120,1')->name('financial-years.data');
            Route::post('financial-years/{financial_year}/set-current', [FinancialYearController::class, 'setCurrent'])->name('financial-years.set-current');
            Route::post('financial-years/{financial_year}/close', [FinancialYearController::class, 'close'])->name('financial-years.close');
            Route::resource('financial-years', FinancialYearController::class)->except(['show']);

            Route::post('document-series/data', [DocumentNumberSeriesController::class, 'data'])->middleware('throttle:120,1')->name('document-series.data');
            Route::get('document-series/{document_number_series}/preview', [DocumentNumberSeriesController::class, 'preview'])->name('document-series.preview');
            Route::resource('document-series', DocumentNumberSeriesController::class)
                ->parameters(['document-series' => 'document_number_series'])
                ->except(['show']);

            Route::get('system/health', [SystemUtilityController::class, 'health'])->name('system.health');
            Route::post('system/clear-cache', [SystemUtilityController::class, 'clearCache'])->middleware('throttle:10,1')->name('system.clear-cache');

            Route::get('notification-rules', [NotificationRuleController::class, 'index'])->name('notification-rules.index');
            Route::post('notification-rules', [NotificationRuleController::class, 'store'])->middleware('throttle:60,1')->name('notification-rules.store');
            Route::put('notification-rules/{notification_rule}', [NotificationRuleController::class, 'update'])->middleware('throttle:60,1')->name('notification-rules.update');
            Route::post('notification-rules/{notification_rule}/toggle', [NotificationRuleController::class, 'toggle'])->middleware('throttle:60,1')->name('notification-rules.toggle');
            Route::delete('notification-rules/{notification_rule}', [NotificationRuleController::class, 'destroy'])->name('notification-rules.destroy');

            Route::post('roles/data', [RoleController::class, 'data'])->middleware('throttle:120,1')->name('roles.data');
            Route::post('roles/{role}/copy', [RoleController::class, 'copy'])->name('roles.copy');
            Route::resource('roles', RoleController::class)->except(['show']);

            Route::post('users/data', [UserController::class, 'data'])->middleware('throttle:120,1')->name('users.data');
            Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
            Route::resource('users', UserController::class)->except(['show']);
        });
    });
});

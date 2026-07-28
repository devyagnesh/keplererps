<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\PurchaseBillStatus;
use App\Enums\SalesInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use App\Services\CsvExportService;
use App\Services\RegisterReportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales, purchase, stock and production registers (M15).
 */
class RegisterReportController extends Controller
{
    /**
     * Register definitions: heading, table columns and the filters the screen offers.
     *
     * @var array<string, array{title: string, columns: array<string, string>, numeric: list<string>, filters: list<string>}>
     */
    protected const REGISTERS = [
        'sales' => [
            'title' => 'Sales Register',
            'columns' => [
                'document_no' => 'Invoice No',
                'document_date' => 'Date',
                'party_code' => 'Customer Code',
                'party_name' => 'Customer',
                'taxable_value' => 'Taxable Value',
                'tax_amount' => 'Tax',
                'invoice_value' => 'Invoice Value',
                'status' => 'Status',
            ],
            'numeric' => ['taxable_value', 'tax_amount', 'invoice_value'],
            'filters' => ['customer', 'sales_status'],
        ],
        'purchase' => [
            'title' => 'Purchase Register',
            'columns' => [
                'document_no' => 'Bill No',
                'document_date' => 'Date',
                'supplier_bill_no' => 'Supplier Bill No',
                'party_code' => 'Supplier Code',
                'party_name' => 'Supplier',
                'taxable_value' => 'Taxable Value',
                'tax_amount' => 'Tax',
                'bill_value' => 'Bill Value',
                'status' => 'Status',
            ],
            'numeric' => ['taxable_value', 'tax_amount', 'bill_value'],
            'filters' => ['supplier', 'purchase_status'],
        ],
        'stock' => [
            'title' => 'Stock Movement Register',
            'columns' => [
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'warehouse' => 'Warehouse',
                'uom' => 'UOM',
                'opening_qty' => 'Opening',
                'inward_qty' => 'Inward',
                'outward_qty' => 'Outward',
                'closing_qty' => 'Closing',
            ],
            'numeric' => ['opening_qty', 'inward_qty', 'outward_qty', 'closing_qty'],
            'filters' => ['item', 'warehouse'],
        ],
        'production' => [
            'title' => 'Production Register',
            'columns' => [
                'document_no' => 'Entry No',
                'document_date' => 'Date',
                'work_order_no' => 'Work Order',
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'work_centre' => 'Work Centre',
                'good_qty' => 'Good Qty',
                'rejected_qty' => 'Rejected Qty',
                'rejection_pct' => 'Rejection %',
                'downtime_minutes' => 'Downtime (min)',
                'total_cost' => 'Cost',
            ],
            'numeric' => ['good_qty', 'rejected_qty', 'downtime_minutes', 'total_cost'],
            'filters' => ['item', 'work_centre'],
        ],
        'pending-sales-orders' => [
            'title' => 'Pending Sales Orders',
            'columns' => [
                'document_no' => 'SO No',
                'document_date' => 'Date',
                'party_code' => 'Customer Code',
                'party_name' => 'Customer',
                'ordered_qty' => 'Ordered',
                'delivered_qty' => 'Delivered',
                'pending_qty' => 'Pending',
                'grand_total' => 'Value',
                'status' => 'Status',
            ],
            'numeric' => ['ordered_qty', 'delivered_qty', 'pending_qty', 'grand_total'],
            'filters' => ['customer'],
        ],
        'pending-purchase-orders' => [
            'title' => 'Pending Purchase Orders',
            'columns' => [
                'document_no' => 'PO No',
                'document_date' => 'Date',
                'party_code' => 'Supplier Code',
                'party_name' => 'Supplier',
                'ordered_qty' => 'Ordered',
                'received_qty' => 'Received',
                'pending_qty' => 'Pending',
                'grand_total' => 'Value',
                'status' => 'Status',
            ],
            'numeric' => ['ordered_qty', 'received_qty', 'pending_qty', 'grand_total'],
            'filters' => ['supplier'],
        ],
        'stock-valuation' => [
            'title' => 'Stock Valuation',
            'columns' => [
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'warehouse' => 'Warehouse',
                'qty_on_hand' => 'Qty',
                'rate' => 'Rate',
                'value' => 'Value',
            ],
            'numeric' => ['qty_on_hand', 'rate', 'value'],
            'filters' => ['item', 'warehouse'],
        ],
        'rejection' => [
            'title' => 'Rejection Register',
            'columns' => [
                'document_no' => 'Entry No',
                'document_date' => 'Date',
                'work_order_no' => 'Work Order',
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'defect_code' => 'Defect Code',
                'defect_name' => 'Defect',
                'rejected_qty' => 'Rejected Qty',
            ],
            'numeric' => ['rejected_qty'],
            'filters' => ['item'],
        ],
        'slow-moving' => [
            'title' => 'Slow / Dead Stock',
            'columns' => [
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'warehouse' => 'Warehouse',
                'qty_on_hand' => 'Qty',
                'last_movement' => 'Last Movement',
                'days_idle' => 'Days Idle',
            ],
            'numeric' => ['qty_on_hand', 'days_idle'],
            'filters' => ['warehouse'],
        ],
        'day-book' => [
            'title' => 'Day Book',
            'columns' => [
                'document_no' => 'Voucher No',
                'document_date' => 'Date',
                'voucher_type' => 'Type',
                'account' => 'Account',
                'debit' => 'Debit',
                'credit' => 'Credit',
                'narration' => 'Narration',
            ],
            'numeric' => ['debit', 'credit'],
            'filters' => [],
        ],
        'sales-executive' => [
            'title' => 'Sales Executive Summary',
            'columns' => [
                'executive' => 'Sales Executive',
                'invoice_count' => 'Invoices',
                'invoice_value' => 'Invoice Value',
                'customer_count' => 'Customers',
            ],
            'numeric' => ['invoice_count', 'invoice_value', 'customer_count'],
            'filters' => ['customer'],
        ],
        'purchase-vs-indent' => [
            'title' => 'Purchase vs Indent',
            'columns' => [
                'indent_no' => 'Indent No',
                'indent_date' => 'Date',
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'uom' => 'UOM',
                'indent_qty' => 'Indent Qty',
                'ordered_qty' => 'Ordered Qty',
                'pending_qty' => 'Pending Qty',
                'status' => 'Status',
            ],
            'numeric' => ['indent_qty', 'ordered_qty', 'pending_qty'],
            'filters' => [],
        ],
        'grn-pending-inspection' => [
            'title' => 'GRN Pending Inspection',
            'columns' => [
                'inspection_no' => 'Inspection No',
                'inspection_date' => 'Date',
                'grn_no' => 'GRN No',
                'grn_date' => 'GRN Date',
                'item_code' => 'Item Code',
                'item_name' => 'Item',
                'lot_quantity' => 'Lot Qty',
                'status' => 'Status',
                'inspector' => 'Inspector',
            ],
            'numeric' => ['lot_quantity'],
            'filters' => ['item'],
        ],
    ];

    public function __construct(
        protected RegisterReportService $registers,
        protected CsvExportService $csv
    ) {}

    public function show(Request $request, string $register): View
    {
        abort_unless(array_key_exists($register, self::REGISTERS), 404);

        $definition = self::REGISTERS[$register];

        return view('admin.reports.register', [
            'register' => $register,
            'definition' => $definition,
            'fromDate' => $this->fromDate($request),
            'toDate' => $this->toDate($request),
            'lookups' => $this->lookups($definition['filters']),
        ]);
    }

    public function data(Request $request, string $register): JsonResponse
    {
        abort_unless(array_key_exists($register, self::REGISTERS), 404);

        $result = $this->build($request, $register);

        return response()->json([
            'status' => true,
            'message' => count($result['rows']).' row(s) loaded.',
            'data' => [
                'rows' => $result['rows'],
                'totals' => $result['totals'],
                'truncated' => count($result['rows']) >= RegisterReportService::MAX_ROWS,
            ],
        ]);
    }

    public function export(Request $request, string $register): StreamedResponse
    {
        abort_unless(array_key_exists($register, self::REGISTERS), 404);

        return $this->csv->stream(
            $this->build($request, $register)['rows'],
            $register.'-register-'.now()->format('Ymd').'.csv'
        );
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    protected function build(Request $request, string $register): array
    {
        $from = $this->fromDate($request);
        $to = $this->toDate($request);
        $filters = [
            'party_id' => $request->integer('party_id') ?: null,
            'item_id' => $request->integer('item_id') ?: null,
            'warehouse_id' => $request->integer('warehouse_id') ?: null,
            'work_centre_id' => $request->integer('work_centre_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        return match ($register) {
            'sales' => $this->registers->sales($from, $to, $filters),
            'purchase' => $this->registers->purchase($from, $to, $filters),
            'stock' => $this->registers->stock($from, $to, $filters),
            'pending-sales-orders' => $this->registers->pendingSalesOrders($from, $to, $filters),
            'pending-purchase-orders' => $this->registers->pendingPurchaseOrders($from, $to, $filters),
            'stock-valuation' => $this->registers->stockValuation($from, $to, $filters),
            'rejection' => $this->registers->rejection($from, $to, $filters),
            'slow-moving' => $this->registers->slowMoving($from, $to, $filters),
            'day-book' => $this->registers->dayBook($from, $to, $filters),
            'sales-executive' => $this->registers->salesExecutive($from, $to, $filters),
            'purchase-vs-indent' => $this->registers->purchaseVsIndent($from, $to, $filters),
            'grn-pending-inspection' => $this->registers->grnPendingInspection($from, $to, $filters),
            default => $this->registers->production($from, $to, $filters),
        };
    }

    /**
     * Lookup lists for the filters a register declares.
     *
     * @param  list<string>  $filters
     * @return array<string, mixed>
     */
    protected function lookups(array $filters): array
    {
        $lookups = [];

        if (in_array('customer', $filters, true)) {
            $lookups['parties'] = $this->parties(PartyType::Customer);
            $lookups['party_label'] = 'Customer';
        }
        if (in_array('supplier', $filters, true)) {
            $lookups['parties'] = $this->parties(PartyType::Supplier);
            $lookups['party_label'] = 'Supplier';
        }
        if (in_array('sales_status', $filters, true)) {
            $lookups['statuses'] = SalesInvoiceStatus::cases();
        }
        if (in_array('purchase_status', $filters, true)) {
            $lookups['statuses'] = PurchaseBillStatus::cases();
        }
        if (in_array('item', $filters, true)) {
            $lookups['items'] = Item::query()->where('is_active', true)->orderBy('item_code')->get(['id', 'item_code', 'item_name']);
        }
        if (in_array('warehouse', $filters, true)) {
            $lookups['warehouses'] = Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
        }
        if (in_array('work_centre', $filters, true)) {
            $lookups['workCentres'] = WorkCentre::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
        }

        return $lookups;
    }

    /**
     * @return Collection<int, Party>
     */
    protected function parties(PartyType $type)
    {
        return Party::query()
            ->where('status', PartyStatus::Active)
            ->whereIn('party_type', [$type->value, PartyType::Both->value])
            ->orderBy('party_code')
            ->get(['id', 'party_code', 'party_name']);
    }

    protected function fromDate(Request $request): string
    {
        return $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
    }

    protected function toDate(Request $request): string
    {
        return $request->date('to_date')?->toDateString() ?? now()->toDateString();
    }
}

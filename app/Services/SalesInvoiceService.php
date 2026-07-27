<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\NotificationEvent;
use App\Enums\SalesInvoiceStatus;
use App\Enums\StockTransactionType;
use App\Models\DeliveryChallan;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Repositories\Interfaces\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sales invoice from sales order or delivery challan (M06 / US-M12-04).
 */
class SalesInvoiceService
{
    public function __construct(
        protected SalesInvoiceRepositoryInterface $repository,
        protected SalesOrderService $salesOrders,
        protected SalesTaxCalculator $tax,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering,
        protected AccountingPostingService $accounting,
        protected NotificationDispatchService $notifications
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): SalesInvoice
    {
        return $this->repository->findById($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SalesInvoice
    {
        return DB::transaction(function () use ($data): SalesInvoice {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $challan = null;
            if (! empty($data['delivery_challan_id'])) {
                $challan = DeliveryChallan::query()->with('items')->findOrFail((int) $data['delivery_challan_id']);
                if (! $challan->status->canInvoice()) {
                    throw ValidationException::withMessages([
                        'delivery_challan_id' => 'Only dispatched or delivered challans can be invoiced.',
                    ]);
                }
                $data['sales_order_id'] = $challan->sales_order_id;
                if ($lines === []) {
                    $lines = $challan->items->map(fn ($line): array => [
                        'sales_order_item_id' => $line->sales_order_item_id,
                        'quantity' => (float) $line->quantity,
                    ])->all();
                }
            }

            $order = SalesOrder::query()->with('items')->findOrFail((int) $data['sales_order_id']);
            if (! $order->status->canInvoice()) {
                throw ValidationException::withMessages(['sales_order_id' => 'Sales order is not open for invoicing.']);
            }

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::Invoice);
            $data['customer_id'] = $order->customer_id;
            $data['warehouse_id'] = $order->warehouse_id;
            $data['place_of_supply_state_id'] = $order->place_of_supply_state_id;
            $data['tax_type'] = $order->tax_type;
            $data['status'] = SalesInvoiceStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['subtotal'] = 0;
            $data['discount_total'] = 0;
            $data['tax_total'] = 0;
            $data['round_off'] = 0;
            $data['grand_total'] = 0;

            $invoice = $this->repository->create($data);
            $this->syncItems($invoice, $order, $lines, $challan);
            $this->recalculate($invoice->id);

            return $this->repository->findById($invoice->id);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): SalesInvoice
    {
        return DB::transaction(function () use ($id, $data): SalesInvoice {
            $invoice = $this->repository->findById($id);
            if ($invoice->status !== SalesInvoiceStatus::Draft) {
                throw ValidationException::withMessages(['invoice' => 'Confirmed invoices cannot be edited.']);
            }

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status'], $data['sales_order_id'], $data['delivery_challan_id']);
            $data['updated_by'] = Auth::id();

            $order = SalesOrder::query()->with('items')->findOrFail($invoice->sales_order_id);
            $challan = $invoice->delivery_challan_id
                ? DeliveryChallan::query()->with('items')->find($invoice->delivery_challan_id)
                : null;

            if ($challan !== null) {
                $lines = $challan->items->map(fn ($line): array => [
                    'sales_order_item_id' => $line->sales_order_item_id,
                    'quantity' => (float) $line->quantity,
                ])->all();
            }

            $this->repository->update($id, $data);
            $this->syncItems($invoice, $order, $lines, $challan);
            $this->recalculate($id);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $invoice = $this->repository->findById($id);
        if ($invoice->status !== SalesInvoiceStatus::Draft) {
            throw ValidationException::withMessages(['invoice' => 'Only draft invoices can be deleted.']);
        }

        return $this->repository->delete($id);
    }

    public function confirm(int $id): SalesInvoice
    {
        return DB::transaction(function () use ($id): SalesInvoice {
            $invoice = SalesInvoice::query()
                ->with(['items', 'salesOrder.items'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($invoice->status !== SalesInvoiceStatus::Draft) {
                throw ValidationException::withMessages(['invoice' => 'Only draft invoices can be confirmed.']);
            }
            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one invoice line.']);
            }

            $fromChallan = $invoice->delivery_challan_id !== null;
            $order = SalesOrder::query()->with('items')->lockForUpdate()->findOrFail($invoice->sales_order_id);

            foreach ($invoice->items as $line) {
                $soLine = SalesOrderItem::query()->lockForUpdate()->findOrFail($line->sales_order_item_id);
                $qty = (float) $line->quantity;
                if ($qty - $soLine->pendingInvoiceQty() > 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => 'Invoice quantity exceeds pending ordered quantity.',
                    ]);
                }

                if (! $fromChallan) {
                    $this->ledger->post([
                        'item_id' => $line->item_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'transaction_type' => StockTransactionType::Delivery,
                        'posting_at' => $invoice->document_date->copy()->startOfDay(),
                        'qty_in' => 0,
                        'qty_out' => $qty,
                        'source' => $invoice,
                        'remarks' => $invoice->document_no,
                    ]);

                    $soLine->forceFill([
                        'invoiced_qty' => round((float) $soLine->invoiced_qty + $qty, 4),
                        'delivered_qty' => round((float) $soLine->delivered_qty + $qty, 4),
                    ])->save();

                    $this->releaseCommitted($order->warehouse_id, $line->item_id, $qty);
                } else {
                    $soLine->forceFill([
                        'invoiced_qty' => round((float) $soLine->invoiced_qty + $qty, 4),
                    ])->save();
                }
            }

            $invoice->forceFill([
                'status' => SalesInvoiceStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $order->refresh()->load('items');
            $this->salesOrders->refreshFulfillmentStatus($order);

            $this->accounting->postSalesInvoice($invoice->refresh());

            $confirmed = $this->repository->findById($id);
            $this->notifications->dispatch(
                NotificationEvent::SalesInvoiceConfirmed,
                [
                    'document_no' => $confirmed->document_no,
                    'party_name' => (string) ($confirmed->customer?->party_name ?? ''),
                ],
                route('admin.sales-invoices.edit', $confirmed)
            );

            return $confirmed;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingLinesForOrder(int $salesOrderId): array
    {
        $order = SalesOrder::query()->with(['items.item:id,item_code,item_name'])->findOrFail($salesOrderId);
        if (! $order->status->canInvoice()) {
            throw ValidationException::withMessages(['sales_order_id' => 'Sales order is not open for invoicing.']);
        }

        $rows = [];
        foreach ($order->items as $line) {
            $pending = $line->pendingInvoiceQty();
            if ($pending <= 0) {
                continue;
            }
            $rows[] = [
                'sales_order_item_id' => $line->id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'pending_qty' => $pending,
                'rate' => (float) $line->rate,
                'discount_percent' => (float) $line->discount_percent,
                'gst_rate' => (float) $line->gst_rate,
                'uom_id' => $line->uom_id,
                'description' => $line->description,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingLinesForChallan(int $deliveryChallanId): array
    {
        $challan = DeliveryChallan::query()
            ->with(['items.item:id,item_code,item_name', 'items.salesOrderItem'])
            ->findOrFail($deliveryChallanId);

        if (! $challan->status->canInvoice()) {
            throw ValidationException::withMessages([
                'delivery_challan_id' => 'Only dispatched or delivered challans can be invoiced.',
            ]);
        }

        $rows = [];
        foreach ($challan->items as $line) {
            $soLine = $line->salesOrderItem;
            if ($soLine === null) {
                continue;
            }
            $pending = $soLine->pendingInvoiceQty();
            $qty = min((float) $line->quantity, $pending);
            if ($qty <= 0) {
                continue;
            }
            $rows[] = [
                'sales_order_item_id' => $line->sales_order_item_id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'pending_qty' => $qty,
                'rate' => (float) ($soLine->rate ?? $line->rate),
                'discount_percent' => (float) $soLine->discount_percent,
                'gst_rate' => (float) $soLine->gst_rate,
                'uom_id' => $line->uom_id,
                'description' => $line->description,
                'locked' => true,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(SalesInvoice $invoice, SalesOrder $order, array $lines, ?DeliveryChallan $challan = null): void
    {
        $invoice->items()->delete();
        $soItems = $order->items->keyBy('id');
        $challanQtyBySoLine = $challan
            ? $challan->items->groupBy('sales_order_item_id')->map(fn ($g) => (float) $g->sum('quantity'))
            : null;

        foreach (array_values($lines) as $index => $line) {
            if (empty($line['sales_order_item_id']) || empty($line['quantity'])) {
                continue;
            }
            $soLine = $soItems->get((int) $line['sales_order_item_id']);
            if ($soLine === null) {
                throw ValidationException::withMessages(['items' => 'Invoice line does not belong to the sales order.']);
            }

            $qty = round((float) $line['quantity'], 4);
            if ($challanQtyBySoLine !== null) {
                $allowed = (float) ($challanQtyBySoLine[(int) $line['sales_order_item_id']] ?? 0);
                if (abs($qty - $allowed) > 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => 'Invoice quantities must match the delivery challan exactly.',
                    ]);
                }
            }

            if ($qty - $soLine->pendingInvoiceQty() > 0.0001) {
                throw ValidationException::withMessages(['items' => 'Quantity exceeds pending invoice quantity.']);
            }

            $rate = round((float) ($line['rate'] ?? $soLine->rate), 4);
            $disc = round((float) ($line['discount_percent'] ?? $soLine->discount_percent), 2);
            $gst = round((float) ($line['gst_rate'] ?? $soLine->gst_rate), 2);
            $calc = $this->tax->calculateLine($qty, $rate, $disc, $gst, $order->tax_type);

            SalesInvoiceItem::query()->create(array_merge([
                'sales_invoice_id' => $invoice->id,
                'sales_order_item_id' => $soLine->id,
                'item_id' => $soLine->item_id,
                'uom_id' => $soLine->uom_id,
                'description' => $line['description'] ?? $soLine->description,
                'quantity' => $qty,
                'rate' => $rate,
                'discount_percent' => $disc,
                'gst_rate' => $gst,
                'sort_order' => $index,
            ], $calc));
        }

        if ($invoice->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Add at least one invoice line.']);
        }
    }

    protected function recalculate(int $id): void
    {
        $invoice = SalesInvoice::query()->with('items')->findOrFail($id);
        $subtotal = round((float) $invoice->items->sum('taxable_amount'), 2);
        $discount = round((float) $invoice->items->sum('discount_amount'), 2);
        $tax = round((float) $invoice->items->sum('tax_amount'), 2);
        $beforeRound = round($subtotal + $tax, 2);
        $roundOff = $this->tax->roundOff($beforeRound);

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'round_off' => $roundOff,
            'grand_total' => round($beforeRound + $roundOff, 2),
        ])->save();
    }

    protected function releaseCommitted(int $warehouseId, int $itemId, float $qty): void
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_key', 0)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->forceFill([
            'committed_qty' => max(0, round((float) $balance->committed_qty - $qty, 4)),
        ])->save();
    }
}

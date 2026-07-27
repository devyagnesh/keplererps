<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\SalesOrderStatus;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Repositories\Interfaces\SalesOrderRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sales order business logic — confirm commits stock (BR-18, US-M06-03).
 */
class SalesOrderService
{
    public function __construct(
        protected SalesOrderRepositoryInterface $repository,
        protected SalesTaxCalculator $tax,
        protected NumberingService $numbering,
        protected ActivityLogService $activityLog,
        protected ApprovalRuleService $approvals,
        protected UomConversionService $uom
    ) {}

    /** @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): SalesOrder
    {
        return $this->repository->findById($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data): SalesOrder {
            $lines = $data['items'] ?? [];
            unset($data['items']);
            $customer = $this->assertCustomer((int) $data['customer_id']);
            $this->assertLeafWarehouse((int) $data['warehouse_id']);
            $this->assertDeliveryDate($data);

            $taxCtx = $this->tax->resolveContext((int) $data['place_of_supply_state_id']);
            $data['tax_type'] = $taxCtx['tax_type'];
            $data['document_no'] = $this->numbering->next(DocumentSeriesType::SalesOrder);
            $data['status'] = SalesOrderStatus::Draft->value;
            $data['credit_hold'] = false;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['subtotal'] = 0;
            $data['discount_total'] = 0;
            $data['tax_total'] = 0;
            $data['grand_total'] = 0;

            $doc = $this->repository->create($data);
            $this->syncItems($doc, $lines, $data['tax_type']);
            $this->recalculate($doc->id);
            $doc = $this->repository->findById($doc->id);

            if ($this->exceedsCreditLimit($customer, (float) $doc->grand_total)) {
                $doc->forceFill([
                    'status' => SalesOrderStatus::PendingApproval,
                    'credit_hold' => true,
                ])->save();
            }

            return $this->repository->findById($doc->id);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): SalesOrder
    {
        return DB::transaction(function () use ($id, $data): SalesOrder {
            $doc = $this->repository->findById($id);
            if (! $doc->status->isEditable()) {
                throw ValidationException::withMessages(['sales_order' => 'Only draft sales orders can be edited.']);
            }

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);
            $customer = $this->assertCustomer((int) ($data['customer_id'] ?? $doc->customer_id));
            if (isset($data['warehouse_id'])) {
                $this->assertLeafWarehouse((int) $data['warehouse_id']);
            }
            $this->assertDeliveryDate(array_merge([
                'document_date' => $doc->document_date->toDateString(),
                'expected_delivery_date' => $doc->expected_delivery_date->toDateString(),
            ], $data));

            $pos = (int) ($data['place_of_supply_state_id'] ?? $doc->place_of_supply_state_id);
            $taxCtx = $this->tax->resolveContext($pos);
            $data['tax_type'] = $taxCtx['tax_type'];
            $data['updated_by'] = Auth::id();

            $this->repository->update($id, $data);
            $this->syncItems($doc, $lines, $data['tax_type']);
            $this->recalculate($id);
            $doc = $this->repository->findById($id);

            if ($this->exceedsCreditLimit($customer, (float) $doc->grand_total)) {
                $doc->forceFill([
                    'status' => SalesOrderStatus::PendingApproval,
                    'credit_hold' => true,
                ])->save();
            } else {
                $doc->forceFill([
                    'status' => SalesOrderStatus::Draft,
                    'credit_hold' => false,
                ])->save();
            }

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $doc = $this->repository->findById($id);
        if ($doc->status !== SalesOrderStatus::Draft) {
            throw ValidationException::withMessages(['sales_order' => 'Only draft sales orders can be deleted.']);
        }

        return $this->repository->delete($id);
    }

    public function confirm(int $id): SalesOrder
    {
        return DB::transaction(function () use ($id): SalesOrder {
            $doc = SalesOrder::query()->with('items')->lockForUpdate()->findOrFail($id);

            if (! in_array($doc->status, [SalesOrderStatus::Draft, SalesOrderStatus::PendingApproval], true)) {
                throw ValidationException::withMessages(['sales_order' => 'Only draft or pending orders can be confirmed.']);
            }
            if ($doc->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one line before confirming.']);
            }

            $this->approvals->assertCanApprove('sales_order', [
                'id' => $doc->id,
                'grand_total' => (float) $doc->grand_total,
            ]);

            $old = $doc->status->value;
            $this->adjustCommitted($doc, true);
            $doc->forceFill([
                'status' => SalesOrderStatus::Confirmed,
                'credit_hold' => false,
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Sales order {$doc->document_no} confirmed.",
                subject: $doc,
                properties: ['old_status' => $old, 'new_status' => SalesOrderStatus::Confirmed->value],
                logName: 'sales'
            );

            return $this->repository->findById($id);
        });
    }

    public function cancel(int $id): SalesOrder
    {
        return DB::transaction(function () use ($id): SalesOrder {
            $doc = SalesOrder::query()->with('items')->lockForUpdate()->findOrFail($id);
            if (! $doc->status->isCancellable()) {
                throw ValidationException::withMessages(['sales_order' => 'This sales order cannot be cancelled.']);
            }

            $old = $doc->status->value;
            if ($doc->status === SalesOrderStatus::Confirmed) {
                $this->adjustCommitted($doc, false);
            }

            $doc->forceFill([
                'status' => SalesOrderStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Sales order {$doc->document_no} cancelled.",
                subject: $doc,
                properties: ['old_status' => $old, 'new_status' => SalesOrderStatus::Cancelled->value],
                logName: 'sales'
            );

            return $this->repository->findById($id);
        });
    }

    public function refreshFulfillmentStatus(SalesOrder $order): void
    {
        $order->loadMissing('items');
        $ordered = (float) $order->items->sum('quantity');
        $invoiced = (float) $order->items->sum('invoiced_qty');
        $delivered = (float) $order->items->sum('delivered_qty');

        if ($invoiced <= 0 && $delivered <= 0) {
            return;
        }

        $status = SalesOrderStatus::Confirmed;
        if ($invoiced + 0.00005 >= $ordered) {
            $status = SalesOrderStatus::Invoiced;
        } elseif ($delivered + 0.00005 >= $ordered) {
            $status = SalesOrderStatus::Delivered;
        } elseif ($delivered > 0 || $invoiced > 0) {
            $status = SalesOrderStatus::PartiallyDelivered;
        }

        $order->forceFill(['status' => $status, 'updated_by' => Auth::id()])->save();
    }

    /** @param  list<array<string, mixed>>  $lines */
    protected function syncItems(SalesOrder $doc, array $lines, string $taxType): void
    {
        $doc->items()->delete();
        foreach (array_values($lines) as $index => $line) {
            if (empty($line['item_id']) || empty($line['quantity'])) {
                continue;
            }
            $item = Item::query()->findOrFail((int) $line['item_id']);
            if (! $item->is_sellable) {
                throw ValidationException::withMessages(['items' => "Item {$item->item_code} is not sellable."]);
            }
            $qty = round((float) $line['quantity'], 4);
            $uomId = (int) ($line['uom_id'] ?? $item->stock_uom_id);
            $baseQty = $this->safeBaseQty($item, $qty, $uomId);
            $rate = round((float) ($line['rate'] ?? $item->selling_price ?? 0), 4);
            $disc = round((float) ($line['discount_percent'] ?? 0), 2);
            $gst = round((float) ($line['gst_rate'] ?? $item->gst_rate ?? 0), 2);
            $calc = $this->tax->calculateLine($qty, $rate, $disc, $gst, $taxType);

            SalesOrderItem::query()->create(array_merge([
                'sales_order_id' => $doc->id,
                'item_id' => $item->id,
                'uom_id' => $uomId,
                'description' => $line['description'] ?? $item->item_name,
                'quantity' => $qty,
                'base_qty' => $baseQty,
                'rate' => $rate,
                'discount_percent' => $disc,
                'gst_rate' => $gst,
                'delivered_qty' => 0,
                'invoiced_qty' => 0,
                'sort_order' => $index,
            ], $calc));
        }
        if ($doc->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Add at least one sales order line.']);
        }
    }

    protected function safeBaseQty(Item $item, float $qty, int $uomId): float
    {
        try {
            return $this->uom->toStockQty($item, $qty, $uomId);
        } catch (\Throwable) {
            return $qty;
        }
    }

    protected function recalculate(int $id): void
    {
        $doc = SalesOrder::query()->with('items')->findOrFail($id);
        $doc->forceFill([
            'subtotal' => round((float) $doc->items->sum('taxable_amount'), 2),
            'discount_total' => round((float) $doc->items->sum('discount_amount'), 2),
            'tax_total' => round((float) $doc->items->sum('tax_amount'), 2),
            'grand_total' => round((float) $doc->items->sum('line_total'), 2),
        ])->save();
    }

    protected function adjustCommitted(SalesOrder $order, bool $increase): void
    {
        foreach ($order->items as $line) {
            $balance = StockBalance::query()->firstOrCreate(
                [
                    'item_id' => $line->item_id,
                    'warehouse_id' => $order->warehouse_id,
                    'batch_key' => 0,
                ],
                [
                    'batch_id' => null,
                    'qty' => 0,
                    'committed_qty' => 0,
                    'on_order_qty' => 0,
                    'value' => 0,
                ]
            );

            $delta = (float) $line->quantity * ($increase ? 1 : -1);
            $balance->forceFill([
                'committed_qty' => max(0, round((float) $balance->committed_qty + $delta, 4)),
            ])->save();
        }
    }

    protected function exceedsCreditLimit(Party $customer, float $orderValue): bool
    {
        if ($customer->unlimited_credit) {
            return false;
        }
        $limit = (float) $customer->credit_limit;
        if ($limit <= 0) {
            return $orderValue > 0;
        }

        $openOrders = (float) SalesOrder::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [
                SalesOrderStatus::Confirmed->value,
                SalesOrderStatus::PendingApproval->value,
                SalesOrderStatus::PartiallyDelivered->value,
            ])
            ->sum('grand_total');

        return ($openOrders + $orderValue) > $limit;
    }

    protected function assertCustomer(int $customerId): Party
    {
        $customer = Party::query()->findOrFail($customerId);
        if (! in_array($customer->party_type, [PartyType::Customer, PartyType::Both], true)) {
            throw ValidationException::withMessages(['customer_id' => 'Selected party must be a customer.']);
        }
        if ($customer->status === PartyStatus::Blocked) {
            throw ValidationException::withMessages(['customer_id' => 'Customer is blocked.']);
        }
        if ($customer->status !== PartyStatus::Active) {
            throw ValidationException::withMessages(['customer_id' => 'Customer is inactive.']);
        }

        return $customer;
    }

    protected function assertLeafWarehouse(int $warehouseId): void
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        if (! $warehouse->is_leaf || ! $warehouse->is_active) {
            throw ValidationException::withMessages(['warehouse_id' => 'Warehouse must be an active leaf warehouse.']);
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function assertDeliveryDate(array $data): void
    {
        if (! empty($data['document_date']) && ! empty($data['expected_delivery_date'])
            && $data['expected_delivery_date'] < $data['document_date']) {
            throw ValidationException::withMessages([
                'expected_delivery_date' => 'Expected delivery cannot be before the order date.',
            ]);
        }
    }
}

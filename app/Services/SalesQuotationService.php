<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\QuotationStatus;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationItem;
use App\Models\Warehouse;
use App\Repositories\Interfaces\SalesQuotationRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sales quotation business logic (M06 / US-M06-01, US-M06-02).
 */
class SalesQuotationService
{
    public function __construct(
        protected SalesQuotationRepositoryInterface $repository,
        protected SalesTaxCalculator $tax,
        protected NumberingService $numbering,
        protected PriceListService $priceLists
    ) {}

    /** @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): SalesQuotation
    {
        return $this->repository->findById($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SalesQuotation
    {
        return DB::transaction(function () use ($data): SalesQuotation {
            $lines = $data['items'] ?? [];
            unset($data['items']);
            $this->assertCustomer((int) $data['customer_id']);
            $this->assertLeafWarehouse((int) $data['warehouse_id']);

            $taxCtx = $this->tax->resolveContext((int) $data['place_of_supply_state_id']);
            $data['tax_type'] = $taxCtx['tax_type'];
            $data['document_no'] = $this->numbering->next(DocumentSeriesType::Quotation);
            $data['status'] = QuotationStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['subtotal'] = 0;
            $data['discount_total'] = 0;
            $data['tax_total'] = 0;
            $data['grand_total'] = 0;

            $doc = $this->repository->create($data);
            $this->syncItems($doc, $lines, $data['tax_type']);
            $this->recalculate($doc->id);

            return $this->repository->findById($doc->id);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): SalesQuotation
    {
        return DB::transaction(function () use ($id, $data): SalesQuotation {
            $doc = $this->repository->findById($id);
            if (! $doc->status->isEditable()) {
                throw ValidationException::withMessages(['quotation' => 'Only draft or sent quotations can be edited.']);
            }

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);
            if (isset($data['customer_id'])) {
                $this->assertCustomer((int) $data['customer_id']);
            }
            if (isset($data['warehouse_id'])) {
                $this->assertLeafWarehouse((int) $data['warehouse_id']);
            }

            $pos = (int) ($data['place_of_supply_state_id'] ?? $doc->place_of_supply_state_id);
            $taxCtx = $this->tax->resolveContext($pos);
            $data['tax_type'] = $taxCtx['tax_type'];
            $data['updated_by'] = Auth::id();

            $this->repository->update($id, $data);
            $this->syncItems($doc, $lines, $data['tax_type']);
            $this->recalculate($id);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $doc = $this->repository->findById($id);
        if ($doc->status !== QuotationStatus::Draft) {
            throw ValidationException::withMessages(['quotation' => 'Only draft quotations can be deleted.']);
        }

        return $this->repository->delete($id);
    }

    public function markSent(int $id): SalesQuotation
    {
        $doc = $this->repository->findById($id);
        if ($doc->status !== QuotationStatus::Draft) {
            throw ValidationException::withMessages(['quotation' => 'Only draft quotations can be marked sent.']);
        }
        $doc->forceFill(['status' => QuotationStatus::Sent, 'updated_by' => Auth::id()])->save();

        return $this->repository->findById($id);
    }

    public function accept(int $id): SalesQuotation
    {
        $doc = $this->repository->findById($id);
        if ($doc->status !== QuotationStatus::Sent) {
            throw ValidationException::withMessages(['quotation' => 'Only sent quotations can be accepted.']);
        }
        $doc->forceFill(['status' => QuotationStatus::Accepted, 'updated_by' => Auth::id()])->save();

        return $this->repository->findById($id);
    }

    /**
     * Convert quotation to sales order (US-M06-02).
     */
    public function convertToSalesOrder(int $id): \App\Models\SalesOrder
    {
        return DB::transaction(function () use ($id) {
            $doc = $this->repository->findById($id);
            if (! $doc->status->canConvert()) {
                throw ValidationException::withMessages(['quotation' => 'Quotation cannot be converted in its current status.']);
            }

            $order = app(SalesOrderService::class)->create([
                'document_date' => now()->toDateString(),
                'customer_id' => $doc->customer_id,
                'warehouse_id' => $doc->warehouse_id,
                'place_of_supply_state_id' => $doc->place_of_supply_state_id,
                'quotation_id' => $doc->id,
                'expected_delivery_date' => now()->addDays(7)->toDateString(),
                'remarks' => $doc->remarks,
                'items' => $doc->items->map(fn (SalesQuotationItem $line): array => [
                    'item_id' => $line->item_id,
                    'uom_id' => $line->uom_id,
                    'description' => $line->description,
                    'quantity' => (float) $line->quantity,
                    'rate' => (float) $line->rate,
                    'discount_percent' => (float) $line->discount_percent,
                    'gst_rate' => (float) $line->gst_rate,
                ])->all(),
            ]);

            $doc->forceFill([
                'status' => QuotationStatus::Converted,
                'converted_sales_order_id' => $order->id,
                'updated_by' => Auth::id(),
            ])->save();

            return $order;
        });
    }

    /** @param  list<array<string, mixed>>  $lines */
    protected function syncItems(SalesQuotation $doc, array $lines, string $taxType): void
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
            $rate = array_key_exists('rate', $line) && $line['rate'] !== null && $line['rate'] !== ''
                ? round((float) $line['rate'], 4)
                : $this->priceLists->resolveRate($doc->customer_id, $item->id, $qty);
            $disc = round((float) ($line['discount_percent'] ?? 0), 2);
            $gst = round((float) ($line['gst_rate'] ?? $item->gst_rate ?? 0), 2);
            $calc = $this->tax->calculateLine($qty, $rate, $disc, $gst, $taxType);

            SalesQuotationItem::query()->create(array_merge([
                'sales_quotation_id' => $doc->id,
                'item_id' => $item->id,
                'uom_id' => (int) ($line['uom_id'] ?? $item->stock_uom_id),
                'description' => $line['description'] ?? $item->item_name,
                'quantity' => $qty,
                'rate' => $rate,
                'discount_percent' => $disc,
                'gst_rate' => $gst,
                'sort_order' => $index,
            ], $calc));
        }
        if ($doc->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Add at least one quotation line.']);
        }
    }

    protected function recalculate(int $id): void
    {
        $doc = SalesQuotation::query()->with('items')->findOrFail($id);
        $doc->forceFill([
            'subtotal' => round((float) $doc->items->sum('taxable_amount'), 2),
            'discount_total' => round((float) $doc->items->sum('discount_amount'), 2),
            'tax_total' => round((float) $doc->items->sum('tax_amount'), 2),
            'grand_total' => round((float) $doc->items->sum('line_total'), 2),
        ])->save();
    }

    protected function assertCustomer(int $customerId): void
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
    }

    protected function assertLeafWarehouse(int $warehouseId): void
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        if (! $warehouse->is_leaf || ! $warehouse->is_active) {
            throw ValidationException::withMessages(['warehouse_id' => 'Warehouse must be an active leaf warehouse.']);
        }
    }
}

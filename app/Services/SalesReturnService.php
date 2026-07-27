<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\DocumentStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Repositories\Interfaces\SalesReturnRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sales return (credit note) business logic — closes the outbound stock loop.
 */
class SalesReturnService
{
    public function __construct(
        protected SalesReturnRepositoryInterface $repository,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): SalesReturn
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data): SalesReturn {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $invoice = $this->loadInvoice($data['sales_invoice_id'] ?? null);
            if ($invoice !== null) {
                $data['customer_id'] = $invoice->customer_id;
                $data['warehouse_id'] = (int) ($data['warehouse_id'] ?? $invoice->warehouse_id);
                $this->assertDates($invoice, $data);
            }

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::SalesReturn);
            $data['status'] = DocumentStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $return = $this->repository->create($data);
            $this->syncItems($return, $invoice, $lines);
            $this->recalculate($return);

            return $this->repository->findById($return->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): SalesReturn
    {
        return DB::transaction(function () use ($id, $data): SalesReturn {
            $return = $this->repository->findById($id);
            $this->assertDraft($return);

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status'], $data['sales_invoice_id'], $data['customer_id']);

            $invoice = $this->loadInvoice($return->sales_invoice_id);
            if ($invoice !== null) {
                $this->assertDates($invoice, array_merge([
                    'document_date' => $return->document_date->toDateString(),
                ], $data));
            }

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);

            $return->refresh();
            $this->syncItems($return, $invoice, $lines);
            $this->recalculate($return);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $return = $this->repository->findById($id);
        $this->assertDraft($return);

        return $this->repository->delete($id);
    }

    /**
     * Post the return: stock comes back into the warehouse.
     */
    public function post(int $id): SalesReturn
    {
        return DB::transaction(function () use ($id): SalesReturn {
            $return = SalesReturn::query()->with('items.item')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($return);

            if ($return->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one return line before posting.',
                ]);
            }

            foreach ($return->items as $line) {
                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $return->warehouse_id,
                    'batch_id' => $line->batch_id,
                    'transaction_type' => StockTransactionType::SalesReturn,
                    'posting_at' => $return->document_date->copy()->startOfDay(),
                    'qty_in' => (float) $line->quantity,
                    'qty_out' => 0,
                    'rate' => (float) $line->rate,
                    'source' => $return,
                    'remarks' => $return->document_no,
                ]);
            }

            $return->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Reverse the ledger effect and cancel the document.
     */
    public function cancel(int $id): SalesReturn
    {
        return DB::transaction(function () use ($id): SalesReturn {
            $return = SalesReturn::query()->lockForUpdate()->findOrFail($id);

            if ($return->status === DocumentStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'sales_return' => 'This return is already cancelled.',
                ]);
            }

            if ($return->status === DocumentStatus::Posted) {
                $this->ledger->reverseSource($return, 'Cancellation of '.$return->document_no);
            }

            $return->forceFill([
                'status' => DocumentStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Invoice lines still open for return.
     *
     * @return list<array<string, mixed>>
     */
    public function returnableLinesForInvoice(int $salesInvoiceId, ?int $ignoreReturnId = null): array
    {
        $invoice = $this->loadInvoice($salesInvoiceId);
        if ($invoice === null) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'Invoice not found.',
            ]);
        }

        $returned = $this->repository->returnedQtyByInvoiceItem($invoice->items->pluck('id')->all(), $ignoreReturnId);

        $lines = [];
        foreach ($invoice->items as $line) {
            $open = round((float) $line->quantity - (float) ($returned[$line->id] ?? 0), 4);
            if ($open <= 0) {
                continue;
            }

            $lines[] = [
                'sales_invoice_item_id' => $line->id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'uom_id' => $line->uom_id,
                'invoiced_qty' => round((float) $line->quantity, 4),
                'returned_qty' => round((float) ($returned[$line->id] ?? 0), 4),
                'open_qty' => $open,
                'quantity' => $open,
                'rate' => round((float) $line->rate, 4),
                'gst_rate' => round((float) $line->gst_rate, 2),
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'Nothing is left to return on this invoice.',
            ]);
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(SalesReturn $return, ?SalesInvoice $invoice, array $lines): void
    {
        $return->items()->delete();
        $invoiceItems = $invoice?->items->keyBy('id');
        $returned = $invoice !== null
            ? $this->repository->returnedQtyByInvoiceItem($invoiceItems->keys()->all(), $return->id)
            : [];

        foreach (array_values($lines) as $index => $line) {
            $quantity = round((float) ($line['quantity'] ?? 0), 4);
            if ($quantity <= 0) {
                continue;
            }

            $invoiceLine = null;
            if (! empty($line['sales_invoice_item_id'])) {
                if ($invoiceItems === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Invoice lines can only be returned against the linked invoice.',
                    ]);
                }

                $invoiceLine = $invoiceItems->get((int) $line['sales_invoice_item_id']);
                if ($invoiceLine === null) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more lines do not belong to the selected invoice.',
                    ]);
                }

                $open = round((float) $invoiceLine->quantity - (float) ($returned[$invoiceLine->id] ?? 0), 4);
                if ($quantity - $open > 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'Return quantity for %s exceeds the returnable quantity of %s.',
                            $invoiceLine->item?->item_code ?? 'the item',
                            number_format($open, 4, '.', '')
                        ),
                    ]);
                }
            }

            $itemId = (int) ($invoiceLine?->item_id ?? $line['item_id'] ?? 0);
            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Item is required on every return line.',
                ]);
            }

            $batchId = $this->resolveBatchId($itemId, $line);
            $rate = round((float) ($line['rate'] ?? $invoiceLine?->rate ?? 0), 4);
            $gstRate = round((float) ($line['gst_rate'] ?? $invoiceLine?->gst_rate ?? 0), 2);
            $taxable = round($quantity * $rate, 2);
            $tax = round($taxable * $gstRate / 100, 2);

            SalesReturnItem::query()->create([
                'sales_return_id' => $return->id,
                'sales_invoice_item_id' => $invoiceLine?->id,
                'item_id' => $itemId,
                'uom_id' => (int) ($line['uom_id'] ?? $invoiceLine?->uom_id),
                'batch_id' => $batchId,
                'quantity' => $quantity,
                'rate' => $rate,
                'gst_rate' => $gstRate,
                'taxable_amount' => $taxable,
                'tax_amount' => $tax,
                'line_total' => round($taxable + $tax, 2),
                'sort_order' => $index,
            ]);
        }

        if ($return->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one sales return line.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected function resolveBatchId(int $itemId, array $line): ?int
    {
        $batchId = ! empty($line['batch_id']) ? (int) $line['batch_id'] : null;
        if ($batchId !== null) {
            return $batchId;
        }

        $item = Item::query()->findOrFail($itemId);
        if (in_array($item->tracking_type, [TrackingType::Batch, TrackingType::BatchSerial], true)) {
            throw ValidationException::withMessages([
                'items' => 'Batch is required for batch-tracked item '.$item->item_code.'.',
            ]);
        }

        return null;
    }

    protected function recalculate(SalesReturn $return): void
    {
        $items = $return->items()->get();
        $subtotal = round((float) $items->sum(fn (SalesReturnItem $line) => (float) $line->taxable_amount), 2);
        $taxTotal = round((float) $items->sum(fn (SalesReturnItem $line) => (float) $line->tax_amount), 2);

        $return->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => round($subtotal + $taxTotal, 2),
        ])->save();
    }

    protected function loadInvoice(int|string|null $salesInvoiceId): ?SalesInvoice
    {
        if (empty($salesInvoiceId)) {
            return null;
        }

        $invoice = SalesInvoice::query()
            ->with(['items.item:id,item_code,item_name,tracking_type'])
            ->findOrFail((int) $salesInvoiceId);

        if ($invoice->status === SalesInvoiceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'Cancelled invoices cannot be returned.',
            ]);
        }

        return $invoice;
    }

    protected function assertDraft(SalesReturn $return): void
    {
        if ($return->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'sales_return' => 'Only draft sales returns can be modified.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertDates(SalesInvoice $invoice, array $data): void
    {
        if (! empty($data['document_date']) && $data['document_date'] < $invoice->document_date->toDateString()) {
            throw ValidationException::withMessages([
                'document_date' => 'Return date cannot be before the invoice date.',
            ]);
        }
    }
}

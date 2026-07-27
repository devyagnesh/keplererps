<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentSeriesType;
use App\Enums\StockTransactionType;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Repositories\Interfaces\StockTransferRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Inter-warehouse stock transfer business logic.
 */
class StockTransferService
{
    public function __construct(
        protected StockTransferRepositoryInterface $repository,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering
    ) {}

    /** @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): StockTransfer
    {
        return $this->repository->findById($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data): StockTransfer {
            $this->assertDistinctWarehouses($data);
            $lines = $data['items'] ?? [];
            unset($data['items']);
            $data['document_no'] = $this->numbering->next(DocumentSeriesType::StockTransfer);
            $data['status'] = DocumentStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $doc = $this->repository->create($data);
            $this->syncItems($doc, $lines);

            return $doc->fresh(['fromWarehouse', 'toWarehouse', 'items.item']);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): StockTransfer
    {
        return DB::transaction(function () use ($id, $data): StockTransfer {
            $doc = $this->repository->findById($id);
            $this->assertDraft($doc);
            $this->assertDistinctWarehouses($data);
            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);
            $data['updated_by'] = Auth::id();

            $updated = $this->repository->update($id, $data);
            $this->syncItems($updated, $lines);

            return $updated->fresh(['fromWarehouse', 'toWarehouse', 'items.item']);
        });
    }

    public function delete(int $id): bool
    {
        $doc = $this->repository->findById($id);
        $this->assertDraft($doc);

        return $this->repository->delete($id);
    }

    public function post(int $id): StockTransfer
    {
        return DB::transaction(function () use ($id): StockTransfer {
            $doc = StockTransfer::query()->with('items.item')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($doc);

            if ($doc->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one line before posting.']);
            }

            foreach ($doc->items as $line) {
                $rate = (float) $line->rate;
                if ($rate <= 0) {
                    $rate = $this->ledger->averageRate($line->item_id, $doc->from_warehouse_id, $line->batch_id);
                }

                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $doc->from_warehouse_id,
                    'batch_id' => $line->batch_id,
                    'transaction_type' => StockTransactionType::StockTransferOut,
                    'posting_at' => $doc->document_date->copy()->startOfDay(),
                    'qty_in' => 0,
                    'qty_out' => (float) $line->quantity,
                    'rate' => $rate,
                    'source' => $doc,
                    'remarks' => $doc->document_no.' OUT',
                ]);

                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $doc->to_warehouse_id,
                    'batch_id' => $line->batch_id,
                    'transaction_type' => StockTransactionType::StockTransferIn,
                    'posting_at' => $doc->document_date->copy()->startOfDay(),
                    'qty_in' => (float) $line->quantity,
                    'qty_out' => 0,
                    'rate' => $rate,
                    'source' => $doc,
                    'remarks' => $doc->document_no.' IN',
                ]);
            }

            $doc->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            return $doc->fresh(['fromWarehouse', 'toWarehouse', 'items.item']);
        });
    }

    /** @param  list<array<string, mixed>>  $lines */
    protected function syncItems(StockTransfer $doc, array $lines): void
    {
        $doc->items()->delete();
        foreach ($lines as $line) {
            if (empty($line['item_id']) || empty($line['quantity'])) {
                continue;
            }
            $qty = round((float) $line['quantity'], 4);
            $rate = round((float) ($line['rate'] ?? 0), 4);
            StockTransferItem::query()->create([
                'stock_transfer_id' => $doc->id,
                'item_id' => (int) $line['item_id'],
                'batch_id' => ! empty($line['batch_id']) ? (int) $line['batch_id'] : null,
                'quantity' => $qty,
                'rate' => $rate,
                'value' => round($qty * $rate, 2),
            ]);
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function assertDistinctWarehouses(array $data): void
    {
        if ((int) ($data['from_warehouse_id'] ?? 0) === (int) ($data['to_warehouse_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Source and destination warehouses must be different.',
            ]);
        }
    }

    protected function assertDraft(StockTransfer $doc): void
    {
        if ($doc->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'stock_transfer' => 'Only draft transfers can be modified.',
            ]);
        }
    }
}

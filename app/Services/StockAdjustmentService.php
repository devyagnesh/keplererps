<?php

namespace App\Services;

use App\Enums\AdjustmentDirection;
use App\Enums\DocumentStatus;
use App\Enums\DocumentSeriesType;
use App\Enums\StockTransactionType;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Repositories\Interfaces\StockAdjustmentRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock adjustment document business logic (US-M08-03).
 */
class StockAdjustmentService
{
    public function __construct(
        protected StockAdjustmentRepositoryInterface $repository,
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

    public function find(int $id): StockAdjustment
    {
        return $this->repository->findById($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data): StockAdjustment {
            $lines = $data['items'] ?? [];
            unset($data['items']);
            $data['document_no'] = $this->numbering->next(DocumentSeriesType::StockAdjustment);
            $data['status'] = DocumentStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $doc = $this->repository->create($data);
            $this->syncItems($doc, $lines);

            return $doc->fresh(['warehouse', 'items.item']);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($id, $data): StockAdjustment {
            $doc = $this->repository->findById($id);
            $this->assertDraft($doc);
            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);
            $data['updated_by'] = Auth::id();

            $updated = $this->repository->update($id, $data);
            $this->syncItems($updated, $lines);

            return $updated->fresh(['warehouse', 'items.item']);
        });
    }

    public function delete(int $id): bool
    {
        $doc = $this->repository->findById($id);
        $this->assertDraft($doc);

        return $this->repository->delete($id);
    }

    public function post(int $id): StockAdjustment
    {
        return DB::transaction(function () use ($id): StockAdjustment {
            $doc = StockAdjustment::query()->with('items.item')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($doc);

            if ($doc->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one line before posting.']);
            }

            foreach ($doc->items as $line) {
                $isIncrease = $line->direction === AdjustmentDirection::Increase;
                $rate = (float) $line->rate;
                if (! $isIncrease && $rate <= 0) {
                    $rate = $this->ledger->averageRate($line->item_id, $doc->warehouse_id, $line->batch_id);
                }

                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $doc->warehouse_id,
                    'batch_id' => $line->batch_id,
                    'transaction_type' => StockTransactionType::StockAdjustment,
                    'posting_at' => $doc->document_date->copy()->startOfDay(),
                    'qty_in' => $isIncrease ? (float) $line->quantity : 0,
                    'qty_out' => $isIncrease ? 0 : (float) $line->quantity,
                    'rate' => $rate,
                    'source' => $doc,
                    'remarks' => $doc->reason,
                ]);
            }

            $doc->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            return $doc->fresh(['warehouse', 'items.item']);
        });
    }

    /** @param  list<array<string, mixed>>  $lines */
    protected function syncItems(StockAdjustment $doc, array $lines): void
    {
        $doc->items()->delete();
        foreach ($lines as $line) {
            if (empty($line['item_id']) || empty($line['quantity']) || empty($line['direction'])) {
                continue;
            }
            $qty = round((float) $line['quantity'], 4);
            $rate = round((float) ($line['rate'] ?? 0), 4);
            StockAdjustmentItem::query()->create([
                'stock_adjustment_id' => $doc->id,
                'item_id' => (int) $line['item_id'],
                'batch_id' => ! empty($line['batch_id']) ? (int) $line['batch_id'] : null,
                'direction' => $line['direction'],
                'quantity' => $qty,
                'rate' => $rate,
                'value' => round($qty * $rate, 2),
            ]);
        }
    }

    protected function assertDraft(StockAdjustment $doc): void
    {
        if ($doc->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'stock_adjustment' => 'Only draft adjustments can be modified.',
            ]);
        }
    }
}

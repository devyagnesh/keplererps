<?php

namespace App\Repositories\Eloquent;

use App\Models\StockAdjustment;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\StockAdjustmentRepositoryInterface;

/**
 * Eloquent stock adjustment repository.
 */
class StockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): StockAdjustment
    {
        return StockAdjustment::query()
            ->with(['warehouse:id,code,name', 'items.item:id,item_code,item_name,tracking_type'])
            ->findOrFail($id);
    }

    public function create(array $data): StockAdjustment
    {
        return StockAdjustment::query()->create($data);
    }

    public function update(int $id, array $data): StockAdjustment
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh(['warehouse', 'items.item']);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function nextDocumentNo(string $prefix = 'ADJ'): string
    {
        $latest = StockAdjustment::query()
            ->withTrashed()
            ->where('document_no', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('document_no');

        $sequence = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            StockAdjustment::query()->with(['warehouse:id,code,name']),
            ['id', 'document_no', 'document_date', 'warehouse_id', 'status', 'created_at'],
            ['document_no', 'reason'],
            function (StockAdjustment $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'warehouse' => $doc->warehouse?->name ?? '—',
                    'reason' => e($doc->reason),
                    'status' => $doc->status->label(),
                    'action' => view('admin.stock-adjustments.partials.actions', ['stockAdjustment' => $doc])->render(),
                ];
            },
            $params
        );
    }
}

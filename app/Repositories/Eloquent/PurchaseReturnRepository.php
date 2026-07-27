<?php

namespace App\Repositories\Eloquent;

use App\Enums\DocumentStatus;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\PurchaseReturnRepositoryInterface;

/**
 * Eloquent purchase return repository.
 */
class PurchaseReturnRepository implements PurchaseReturnRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): PurchaseReturn
    {
        return PurchaseReturn::query()
            ->with([
                'supplier:id,party_code,party_name',
                'goodsReceipt:id,document_no,document_date,warehouse_id',
                'warehouse:id,code,name',
                'items.item:id,item_code,item_name,tracking_type',
                'items.batch:id,batch_no,expiry_date',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): PurchaseReturn
    {
        return PurchaseReturn::query()->create($data);
    }

    public function update(int $id, array $data): PurchaseReturn
    {
        $record = PurchaseReturn::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) PurchaseReturn::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = PurchaseReturn::query()->with([
            'supplier:id,party_code,party_name',
            'goodsReceipt:id,document_no',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'supplier_id', 'grand_total', 'status', 'created_at'],
            ['document_no', 'reason'],
            function (PurchaseReturn $return): array {
                return [
                    'id' => $return->id,
                    'document_no' => $return->document_no,
                    'document_date' => $return->document_date?->format('Y-m-d'),
                    'supplier' => $return->supplier
                        ? $return->supplier->party_code.' — '.e($return->supplier->party_name)
                        : '—',
                    'goods_receipt' => $return->goodsReceipt?->document_no ?? '—',
                    'reason' => e($return->reason),
                    'grand_total' => number_format((float) $return->grand_total, 2, '.', ''),
                    'status' => $return->status->label(),
                    'action' => view('admin.purchase-returns.partials.actions', ['return' => $return])->render(),
                ];
            },
            $params
        );
    }

    public function returnedQtyByGrnItem(array $goodsReceiptItemIds, ?int $ignoreReturnId = null): array
    {
        if ($goodsReceiptItemIds === []) {
            return [];
        }

        return PurchaseReturnItem::query()
            ->selectRaw('goods_receipt_item_id, SUM(quantity) as returned_qty')
            ->whereIn('goods_receipt_item_id', $goodsReceiptItemIds)
            ->whereHas('purchaseReturn', function ($query) use ($ignoreReturnId): void {
                $query->where('status', '!=', DocumentStatus::Cancelled->value);
                if ($ignoreReturnId !== null) {
                    $query->where('id', '!=', $ignoreReturnId);
                }
            })
            ->groupBy('goods_receipt_item_id')
            ->pluck('returned_qty', 'goods_receipt_item_id')
            ->map(fn ($qty): float => round((float) $qty, 4))
            ->all();
    }
}

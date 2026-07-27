<?php

namespace App\Repositories\Eloquent;

use App\Models\GoodsReceipt;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\GoodsReceiptRepositoryInterface;

/**
 * Eloquent goods receipt repository.
 */
class GoodsReceiptRepository implements GoodsReceiptRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): GoodsReceipt
    {
        return GoodsReceipt::query()
            ->with([
                'purchaseOrder:id,document_no,document_date,status',
                'supplier:id,party_code,party_name',
                'warehouse:id,code,name,branch_id',
                'items.item:id,item_code,item_name,tracking_type,expiry_tracking,requires_inspection',
                'items.purchaseOrderItem',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): GoodsReceipt
    {
        return GoodsReceipt::query()->create($data);
    }

    public function update(int $id, array $data): GoodsReceipt
    {
        $record = GoodsReceipt::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) GoodsReceipt::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = GoodsReceipt::query()->with([
            'purchaseOrder:id,document_no',
            'supplier:id,party_code,party_name',
            'warehouse:id,code,name',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'purchase_order_id', 'status', 'created_at'],
            ['document_no', 'supplier_invoice_no'],
            function (GoodsReceipt $grn): array {
                return [
                    'id' => $grn->id,
                    'document_no' => $grn->document_no,
                    'document_date' => $grn->document_date?->format('Y-m-d'),
                    'purchase_order' => $grn->purchaseOrder?->document_no ?? '—',
                    'supplier' => $grn->supplier
                        ? $grn->supplier->party_code.' — '.$grn->supplier->party_name
                        : '—',
                    'status' => $grn->status->label(),
                    'action' => view('admin.goods-receipts.partials.actions', ['goodsReceipt' => $grn])->render(),
                ];
            },
            $params
        );
    }
}

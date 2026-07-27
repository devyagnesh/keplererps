<?php

namespace App\Repositories\Eloquent;

use App\Models\QcInspection;
use App\Repositories\Interfaces\QcInspectionRepositoryInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Eloquent QC inspection repository.
 */
class QcInspectionRepository implements QcInspectionRepositoryInterface
{
    public function __construct(protected QcInspection $model) {}

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): QcInspection
    {
        return $this->model->newQuery()
            ->with([
                'item:id,item_code,item_name,stock_uom_id',
                'item.stockUom:id,code,name',
                'template.parameters',
                'quarantineWarehouse:id,code,name,branch_id',
                'targetWarehouse:id,code,name',
                'batch:id,batch_no',
                'source',
                'readings',
                'inspector:id,name',
            ])
            ->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): QcInspection
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): QcInspection
    {
        $inspection = $this->findById($id);
        $data['updated_by'] = Auth::id();
        $inspection->update($data);

        return $this->findById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getForDataTable(array $params): array
    {
        $draw = (int) ($params['draw'] ?? 1);
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 25);
        $search = trim((string) data_get($params, 'search.value', ''));
        $orderCol = (int) data_get($params, 'order.0.column', 0);
        $orderDir = strtolower((string) data_get($params, 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = ['id', 'document_no', 'document_date', 'inspection_type', 'status', 'id'];
        $orderBy = $columns[$orderCol] ?? 'id';

        $base = $this->model->newQuery()->with(['item:id,item_code,item_name']);
        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function ($q) use ($search): void {
                $q->where('document_no', 'like', "%{$search}%")
                    ->orWhereHas(
                        'item',
                        fn ($iq) => $iq->where('item_code', 'like', "%{$search}%")
                            ->orWhere('item_name', 'like', "%{$search}%")
                    );
            });
        }

        if (! empty($params['status'])) {
            $base->where('status', $params['status']);
        }

        $recordsFiltered = (clone $base)->count();
        $rows = $base->orderBy($orderBy, $orderDir)->skip($start)->take($length)->get();

        $data = $rows->map(function (QcInspection $row): array {
            return [
                'id' => $row->id,
                'document_no' => e($row->document_no),
                'document_date' => $row->document_date?->format('d M Y'),
                'item' => e(($row->item?->item_code ?? '').' — '.($row->item?->item_name ?? '')),
                'qty' => number_format((float) $row->lot_quantity, 4),
                'inspection_type' => e($row->inspection_type->label()),
                'status' => e($row->status->label()),
                'action' => view('admin.qc-inspections.partials.actions', ['inspection' => $row])->render(),
            ];
        })->all();

        return compact('draw', 'recordsTotal', 'recordsFiltered', 'data');
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Models\MaintenanceOrder;
use App\Repositories\Interfaces\MaintenanceOrderRepositoryInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Eloquent maintenance order repository.
 */
class MaintenanceOrderRepository implements MaintenanceOrderRepositoryInterface
{
    public function __construct(protected MaintenanceOrder $model) {}

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): MaintenanceOrder
    {
        return $this->model->newQuery()
            ->with([
                'workCentre',
                'parts.item:id,item_code,item_name',
                'parts.warehouse:id,code,name',
                'reportedBy:id,name',
                'assignedTo:id,name',
            ])
            ->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): MaintenanceOrder
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): MaintenanceOrder
    {
        $order = $this->findById($id);
        $data['updated_by'] = Auth::id();
        $order->update($data);

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
        $columns = ['id', 'document_no', 'document_date', 'order_type', 'status', 'id'];
        $orderBy = $columns[$orderCol] ?? 'id';

        $base = $this->model->newQuery()->with(['workCentre:id,code,name']);
        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function ($q) use ($search): void {
                $q->where('document_no', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('workCentre', fn ($wq) => $wq->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($params['status'])) {
            $base->where('status', $params['status']);
        }

        if (! empty($params['order_type'])) {
            $base->where('order_type', $params['order_type']);
        }

        $recordsFiltered = (clone $base)->count();
        $rows = $base->orderBy($orderBy, $orderDir)->skip($start)->take($length)->get();

        $data = $rows->map(function (MaintenanceOrder $row): array {
            return [
                'id' => $row->id,
                'document_no' => e($row->document_no),
                'document_date' => $row->document_date?->format('d M Y'),
                'asset' => e(($row->workCentre?->code ?? '').' — '.($row->workCentre?->name ?? '')),
                'order_type' => e($row->order_type->label()),
                'status' => e($row->status->label()),
                'downtime' => number_format((int) $row->downtime_minutes).' min',
                'action' => view('admin.maintenance-orders.partials.actions', ['order' => $row])->render(),
            ];
        })->all();

        return compact('draw', 'recordsTotal', 'recordsFiltered', 'data');
    }
}

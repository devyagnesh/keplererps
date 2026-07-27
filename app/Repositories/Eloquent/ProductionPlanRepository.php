<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductionPlan;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\ProductionPlanRepositoryInterface;

/**
 * Eloquent production plan repository.
 */
class ProductionPlanRepository implements ProductionPlanRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): ProductionPlan
    {
        return ProductionPlan::query()
            ->with([
                'sourceWarehouse:id,code,name',
                'targetWarehouse:id,code,name',
                'items.item:id,item_code,item_name',
                'items.bom:id,bom_number,version',
                'items.salesOrder:id,document_no',
                'items.workOrder:id,document_no,status',
                'shortages.item:id,item_code,item_name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): ProductionPlan
    {
        return ProductionPlan::query()->create($data);
    }

    public function update(int $id, array $data): ProductionPlan
    {
        $record = ProductionPlan::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) ProductionPlan::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = ProductionPlan::query()->withCount('items');

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'plan_from_date', 'plan_to_date', 'status', 'created_at'],
            ['document_no', 'remarks'],
            function (ProductionPlan $plan): array {
                return [
                    'id' => $plan->id,
                    'document_no' => $plan->document_no,
                    'document_date' => $plan->document_date?->format('Y-m-d'),
                    'horizon' => $plan->plan_from_date?->format('Y-m-d').' → '.$plan->plan_to_date?->format('Y-m-d'),
                    'lines' => $plan->items_count,
                    'status' => $plan->status->label(),
                    'action' => view('admin.production-plans.partials.actions', ['plan' => $plan])->render(),
                ];
            },
            $params
        );
    }
}

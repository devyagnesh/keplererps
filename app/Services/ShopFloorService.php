<?php

namespace App\Services;

use App\Enums\MaintenanceOrderStatus;
use App\Enums\WorkOrderStatus;
use App\Models\MaintenanceOrder;
use App\Models\Shift;
use App\Models\WorkCentre;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Shop-floor operator board, capacity planning and work-order costing (SRS depth).
 */
class ShopFloorService
{
    /**
     * Open work orders for a work centre (operator machine board).
     *
     * @return Collection<int, WorkOrder>
     */
    public function operatorBoard(?int $workCentreId = null): Collection
    {
        return WorkOrder::query()
            ->with([
                'item:id,item_code,item_name',
                'workCentre:id,code,name',
            ])
            ->when($workCentreId !== null, fn ($query) => $query->where('work_centre_id', $workCentreId))
            ->whereIn('status', [
                WorkOrderStatus::Released->value,
                WorkOrderStatus::InProgress->value,
            ])
            ->orderBy('priority')
            ->orderBy('planned_start_date')
            ->get();
    }

    /**
     * Planned versus available hours per work centre for the next N days.
     *
     * @return list<array<string, mixed>>
     */
    public function capacityChart(int $days = 7): array
    {
        $days = max(1, $days);
        $from = now()->startOfDay();
        $to = now()->addDays($days - 1)->endOfDay();
        $shiftHours = $this->defaultShiftHours();

        $rows = [];

        foreach (WorkCentre::query()->where('is_active', true)->orderBy('code')->get() as $centre) {
            $plannedHours = 0.0;

            $workOrders = WorkOrder::query()
                ->where('work_centre_id', $centre->id)
                ->whereIn('status', [
                    WorkOrderStatus::Released->value,
                    WorkOrderStatus::InProgress->value,
                ])
                ->where(function ($query) use ($from, $to): void {
                    $query->whereBetween('planned_start_date', [$from->toDateString(), $to->toDateString()])
                        ->orWhereBetween('planned_end_date', [$from->toDateString(), $to->toDateString()]);
                })
                ->with('operations')
                ->get();

            foreach ($workOrders as $workOrder) {
                $plannedHours += $this->plannedHoursForWorkOrder($workOrder);
            }

            $maintenanceMinutes = (int) MaintenanceOrder::query()
                ->where('work_centre_id', $centre->id)
                ->whereIn('status', [
                    MaintenanceOrderStatus::Open->value,
                    MaintenanceOrderStatus::InProgress->value,
                    MaintenanceOrderStatus::Closed->value,
                ])
                ->whereDate('document_date', '>=', $from->toDateString())
                ->whereDate('document_date', '<=', $to->toDateString())
                ->sum('downtime_minutes');

            $availableHours = max(0, ($shiftHours * $days) - ($maintenanceMinutes / 60));

            $rows[] = [
                'work_centre_id' => $centre->id,
                'work_centre_code' => $centre->code,
                'work_centre_name' => $centre->name,
                'planned_hours' => round($plannedHours, 2),
                'available_hours' => round($availableHours, 2),
                'maintenance_hours' => round($maintenanceMinutes / 60, 2),
                'utilization_percent' => $availableHours > 0
                    ? round(($plannedHours / $availableHours) * 100, 1)
                    : 0.0,
            ];
        }

        return $rows;
    }

    /**
     * Standard versus actual cost breakdown for one work order.
     *
     * @return array<string, mixed>
     */
    public function costingSheet(int $workOrderId): array
    {
        $workOrder = WorkOrder::query()
            ->with(['item:id,item_code,item_name', 'workCentre:id,code,name'])
            ->findOrFail($workOrderId);

        $plannedQty = (float) $workOrder->planned_quantity;
        $standardUnit = (float) $workOrder->standard_unit_cost;
        $standardTotal = round($standardUnit * $plannedQty, 2);

        $actualTotal = (float) $workOrder->actual_total_cost;

        return [
            'work_order' => $workOrder,
            'standard' => [
                'unit_cost' => $standardUnit,
                'total_cost' => $standardTotal,
            ],
            'actual' => [
                'material_cost' => (float) $workOrder->actual_material_cost,
                'machine_cost' => (float) $workOrder->actual_machine_cost,
                'labour_cost' => (float) $workOrder->actual_labour_cost,
                'overhead_cost' => (float) $workOrder->actual_overhead_cost,
                'total_cost' => $actualTotal,
                'unit_cost' => (float) $workOrder->actual_unit_cost,
            ],
            'variance' => round((float) $workOrder->cost_variance, 2),
            'variance_percent' => $standardTotal > 0
                ? round((($actualTotal - $standardTotal) / $standardTotal) * 100, 2)
                : 0.0,
        ];
    }

    /**
     * Planned hours from routing operations, falling back to planned date span.
     */
    protected function plannedHoursForWorkOrder(WorkOrder $workOrder): float
    {
        $qty = (float) $workOrder->planned_quantity;

        if ($workOrder->operations->isNotEmpty()) {
            $minutes = 0.0;

            foreach ($workOrder->operations as $operation) {
                $minutes += (float) $operation->setup_time_minutes;
                $minutes += (float) $operation->run_time_per_unit_minutes * $qty;
            }

            return round($minutes / 60, 2);
        }

        if ($workOrder->planned_start_date !== null && $workOrder->planned_end_date !== null) {
            $days = max(1, Carbon::parse($workOrder->planned_start_date)
                ->diffInDays(Carbon::parse($workOrder->planned_end_date)) + 1);

            return round($this->defaultShiftHours() * $days, 2);
        }

        return 0.0;
    }

    /**
     * Average active shift duration, defaulting to 8 hours.
     */
    protected function defaultShiftHours(): float
    {
        $shift = Shift::query()->where('is_active', true)->first();

        return $shift !== null ? $shift->durationHours() : 8.0;
    }
}

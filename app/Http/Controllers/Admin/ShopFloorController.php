<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkCentre;
use App\Models\WorkOrder;
use App\Services\ShopFloorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shop-floor operator board, capacity chart and costing views.
 */
class ShopFloorController extends Controller
{
    public function __construct(protected ShopFloorService $service) {}

    /**
     * Operator board — open work orders on a work centre.
     */
    public function operator(Request $request): View
    {
        $workCentreId = $request->integer('work_centre_id') ?: null;

        return view('admin.shop-floor.operator', [
            'workCentres' => WorkCentre::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'selectedWorkCentreId' => $workCentreId,
            'workOrders' => $this->service->operatorBoard($workCentreId),
        ]);
    }

    /**
     * Capacity chart — planned vs available hours per work centre.
     */
    public function capacity(Request $request): View
    {
        $days = max(1, min(30, $request->integer('days') ?: 7));

        return view('admin.shop-floor.capacity', [
            'days' => $days,
            'rows' => $this->service->capacityChart($days),
        ]);
    }

    /**
     * Work-order costing sheet — standard vs actual breakdown.
     */
    public function costing(WorkOrder $workOrder): View
    {
        return view('admin.shop-floor.costing', [
            'sheet' => $this->service->costingSheet($workOrder->id),
        ]);
    }
}

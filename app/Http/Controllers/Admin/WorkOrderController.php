<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkOrderRequest;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Work order screens (M09).
 */
class WorkOrderController extends Controller
{
    public function __construct(protected WorkOrderService $service) {}

    public function index(): View
    {
        return view('admin.work-orders.index', [
            'statuses' => WorkOrderStatus::cases(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.work-orders.create', $this->lookups());
    }

    public function store(WorkOrderRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Work order saved as draft.',
                'data' => $record,
                'redirect' => route('admin.work-orders.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(WorkOrder $workOrder): View
    {
        $wo = $this->service->find($workOrder->id);

        return view('admin.work-orders.edit', array_merge($this->lookups(), [
            'workOrder' => $wo,
            'availability' => $wo->status->canRelease() || $wo->status === WorkOrderStatus::Released
                ? $this->service->materialAvailability($wo->id)
                : [],
        ]));
    }

    public function update(WorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        try {
            $record = $this->service->update($workOrder->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Work order updated.',
                'data' => $record,
                'redirect' => route('admin.work-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        try {
            $this->service->delete($workOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Work order deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function release(Request $request, WorkOrder $workOrder): JsonResponse
    {
        try {
            $record = $this->service->release($workOrder->id, [
                'confirm_non_critical' => $request->boolean('confirm_non_critical'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Work order released. Materials reserved.',
                'data' => $record,
                'redirect' => route('admin.work-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function availability(WorkOrder $workOrder): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Material availability loaded.',
            'data' => $this->service->materialAvailability($workOrder->id),
        ]);
    }

    public function issueMaterials(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.work_order_component_id' => ['required', 'integer', 'exists:work_order_components,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $record = $this->service->issueMaterials($workOrder->id, $data['items']);

            return response()->json([
                'status' => true,
                'message' => 'Materials issued to work order.',
                'data' => $record,
                'redirect' => route('admin.work-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function close(WorkOrder $workOrder): JsonResponse
    {
        try {
            $record = $this->service->close($workOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Work order closed. Cost variance calculated.',
                'data' => $record,
                'redirect' => route('admin.work-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function bomsForItem(Item $item): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'BOMs loaded.',
            'data' => $this->service->activeBomsForItem($item->id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'items' => Item::query()
                ->where('is_manufacturable', true)
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name', 'stock_uom_id']),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'workCentres' => WorkCentre::query()->orderBy('name')->get(['id', 'code', 'name']),
            'priorities' => WorkOrderPriority::cases(),
            'salesOrders' => SalesOrder::query()
                ->whereIn('status', ['confirmed', 'partially_delivered', 'delivered'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'document_no', 'customer_id']),
        ];
    }

    protected function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => collect($e->errors())->flatten()->first(),
            'errors' => $e->errors(),
        ], 422);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MaintenanceOrderStatus;
use App\Enums\MaintenanceOrderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceOrderRequest;
use App\Models\Item;
use App\Models\MaintenanceOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use App\Services\MaintenanceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Maintenance order screens (M11).
 */
class MaintenanceOrderController extends Controller
{
    public function __construct(protected MaintenanceOrderService $service) {}

    public function index(): View
    {
        return view('admin.maintenance-orders.index', [
            'statuses' => MaintenanceOrderStatus::cases(),
            'orderTypes' => MaintenanceOrderType::cases(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.maintenance-orders.create', $this->lookups());
    }

    public function store(MaintenanceOrderRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Maintenance order opened. Asset marked stopped.',
                'data' => $record,
                'redirect' => route('admin.maintenance-orders.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(MaintenanceOrder $maintenanceOrder): View
    {
        return view('admin.maintenance-orders.edit', array_merge($this->lookups(), [
            'order' => $this->service->find($maintenanceOrder->id),
        ]));
    }

    public function update(MaintenanceOrderRequest $request, MaintenanceOrder $maintenanceOrder): JsonResponse
    {
        try {
            $record = $this->service->update($maintenanceOrder->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Maintenance order updated.',
                'data' => $record,
                'redirect' => route('admin.maintenance-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function issueParts(MaintenanceOrder $maintenanceOrder): JsonResponse
    {
        try {
            $record = $this->service->issueParts($maintenanceOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Spare parts issued from stock.',
                'data' => $record,
                'redirect' => route('admin.maintenance-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function close(Request $request, MaintenanceOrder $maintenanceOrder): JsonResponse
    {
        $data = $request->validate([
            'action_taken' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'downtime_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $record = $this->service->close($maintenanceOrder->id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Maintenance closed. Asset returned to Active.',
                'data' => $record,
                'redirect' => route('admin.maintenance-orders.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(MaintenanceOrder $maintenanceOrder): JsonResponse
    {
        try {
            $this->service->delete($maintenanceOrder->id);

            return response()->json([
                'status' => true,
                'message' => 'Maintenance order cancelled.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'assets' => WorkCentre::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'status', 'asset_type']),
            'items' => Item::query()
                ->where('is_active', true)
                ->orderBy('item_code')
                ->limit(500)
                ->get(['id', 'item_code', 'item_name']),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'orderTypes' => MaintenanceOrderType::cases(),
            'statuses' => MaintenanceOrderStatus::cases(),
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

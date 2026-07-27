<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RejectionDisposition;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductionEntryRequest;
use App\Models\DefectReason;
use App\Models\Item;
use App\Models\ProductionEntry;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ProductionEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Production entry screens (M09).
 */
class ProductionEntryController extends Controller
{
    public function __construct(protected ProductionEntryService $service) {}

    public function index(): View
    {
        return view('admin.production-entries.index', [
            'workOrders' => WorkOrder::query()
                ->whereIn('status', [
                    WorkOrderStatus::Released->value,
                    WorkOrderStatus::InProgress->value,
                    WorkOrderStatus::Completed->value,
                ])
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'document_no']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $workOrderId = $request->integer('work_order_id') ?: null;

        return view('admin.production-entries.create', array_merge($this->lookups(), [
            'selectedWorkOrderId' => $workOrderId,
            'workOrder' => $workOrderId ? WorkOrder::query()->with('item')->find($workOrderId) : null,
        ]));
    }

    public function store(ProductionEntryRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $postImmediately = (bool) ($data['post_immediately'] ?? true);
            unset($data['post_immediately']);

            $record = $postImmediately
                ? $this->service->createAndPost($data)
                : $this->service->create($data);

            return response()->json([
                'status' => true,
                'message' => $postImmediately ? 'Production entry posted.' : 'Production entry saved as draft.',
                'data' => $record,
                'redirect' => route('admin.production-entries.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(ProductionEntry $productionEntry): View
    {
        return view('admin.production-entries.edit', array_merge($this->lookups(), [
            'productionEntry' => $this->service->find($productionEntry->id),
        ]));
    }

    public function destroy(ProductionEntry $productionEntry): JsonResponse
    {
        try {
            $this->service->delete($productionEntry->id);

            return response()->json([
                'status' => true,
                'message' => 'Production entry deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function post(ProductionEntry $productionEntry): JsonResponse
    {
        try {
            $record = $this->service->post($productionEntry->id);

            return response()->json([
                'status' => true,
                'message' => 'Production entry posted to stock.',
                'data' => $record,
                'redirect' => route('admin.production-entries.edit', $record),
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
            'workOrders' => WorkOrder::query()
                ->with('item:id,item_code,item_name')
                ->whereIn('status', [
                    WorkOrderStatus::Released->value,
                    WorkOrderStatus::InProgress->value,
                ])
                ->orderByDesc('id')
                ->get(['id', 'document_no', 'item_id', 'planned_quantity', 'good_quantity', 'status']),
            'defectReasons' => DefectReason::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
            'dispositions' => RejectionDisposition::cases(),
            'operators' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'items' => Item::query()->where('is_active', true)->orderBy('item_code')->limit(200)->get(['id', 'item_code', 'item_name']),
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

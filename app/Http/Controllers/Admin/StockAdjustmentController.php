<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdjustmentDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stock adjustment documents (M08).
 */
class StockAdjustmentController extends Controller
{
    public function __construct(protected StockAdjustmentService $service) {}

    public function index(): View
    {
        return view('admin.stock-adjustments.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.stock-adjustments.create', $this->lookups());
    }

    public function store(StockAdjustmentRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Stock adjustment saved as draft.',
            'data' => $record,
            'redirect' => route('admin.stock-adjustments.edit', $record),
        ], 201);
    }

    public function edit(StockAdjustment $stockAdjustment): View
    {
        return view('admin.stock-adjustments.edit', array_merge($this->lookups(), [
            'stockAdjustment' => $this->service->find($stockAdjustment->id),
        ]));
    }

    public function update(StockAdjustmentRequest $request, StockAdjustment $stockAdjustment): JsonResponse
    {
        try {
            $record = $this->service->update($stockAdjustment->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Stock adjustment updated.',
                'data' => $record,
                'redirect' => route('admin.stock-adjustments.edit', $record),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(StockAdjustment $stockAdjustment): JsonResponse
    {
        try {
            $this->service->delete($stockAdjustment->id);

            return response()->json(['status' => true, 'message' => 'Stock adjustment deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function post(StockAdjustment $stockAdjustment): JsonResponse
    {
        try {
            $record = $this->service->post($stockAdjustment->id);

            return response()->json([
                'status' => true,
                'message' => 'Stock adjustment posted.',
                'data' => $record,
                'redirect' => route('admin.stock-adjustments.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'directions' => AdjustmentDirection::cases(),
            'warehouses' => Warehouse::query()->where('is_active', true)->where('is_leaf', true)->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::query()->where('is_active', true)->where('item_type', '!=', 'service')->orderBy('item_name')->limit(500)->get(['id', 'item_code', 'item_name', 'tracking_type', 'standard_cost']),
        ];
    }
}

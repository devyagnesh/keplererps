<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WarehouseLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Warehouse hierarchy CRUD (M01).
 */
class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $service
    ) {}

    /**
     * List warehouses.
     */
    public function index(): View
    {
        return view('admin.warehouses.index', [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'levels' => WarehouseLevel::cases(),
        ]);
    }

    /**
     * DataTables JSON.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        return view('admin.warehouses.create', [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'parents' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id', 'level', 'depth']),
            'levels' => WarehouseLevel::cases(),
        ]);
    }

    /**
     * Store a warehouse.
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        try {
            $warehouse = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Warehouse created successfully.',
                'data' => $warehouse,
                'redirect' => route('admin.warehouses.index'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create warehouse.',
            ], 500);
        }
    }

    /**
     * Edit form.
     */
    public function edit(Warehouse $warehouse): View
    {
        return view('admin.warehouses.edit', [
            'warehouse' => $warehouse->load(['branch', 'parent']),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'parents' => Warehouse::query()
                ->where('is_active', true)
                ->where('id', '!=', $warehouse->id)
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id', 'level', 'depth']),
            'levels' => WarehouseLevel::cases(),
        ]);
    }

    /**
     * Update a warehouse.
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            $updated = $this->service->update($warehouse->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Warehouse updated successfully.',
                'data' => $updated,
                'redirect' => route('admin.warehouses.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update warehouse.',
            ], 500);
        }
    }

    /**
     * Soft-delete a warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        try {
            $this->service->delete($warehouse->id);

            return response()->json([
                'status' => true,
                'message' => 'Warehouse deleted successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Unable to delete warehouse.',
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete warehouse.',
            ], 500);
        }
    }
}

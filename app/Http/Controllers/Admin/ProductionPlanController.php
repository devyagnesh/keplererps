<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductionPlanRequest;
use App\Models\ProductionPlan;
use App\Services\ProductionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Production plan screens: pull open demand, then generate draft work orders.
 */
class ProductionPlanController extends Controller
{
    public function __construct(protected ProductionPlanService $service) {}

    public function index(): View
    {
        return view('admin.production-plans.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.production-plans.create', [
            'warehouses' => $this->service->selectableWarehouses(),
        ]);
    }

    public function store(ProductionPlanRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Production plan saved as draft.',
                'data' => $record,
                'redirect' => route('admin.production-plans.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(ProductionPlan $productionPlan): View
    {
        return view('admin.production-plans.edit', [
            'productionPlan' => $this->service->find($productionPlan->id),
            'warehouses' => $this->service->selectableWarehouses(),
        ]);
    }

    public function update(ProductionPlanRequest $request, ProductionPlan $productionPlan): JsonResponse
    {
        try {
            $record = $this->service->update($productionPlan->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Production plan updated.',
                'data' => $record,
                'redirect' => route('admin.production-plans.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(ProductionPlan $productionPlan): JsonResponse
    {
        try {
            $this->service->delete($productionPlan->id);

            return response()->json([
                'status' => true,
                'message' => 'Production plan deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Open sales demand available for planning.
     */
    public function demand(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Open demand loaded.',
            'data' => $this->service->demandLines(
                $request->filled('plan_from_date') ? $request->date('plan_from_date')?->toDateString() : null,
                $request->filled('plan_to_date') ? $request->date('plan_to_date')?->toDateString() : null,
            ),
        ]);
    }

    public function generate(ProductionPlan $productionPlan): JsonResponse
    {
        try {
            $record = $this->service->generateWorkOrders($productionPlan->id);

            return response()->json([
                'status' => true,
                'message' => 'Draft work orders generated.',
                'data' => $record,
                'redirect' => route('admin.production-plans.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(ProductionPlan $productionPlan): JsonResponse
    {
        try {
            $record = $this->service->cancel($productionPlan->id);

            return response()->json([
                'status' => true,
                'message' => 'Production plan cancelled.',
                'data' => $record,
                'redirect' => route('admin.production-plans.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
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

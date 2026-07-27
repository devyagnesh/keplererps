<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxRateRequest;
use App\Models\TaxRate;
use App\Services\TaxRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tax rate master CRUD.
 */
class TaxRateController extends Controller
{
    public function __construct(protected TaxRateService $service) {}

    public function index(): View
    {
        return view('admin.tax-rates.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.tax-rates.create');
    }

    public function store(TaxRateRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tax rate created successfully.',
            'data' => $record,
            'redirect' => route('admin.tax-rates.index'),
        ], 201);
    }

    public function edit(TaxRate $taxRate): View
    {
        return view('admin.tax-rates.edit', ['taxRate' => $taxRate]);
    }

    public function update(TaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $record = $this->service->update($taxRate->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tax rate updated successfully.',
            'data' => $record,
            'redirect' => route('admin.tax-rates.index'),
        ]);
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        try {
            $this->service->delete($taxRate->id);

            return response()->json(['status' => true, 'message' => 'Tax rate deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinancialYearRequest;
use App\Models\FinancialYear;
use App\Services\FinancialYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Financial year CRUD and close/set-current actions.
 */
class FinancialYearController extends Controller
{
    public function __construct(protected FinancialYearService $service) {}

    public function index(): View
    {
        return view('admin.financial-years.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.financial-years.create');
    }

    public function store(FinancialYearRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Financial year created.',
                'data' => $record,
                'redirect' => route('admin.financial-years.index'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function edit(FinancialYear $financialYear): View
    {
        return view('admin.financial-years.edit', ['financialYear' => $financialYear]);
    }

    public function update(FinancialYearRequest $request, FinancialYear $financialYear): JsonResponse
    {
        try {
            $record = $this->service->update($financialYear->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Financial year updated.',
                'data' => $record,
                'redirect' => route('admin.financial-years.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(FinancialYear $financialYear): JsonResponse
    {
        try {
            $this->service->delete($financialYear->id);

            return response()->json(['status' => true, 'message' => 'Financial year deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function setCurrent(FinancialYear $financialYear): JsonResponse
    {
        try {
            $this->service->setCurrent($financialYear->id);

            return response()->json(['status' => true, 'message' => 'Current financial year updated.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function close(FinancialYear $financialYear): JsonResponse
    {
        try {
            $this->service->close($financialYear->id);

            return response()->json(['status' => true, 'message' => 'Financial year closed. Costing method is now locked.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}

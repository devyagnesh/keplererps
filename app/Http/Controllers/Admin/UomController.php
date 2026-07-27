<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UomRequest;
use App\Models\Uom;
use App\Services\UomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UOM master CRUD.
 */
class UomController extends Controller
{
    public function __construct(protected UomService $service) {}

    public function index(): View
    {
        return view('admin.uoms.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.uoms.create');
    }

    public function store(UomRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'UOM created successfully.',
            'data' => $record,
            'redirect' => route('admin.uoms.index'),
        ], 201);
    }

    public function edit(Uom $uom): View
    {
        return view('admin.uoms.edit', ['uom' => $uom]);
    }

    public function update(UomRequest $request, Uom $uom): JsonResponse
    {
        $record = $this->service->update($uom->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'UOM updated successfully.',
            'data' => $record,
            'redirect' => route('admin.uoms.index'),
        ]);
    }

    public function destroy(Uom $uom): JsonResponse
    {
        try {
            $this->service->delete($uom->id);

            return response()->json(['status' => true, 'message' => 'UOM deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}

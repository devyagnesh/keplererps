<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HsnCodeRequest;
use App\Models\HsnCode;
use App\Services\HsnCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HSN / SAC master CRUD.
 */
class HsnCodeController extends Controller
{
    public function __construct(protected HsnCodeService $service) {}

    public function index(): View
    {
        return view('admin.hsn-codes.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.hsn-codes.create');
    }

    public function store(HsnCodeRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'HSN/SAC code created successfully.',
            'data' => $record,
            'redirect' => route('admin.hsn-codes.index'),
        ], 201);
    }

    public function edit(HsnCode $hsnCode): View
    {
        return view('admin.hsn-codes.edit', ['hsnCode' => $hsnCode]);
    }

    public function update(HsnCodeRequest $request, HsnCode $hsnCode): JsonResponse
    {
        $record = $this->service->update($hsnCode->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'HSN/SAC code updated successfully.',
            'data' => $record,
            'redirect' => route('admin.hsn-codes.index'),
        ]);
    }

    public function destroy(HsnCode $hsnCode): JsonResponse
    {
        try {
            $this->service->delete($hsnCode->id);

            return response()->json(['status' => true, 'message' => 'HSN/SAC code deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}

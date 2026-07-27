<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransporterRequest;
use App\Models\Transporter;
use App\Services\TransporterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Transporter master CRUD.
 */
class TransporterController extends Controller
{
    public function __construct(protected TransporterService $service) {}

    public function index(): View
    {
        return view('admin.transporters.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.transporters.create');
    }

    public function store(TransporterRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Transporter created successfully.',
            'data' => $record,
            'redirect' => route('admin.transporters.index'),
        ], 201);
    }

    public function edit(Transporter $transporter): View
    {
        return view('admin.transporters.edit', ['transporter' => $transporter]);
    }

    public function update(TransporterRequest $request, Transporter $transporter): JsonResponse
    {
        $record = $this->service->update($transporter->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Transporter updated successfully.',
            'data' => $record,
            'redirect' => route('admin.transporters.index'),
        ]);
    }

    public function destroy(Transporter $transporter): JsonResponse
    {
        try {
            $this->service->delete($transporter->id);

            return response()->json(['status' => true, 'message' => 'Transporter deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}

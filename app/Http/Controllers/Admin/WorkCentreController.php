<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkCentreRequest;
use App\Models\WorkCentre;
use App\Services\WorkCentreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Asset / work centre register screens (M11).
 */
class WorkCentreController extends Controller
{
    public function __construct(protected WorkCentreService $service) {}

    public function index(): View
    {
        return view('admin.work-centres.index', [
            'statuses' => AssetStatus::cases(),
            'assetTypes' => AssetType::cases(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.work-centres.create', $this->lookups());
    }

    public function store(WorkCentreRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Asset saved.',
                'data' => $record,
                'redirect' => route('admin.work-centres.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(WorkCentre $workCentre): View
    {
        return view('admin.work-centres.edit', array_merge($this->lookups(), [
            'asset' => $this->service->find($workCentre->id),
        ]));
    }

    public function update(WorkCentreRequest $request, WorkCentre $workCentre): JsonResponse
    {
        try {
            $record = $this->service->update($workCentre->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Asset updated.',
                'data' => $record,
                'redirect' => route('admin.work-centres.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(WorkCentre $workCentre): JsonResponse
    {
        try {
            $this->service->delete($workCentre->id);

            return response()->json([
                'status' => true,
                'message' => 'Asset deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function due(): View
    {
        return view('admin.work-centres.due', [
            'assets' => $this->service->dueForMaintenance(90.0),
        ]);
    }

    public function statusBoard(): View
    {
        return view('admin.work-centres.status-board', [
            'assets' => $this->service->statusBoard(),
            'statuses' => AssetStatus::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'assetTypes' => AssetType::cases(),
            'statuses' => AssetStatus::cases(),
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

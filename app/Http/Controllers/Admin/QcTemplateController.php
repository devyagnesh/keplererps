<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InspectionType;
use App\Enums\QcParameterType;
use App\Enums\SamplingPlanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QcTemplateRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\QcTemplate;
use App\Services\QcTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * QC template screens (M10).
 */
class QcTemplateController extends Controller
{
    public function __construct(protected QcTemplateService $service) {}

    public function index(): View
    {
        return view('admin.qc-templates.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.qc-templates.create', $this->lookups());
    }

    public function store(QcTemplateRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'QC template created.',
                'data' => $record,
                'redirect' => route('admin.qc-templates.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(QcTemplate $qcTemplate): View
    {
        return view('admin.qc-templates.edit', array_merge($this->lookups(), [
            'template' => $this->service->find($qcTemplate->id),
        ]));
    }

    public function update(QcTemplateRequest $request, QcTemplate $qcTemplate): JsonResponse
    {
        try {
            $record = $this->service->update($qcTemplate->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'QC template updated.',
                'data' => $record,
                'redirect' => route('admin.qc-templates.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(QcTemplate $qcTemplate): JsonResponse
    {
        try {
            $this->service->delete($qcTemplate->id);

            return response()->json([
                'status' => true,
                'message' => 'QC template deleted.',
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
            'items' => Item::query()
                ->where('is_active', true)
                ->orderBy('item_code')
                ->get(['id', 'item_code', 'item_name', 'category_id']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'inspectionTypes' => InspectionType::cases(),
            'samplingPlans' => SamplingPlanType::cases(),
            'parameterTypes' => QcParameterType::cases(),
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

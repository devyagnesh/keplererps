<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentSeriesType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentNumberSeriesRequest;
use App\Models\Branch;
use App\Models\DocumentNumberSeries;
use App\Models\FinancialYear;
use App\Services\DocumentNumberSeriesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Document numbering series configuration (C2).
 */
class DocumentNumberSeriesController extends Controller
{
    public function __construct(protected DocumentNumberSeriesService $service) {}

    public function index(): View
    {
        return view('admin.document-series.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.document-series.create', $this->lookups());
    }

    public function store(DocumentNumberSeriesRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Document series created.',
                'data' => $record,
                'redirect' => route('admin.document-series.index'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function edit(DocumentNumberSeries $document_number_series): View
    {
        return view('admin.document-series.edit', array_merge($this->lookups(), [
            'series' => $this->service->find($document_number_series->id),
        ]));
    }

    public function update(DocumentNumberSeriesRequest $request, DocumentNumberSeries $document_number_series): JsonResponse
    {
        try {
            $record = $this->service->update($document_number_series->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Document series updated.',
                'data' => $record,
                'redirect' => route('admin.document-series.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(DocumentNumberSeries $document_number_series): JsonResponse
    {
        try {
            $this->service->delete($document_number_series->id);

            return response()->json(['status' => true, 'message' => 'Document series deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function preview(DocumentNumberSeries $document_number_series): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Next number preview.',
            'data' => ['next' => $this->service->preview($document_number_series->id)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'documentTypes' => DocumentSeriesType::cases(),
            'financialYears' => FinancialYear::query()->orderByDesc('starts_on')->get(['id', 'code', 'name']),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ];
    }
}

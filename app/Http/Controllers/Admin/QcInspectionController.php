<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\QcDisposition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QcInspectionRequest;
use App\Http\Requests\Admin\QcInspectionStoreRequest;
use App\Models\Batch;
use App\Models\Item;
use App\Models\QcInspection;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\QcInspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * QC inspection screens (M10).
 */
class QcInspectionController extends Controller
{
    public function __construct(protected QcInspectionService $service) {}

    public function index(): View
    {
        return view('admin.qc-inspections.index', [
            'statuses' => InspectionStatus::cases(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.qc-inspections.create', $this->formOptions());
    }

    public function store(QcInspectionStoreRequest $request): JsonResponse
    {
        try {
            $record = $this->service->createManual($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Inspection raised. Record the readings to complete it.',
                'data' => $record,
                'redirect' => route('admin.qc-inspections.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(QcInspection $qcInspection): View
    {
        return view('admin.qc-inspections.edit', [
            'inspection' => $this->service->find($qcInspection->id),
            'dispositions' => QcDisposition::cases(),
        ]);
    }

    public function update(QcInspectionRequest $request, QcInspection $qcInspection): JsonResponse
    {
        try {
            $record = $this->service->update($qcInspection->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Inspection readings saved.',
                'data' => $record,
                'redirect' => route('admin.qc-inspections.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function complete(QcInspectionRequest $request, QcInspection $qcInspection): JsonResponse
    {
        try {
            $record = $this->service->complete($qcInspection->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Inspection completed. Stock disposition applied.',
                'data' => $record,
                'redirect' => route('admin.qc-inspections.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Printable Certificate of Analysis for a completed inspection.
     */
    public function coa(QcInspection $qcInspection): View
    {
        $inspection = $this->service->find($qcInspection->id);

        if ($inspection->status !== InspectionStatus::Completed) {
            abort(422, 'Certificate of Analysis is available only after the inspection is completed.');
        }

        return view('admin.qc-inspections.coa', ['inspection' => $inspection]);
    }

    public function destroy(QcInspection $qcInspection): JsonResponse
    {
        try {
            $this->service->delete($qcInspection->id);

            return response()->json([
                'status' => true,
                'message' => 'Inspection deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Lookups for the manual inspection form.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'types' => array_values(array_filter(
                InspectionType::cases(),
                fn (InspectionType $type): bool => $type !== InspectionType::Incoming
            )),
            'items' => Item::query()
                ->where('is_active', true)
                ->where('item_type', '!=', 'service')
                ->orderBy('item_name')
                ->limit(500)
                ->get(['id', 'item_code', 'item_name', 'tracking_type']),
            'batches' => Batch::query()
                ->where('is_active', true)
                ->orderBy('batch_no')
                ->limit(500)
                ->get(['id', 'item_id', 'batch_no', 'expiry_date']),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'workOrders' => WorkOrder::query()
                ->whereIn('status', ['released', 'in_progress'])
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'document_no']),
            'salesOrders' => SalesOrder::query()
                ->whereIn('status', ['confirmed', 'partially_delivered'])
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'document_no']),
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

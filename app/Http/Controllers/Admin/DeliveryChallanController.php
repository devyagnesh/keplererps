<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryChallanStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\TransportMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryChallanRequest;
use App\Models\DeliveryChallan;
use App\Models\SalesOrder;
use App\Models\Transporter;
use App\Services\DeliveryChallanService;
use App\Services\DocumentPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Delivery challan / dispatch screens (M12).
 */
class DeliveryChallanController extends Controller
{
    public function __construct(protected DeliveryChallanService $service) {}

    public function index(): View
    {
        return view('admin.delivery-challans.index', [
            'statuses' => DeliveryChallanStatus::cases(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(Request $request): View
    {
        $salesOrderId = $request->integer('sales_order_id') ?: null;

        return view('admin.delivery-challans.create', array_merge($this->lookups(), [
            'selectedSalesOrderId' => $salesOrderId,
            'pendingLines' => $salesOrderId ? $this->service->pendingLinesForOrder($salesOrderId) : [],
        ]));
    }

    public function store(DeliveryChallanRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Delivery challan saved as draft.',
                'data' => $record,
                'redirect' => route('admin.delivery-challans.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Print-friendly delivery challan.
     */
    public function print(DeliveryChallan $deliveryChallan, DocumentPrintService $print, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return $print->respond($print->deliveryChallan($deliveryChallan->id), 'delivery_challan', $request->string('format')->toString() === 'pdf');
    }

    public function edit(DeliveryChallan $deliveryChallan): View
    {
        $challan = $this->service->find($deliveryChallan->id);

        return view('admin.delivery-challans.edit', array_merge($this->lookups(), [
            'deliveryChallan' => $challan,
            'selectedSalesOrderId' => $challan->sales_order_id,
            'pendingLines' => [],
        ]));
    }

    public function update(DeliveryChallanRequest $request, DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            $record = $this->service->update($deliveryChallan->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Delivery challan updated.',
                'data' => $record,
                'redirect' => route('admin.delivery-challans.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            $this->service->delete($deliveryChallan->id);

            return response()->json([
                'status' => true,
                'message' => 'Delivery challan deleted.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function dispatch(DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            $record = $this->service->dispatch($deliveryChallan->id);

            return response()->json([
                'status' => true,
                'message' => 'Challan dispatched. Stock issued from warehouse.',
                'data' => $record,
                'redirect' => route('admin.delivery-challans.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function markDelivered(Request $request, DeliveryChallan $deliveryChallan): JsonResponse
    {
        $request->validate([
            'pod' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $record = $this->service->markDelivered($deliveryChallan->id, [
                'pod' => $request->file('pod'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Proof of delivery saved. Challan marked delivered.',
                'data' => $record,
                'redirect' => route('admin.delivery-challans.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function pendingLines(SalesOrder $salesOrder): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Pending lines loaded.',
                'data' => $this->service->pendingLinesForOrder($salesOrder->id),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function ewayPayload(DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'E-way bill payload generated.',
                'data' => $this->service->ewayBillPayload($deliveryChallan->id),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Submit e-way bill to the GSP for a dispatched challan.
     */
    public function submitEway(DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            $result = $this->service->submitEwayBill($deliveryChallan->id);

            return response()->json([
                'status' => true,
                'message' => filled($result['eway_bill_number'])
                    ? 'E-way bill submitted successfully.'
                    : 'E-way bill submission queued.',
                'data' => $result,
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
            'salesOrders' => SalesOrder::query()
                ->with('customer:id,party_code,party_name')
                ->whereIn('status', [
                    SalesOrderStatus::Confirmed->value,
                    SalesOrderStatus::PartiallyDelivered->value,
                    SalesOrderStatus::Delivered->value,
                ])
                ->orderByDesc('id')
                ->get(['id', 'document_no', 'customer_id', 'status', 'document_date']),
            'transporters' => Transporter::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'gstin']),
            'transportModes' => TransportMode::cases(),
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

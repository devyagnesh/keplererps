<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\PurchaseIndent;
use App\Models\PurchaseRfq;
use App\Models\PurchaseRfqQuote;
use App\Services\PurchaseRfqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Purchase RFQ + comparative award screens (M07).
 */
class PurchaseRfqController extends Controller
{
    public function __construct(protected PurchaseRfqService $service) {}

    public function index(): View
    {
        return view('admin.purchase-rfqs.index', [
            'rfqs' => $this->service->all(),
        ]);
    }

    public function show(PurchaseRfq $purchaseRfq): View
    {
        $rfq = $this->service->find($purchaseRfq->id);

        return view('admin.purchase-rfqs.show', [
            'rfq' => $rfq,
            'comparative' => $this->service->comparative($rfq->id),
            'suppliers' => Party::query()
                ->whereIn('party_type', ['supplier', 'both'])
                ->where('status', 'active')
                ->orderBy('party_name')
                ->get(['id', 'party_code', 'party_name']),
        ]);
    }

    public function storeFromIndent(Request $request, PurchaseIndent $purchaseIndent): JsonResponse
    {
        $data = $request->validate([
            'document_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $rfq = $this->service->createFromIndent($purchaseIndent->id, $data);

            return response()->json([
                'status' => true,
                'message' => 'RFQ created from indent.',
                'data' => $rfq,
                'redirect' => route('admin.purchase-rfqs.show', $rfq),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function markSent(PurchaseRfq $purchaseRfq): JsonResponse
    {
        try {
            $rfq = $this->service->markSent($purchaseRfq->id);

            return response()->json(['status' => true, 'message' => 'RFQ marked as sent.', 'data' => $rfq]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function addQuote(Request $request, PurchaseRfq $purchaseRfq): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:parties,id'],
            'quote_date' => ['nullable', 'date'],
            'freight_amount' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rates' => ['required', 'array'],
            'rates.*' => ['numeric', 'min:0'],
        ]);

        try {
            $quote = $this->service->addQuote($purchaseRfq->id, $data);

            return response()->json(['status' => true, 'message' => 'Supplier quote saved.', 'data' => $quote], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function award(Request $request, PurchaseRfq $purchaseRfq, PurchaseRfqQuote $quote): JsonResponse
    {
        $data = $request->validate([
            'award_reason' => ['nullable', 'string', 'max:255'],
            'create_po' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->service->award($purchaseRfq->id, $quote->id, $data);

            return response()->json([
                'status' => true,
                'message' => $result['purchase_order_id']
                    ? 'Quote awarded and purchase order created.'
                    : 'Quote awarded.',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
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

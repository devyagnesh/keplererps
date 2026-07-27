<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalVoucher;
use App\Services\VoucherAllocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Receipt / payment allocation against open invoices and bills (M13).
 */
class VoucherAllocationController extends Controller
{
    public function __construct(protected VoucherAllocationService $service) {}

    public function edit(JournalVoucher $journalVoucher): View
    {
        return view('admin.voucher-allocations.edit', [
            'voucher' => $journalVoucher->load(['allocations', 'lines.ledgerAccount']),
            'allocations' => $this->service->forVoucher($journalVoucher->id),
        ]);
    }

    public function openDocuments(Request $request): JsonResponse
    {
        $partyId = $request->integer('party_id');
        $type = $request->string('type')->toString();

        $rows = $type === 'payable'
            ? $this->service->openBills($partyId)
            : $this->service->openInvoices($partyId);

        return response()->json([
            'status' => true,
            'message' => count($rows).' open document(s).',
            'data' => $rows,
        ]);
    }

    public function sync(Request $request, JournalVoucher $journalVoucher): JsonResponse
    {
        $validated = $request->validate([
            'allocations' => ['nullable', 'array'],
            'allocations.*.allocatable_type' => ['required', 'string'],
            'allocations.*.allocatable_id' => ['required', 'integer'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
            'allocations.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $voucher = $this->service->sync($journalVoucher->id, $validated['allocations'] ?? []);

            return response()->json([
                'status' => true,
                'message' => 'Allocations saved.',
                'data' => $voucher,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}

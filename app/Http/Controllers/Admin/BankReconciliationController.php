<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BankReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bank reconciliation UI (M13).
 */
class BankReconciliationController extends Controller
{
    public function __construct(protected BankReconciliationService $service) {}

    public function index(Request $request): View
    {
        $accounts = $this->service->bankAccounts();
        $accountId = (int) ($request->integer('ledger_account_id') ?: ($accounts->first()?->id ?? 0));
        $from = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? now()->endOfMonth()->toDateString();

        return view('admin.bank-reconciliation.index', [
            'accounts' => $accounts,
            'accountId' => $accountId,
            'fromDate' => $from,
            'toDate' => $to,
            'lines' => $accountId > 0 ? $this->service->lines($accountId, $from, $to) : collect(),
        ]);
    }

    public function reconcile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'line_ids' => ['required', 'array', 'min:1'],
            'line_ids.*' => ['integer'],
            'bank_date' => ['nullable', 'date'],
        ]);

        try {
            $count = $this->service->reconcile($data['line_ids'], $data['bank_date'] ?? null);

            return response()->json([
                'status' => true,
                'message' => "{$count} line(s) reconciled.",
                'data' => ['count' => $count],
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

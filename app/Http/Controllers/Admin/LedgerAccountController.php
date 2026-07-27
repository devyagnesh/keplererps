<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerAccountRequest;
use App\Models\LedgerAccount;
use App\Services\LedgerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Chart-of-accounts master CRUD (M13).
 */
class LedgerAccountController extends Controller
{
    public function __construct(protected LedgerAccountService $service) {}

    public function index(): View
    {
        return view('admin.ledger-accounts.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.ledger-accounts.create', ['accounts' => $this->service->selectable()]);
    }

    public function store(LedgerAccountRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Ledger account created successfully.',
                'data' => $record,
                'redirect' => route('admin.ledger-accounts.index'),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(LedgerAccount $ledgerAccount): View
    {
        return view('admin.ledger-accounts.edit', [
            'account' => $ledgerAccount,
            'accounts' => $this->service->selectable(),
        ]);
    }

    public function update(LedgerAccountRequest $request, LedgerAccount $ledgerAccount): JsonResponse
    {
        try {
            $record = $this->service->update($ledgerAccount->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Ledger account updated successfully.',
                'data' => $record,
                'redirect' => route('admin.ledger-accounts.index'),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(LedgerAccount $ledgerAccount): JsonResponse
    {
        try {
            $this->service->delete($ledgerAccount->id);

            return response()->json(['status' => true, 'message' => 'Ledger account deleted successfully.']);
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

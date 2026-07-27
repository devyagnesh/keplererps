<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JournalVoucherRequest;
use App\Models\JournalVoucher;
use App\Models\Party;
use App\Services\JournalVoucherService;
use App\Services\LedgerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Journal voucher screens (M13).
 */
class JournalVoucherController extends Controller
{
    public function __construct(
        protected JournalVoucherService $service,
        protected LedgerAccountService $accounts
    ) {}

    public function index(): View
    {
        return view('admin.journal-vouchers.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.journal-vouchers.create', $this->lookups());
    }

    public function store(JournalVoucherRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Journal voucher saved as draft.',
                'data' => $record,
                'redirect' => route('admin.journal-vouchers.edit', $record),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(JournalVoucher $journalVoucher): View
    {
        return view('admin.journal-vouchers.edit', array_merge($this->lookups(), [
            'voucher' => $this->service->find($journalVoucher->id),
        ]));
    }

    public function update(JournalVoucherRequest $request, JournalVoucher $journalVoucher): JsonResponse
    {
        try {
            $record = $this->service->update($journalVoucher->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Journal voucher updated.',
                'data' => $record,
                'redirect' => route('admin.journal-vouchers.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(JournalVoucher $journalVoucher): JsonResponse
    {
        try {
            $this->service->delete($journalVoucher->id);

            return response()->json(['status' => true, 'message' => 'Journal voucher deleted.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function post(Request $request, JournalVoucher $journalVoucher): JsonResponse
    {
        try {
            $record = $this->service->post(
                $journalVoucher->id,
                $request->string('override_reason')->toString() ?: null
            );

            return response()->json([
                'status' => true,
                'message' => 'Journal voucher posted to the ledger.',
                'data' => $record,
                'redirect' => route('admin.journal-vouchers.edit', $record),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(JournalVoucher $journalVoucher): JsonResponse
    {
        try {
            $record = $this->service->cancel($journalVoucher->id);

            return response()->json([
                'status' => true,
                'message' => 'Journal voucher cancelled.',
                'data' => $record,
                'redirect' => route('admin.journal-vouchers.edit', $record),
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
            'accounts' => $this->accounts->selectable(),
            'parties' => Party::query()
                ->where('status', PartyStatus::Active)
                ->orderBy('party_name')
                ->get(['id', 'party_code', 'party_name']),
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

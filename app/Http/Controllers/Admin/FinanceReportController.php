<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinanceReportService;
use App\Services\GstReportService;
use App\Services\LedgerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Finance registers: AR/AP ageing, account statements and trial balance (M13).
 */
class FinanceReportController extends Controller
{
    public function __construct(
        protected FinanceReportService $reports,
        protected LedgerAccountService $accounts,
        protected GstReportService $gst
    ) {}

    public function ageing(Request $request): View
    {
        $type = $request->string('type')->toString() === 'payable' ? 'payable' : 'receivable';

        return view('admin.finance-reports.ageing', [
            'type' => $type,
            'asOnDate' => $request->date('as_on_date')?->toDateString() ?? now()->toDateString(),
        ]);
    }

    public function ageingData(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString() === 'payable' ? 'payable' : 'receivable';

        return response()->json([
            'status' => true,
            'message' => 'Ageing loaded.',
            'data' => $this->reports->ageing($type, $request->date('as_on_date')?->toDateString()),
        ]);
    }

    public function ageingExport(Request $request): StreamedResponse
    {
        $type = $request->string('type')->toString() === 'payable' ? 'payable' : 'receivable';
        $rows = $this->reports->ageing($type, $request->date('as_on_date')?->toDateString());

        return $this->gst->streamCsv($rows, $type.'-ageing-'.now()->format('Ymd').'.csv');
    }

    public function statement(Request $request): View
    {
        $accountId = $request->integer('ledger_account_id') ?: null;

        return view('admin.finance-reports.statement', [
            'accounts' => $this->accounts->selectable(),
            'selectedAccountId' => $accountId,
            'fromDate' => $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString(),
            'toDate' => $request->date('to_date')?->toDateString() ?? now()->toDateString(),
            'statement' => $accountId
                ? $this->reports->accountStatement(
                    $accountId,
                    $request->date('from_date')?->toDateString(),
                    $request->date('to_date')?->toDateString(),
                    $request->integer('party_id') ?: null
                )
                : null,
        ]);
    }

    public function trialBalance(Request $request): View
    {
        return view('admin.finance-reports.trial-balance', [
            'fromDate' => $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString(),
            'toDate' => $request->date('to_date')?->toDateString() ?? now()->toDateString(),
            'report' => $this->reports->trialBalance(
                $request->date('from_date')?->toDateString(),
                $request->date('to_date')?->toDateString()
            ),
        ]);
    }

    public function profitAndLoss(Request $request): View
    {
        $fromDate = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $toDate = $request->date('to_date')?->toDateString() ?? now()->toDateString();

        return view('admin.finance-reports.profit-and-loss', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'report' => $this->reports->profitAndLoss($fromDate, $toDate),
        ]);
    }

    public function balanceSheet(Request $request): View
    {
        $asOnDate = $request->date('as_on_date')?->toDateString() ?? now()->toDateString();

        return view('admin.finance-reports.balance-sheet', [
            'asOnDate' => $asOnDate,
            'report' => $this->reports->balanceSheet($asOnDate),
        ]);
    }
}

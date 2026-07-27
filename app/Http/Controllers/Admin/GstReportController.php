<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GspFilingLog;
use App\Services\GstGspService;
use App\Services\GstReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GSTR-1 / GSTR-3B reconciliation worksheets + GSP push stub (M13).
 */
class GstReportController extends Controller
{
    public function __construct(
        protected GstReportService $service,
        protected GstGspService $gsp
    ) {}

    public function index(Request $request): View
    {
        [$fromDate, $toDate] = $this->period($request);

        return view('admin.gst-reports.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'summary' => $this->service->summary($fromDate, $toDate),
            'outward' => $this->service->outwardSupplies($fromDate, $toDate),
            'inward' => $this->service->inwardSupplies($fromDate, $toDate),
            'gspLogs' => GspFilingLog::query()->latest('id')->limit(10)->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$fromDate, $toDate] = $this->period($request);
        $worksheet = $request->string('worksheet')->toString() === 'inward' ? 'inward' : 'outward';

        $rows = $worksheet === 'inward'
            ? $this->service->inwardSupplies($fromDate, $toDate)
            : $this->service->outwardSupplies($fromDate, $toDate);

        return $this->service->streamCsv(
            $rows,
            sprintf('gst-%s-%s-to-%s.csv', $worksheet, $fromDate, $toDate)
        );
    }

    public function pushGsp(Request $request): JsonResponse
    {
        [$fromDate, $toDate] = $this->period($request);
        $result = $this->gsp->pushOutward($fromDate, $toDate);

        return response()->json([
            'status' => true,
            'message' => 'GSP filing '.$result['status'].'.',
            'data' => $result['log'],
        ], $result['status'] === 'failed' ? 422 : 200);
    }

    /**
     * Reporting period, defaulting to the current month.
     *
     * @return array{0: string, 1: string}
     */
    protected function period(Request $request): array
    {
        $from = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? now()->endOfMonth()->toDateString();

        return [$from, $to];
    }
}

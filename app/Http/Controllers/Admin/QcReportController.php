<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DefectParetoReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Defect Pareto report (M10).
 */
class QcReportController extends Controller
{
    public function __construct(protected DefectParetoReportService $pareto) {}

    public function pareto(Request $request): View
    {
        $from = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? now()->toDateString();

        return view('admin.qc-reports.pareto', [
            'rows' => $this->pareto->report($from, $to),
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }
}

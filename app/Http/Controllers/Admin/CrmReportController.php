<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CrmFunnelReportService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRM funnel and follow-up reminder screens (M05).
 */
class CrmReportController extends Controller
{
    public function __construct(
        protected CrmFunnelReportService $funnel,
        protected LeadService $leads
    ) {}

    public function funnel(Request $request): View
    {
        $from = $request->date('from_date')?->toDateString();
        $to = $request->date('to_date')?->toDateString();

        return view('admin.crm-reports.funnel', [
            'summary' => $this->funnel->summary($from, $to),
            'fromDate' => $from ?? now()->startOfMonth()->toDateString(),
            'toDate' => $to ?? now()->toDateString(),
        ]);
    }

    public function overdueFollowUps(): View
    {
        return view('admin.crm-reports.overdue', [
            'rows' => $this->leads->overdueFollowUps(),
        ]);
    }

    public function duplicates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'exclude_id' => ['nullable', 'integer'],
        ]);

        $rows = $this->leads->findDuplicates($data, $data['exclude_id'] ?? null);

        return response()->json([
            'status' => true,
            'message' => count($rows).' possible duplicate(s).',
            'data' => $rows,
        ]);
    }
}

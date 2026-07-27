<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use App\Services\ScheduledReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Scheduled register report management.
 */
class ScheduledReportController extends Controller
{
    public function __construct(protected ScheduledReportService $service) {}

    public function index(): View
    {
        return view('admin.scheduled-reports.index', [
            'reports' => $this->service->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'register_key' => ['required', 'string', 'max:60'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'recipient_emails' => ['required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $report = $this->service->create($data);

        return response()->json([
            'status' => true,
            'message' => 'Scheduled report saved.',
            'data' => $report,
        ], 201);
    }

    public function destroy(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->service->delete($scheduledReport->id);

        return response()->json([
            'status' => true,
            'message' => 'Scheduled report deleted.',
        ]);
    }
}

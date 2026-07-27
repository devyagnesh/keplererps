<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only activity / audit log viewer (M16).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()
            ->with(['causer'])
            ->latest('id');

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name')->toString());
        }

        if ($request->filled('event')) {
            $query->where('event', 'like', '%'.$request->string('event')->toString().'%');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->date('from_date')->toDateString());
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->date('to_date')->toDateString());
        }

        return view('admin.activity-logs.index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'logNames' => ActivityLog::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
            'filters' => [
                'log_name' => $request->string('log_name')->toString(),
                'event' => $request->string('event')->toString(),
                'from_date' => $request->date('from_date')?->toDateString(),
                'to_date' => $request->date('to_date')?->toDateString(),
            ],
        ]);
    }
}

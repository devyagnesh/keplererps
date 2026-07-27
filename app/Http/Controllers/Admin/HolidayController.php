<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Holiday calendar and leave balances (M14).
 */
class HolidayController extends Controller
{
    public function __construct(protected HolidayService $service) {}

    public function index(Request $request): View
    {
        $year = (int) ($request->integer('year') ?: now()->year);

        return view('admin.holidays.index', [
            'year' => $year,
            'holidays' => $this->service->holidays($year),
            'leaveBalances' => $this->service->leaveBalances($year),
            'employees' => Employee::query()->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
        ]);
    }

    public function storeHoliday(Request $request): JsonResponse
    {
        $data = $request->validate([
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:120'],
            'is_optional' => ['sometimes', 'boolean'],
        ]);

        $holiday = $this->service->createHoliday($data);

        return response()->json(['status' => true, 'message' => 'Holiday saved.', 'data' => $holiday], 201);
    }

    public function storeLeaveBalance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'leave_type' => ['nullable', 'string', 'max:40'],
            'opening_days' => ['required', 'numeric', 'min:0'],
            'availed_days' => ['nullable', 'numeric', 'min:0'],
        ]);

        $balance = $this->service->upsertLeaveBalance($data);

        return response()->json(['status' => true, 'message' => 'Leave balance saved.', 'data' => $balance], 201);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Mobile geo attendance punch for employees linked to the authenticated user.
 */
class MobileAttendanceController extends Controller
{
    public function __construct(protected AttendanceService $service) {}

    /**
     * Record a mobile punch-in or punch-out for the logged-in operator.
     */
    public function punch(Request $request): JsonResponse
    {
        $employee = Employee::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        if ($employee === null) {
            return response()->json([
                'status' => false,
                'message' => 'No employee profile is linked to your user account.',
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'punch_out' => ['sometimes', 'boolean'],
        ]);

        try {
            $record = $this->service->mobilePunch($employee->id, $validated);

            return response()->json([
                'status' => true,
                'message' => ($validated['punch_out'] ?? false)
                    ? 'Punch out recorded successfully.'
                    : 'Punch in recorded successfully.',
                'data' => $record,
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

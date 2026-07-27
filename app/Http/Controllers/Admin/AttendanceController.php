<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceRequest;
use App\Http\Requests\Admin\BiometricAttendanceImportRequest;
use App\Services\AttendanceService;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Daily attendance sheet (M14).
 */
class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $service,
        protected ShiftService $shifts
    ) {}

    public function index(Request $request): View
    {
        $date = $request->date('attendance_date')?->toDateString() ?? now()->toDateString();

        return view('admin.attendance.index', [
            'sheet' => $this->service->sheet($date),
            'shifts' => $this->shifts->selectable(),
        ]);
    }

    public function store(AttendanceRequest $request): JsonResponse
    {
        try {
            $marked = $this->service->save($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Attendance saved for '.$marked.' employee(s).',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Import punch rows from a biometric device CSV export.
     */
    public function import(BiometricAttendanceImportRequest $request): JsonResponse
    {
        try {
            $path = $request->file('file')?->getRealPath();
            if ($path === false || $path === null) {
                throw ValidationException::withMessages(['file' => 'Could not read the uploaded file.']);
            }

            $result = $this->service->importBiometricCsv($path);

            return response()->json([
                'status' => true,
                'message' => "Imported {$result['imported']} punch(es); skipped {$result['skipped']}.",
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Download a blank biometric CSV template.
     */
    public function importTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['biometric_code', 'attendance_date', 'status', 'worked_hours', 'overtime_hours']);
            fputcsv($out, ['EMP001', now()->toDateString(), 'present', '8', '0']);
            fclose($out);
        }, 'biometric-attendance-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}

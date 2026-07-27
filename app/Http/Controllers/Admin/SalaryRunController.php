<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryRunRequest;
use App\Models\SalaryRun;
use App\Services\SalaryRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Monthly payroll run screens (M14).
 */
class SalaryRunController extends Controller
{
    public function __construct(protected SalaryRunService $service) {}

    public function index(): View
    {
        return view('admin.salary-runs.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.salary-runs.create');
    }

    public function store(SalaryRunRequest $request): JsonResponse
    {
        try {
            $run = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Salary run '.$run->document_no.' created with '.$run->employee_count.' slip(s).',
                'data' => ['id' => $run->id],
                'redirect' => route('admin.salary-runs.edit', $run->id),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(SalaryRun $salaryRun): View
    {
        return view('admin.salary-runs.edit', ['run' => $this->service->find($salaryRun->id)]);
    }

    /**
     * Rebuild the slips of a draft run from current attendance.
     */
    public function recalculate(SalaryRun $salaryRun): JsonResponse
    {
        try {
            $run = $this->service->recalculate($salaryRun->id);

            return response()->json([
                'status' => true,
                'message' => 'Salary slips recalculated.',
                'data' => ['net_total' => (float) $run->net_total],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function post(SalaryRun $salaryRun): JsonResponse
    {
        try {
            $run = $this->service->post($salaryRun->id);

            return response()->json([
                'status' => true,
                'message' => 'Salary run posted to the general ledger.',
                'data' => ['status' => $run->status->value],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function cancel(SalaryRun $salaryRun): JsonResponse
    {
        try {
            $run = $this->service->cancel($salaryRun->id);

            return response()->json([
                'status' => true,
                'message' => 'Salary run cancelled.',
                'data' => ['status' => $run->status->value],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(SalaryRun $salaryRun): JsonResponse
    {
        try {
            $this->service->delete($salaryRun->id);

            return response()->json(['status' => true, 'message' => 'Salary run deleted.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * Printable payslips for the run.
     */
    public function print(SalaryRun $salaryRun): View
    {
        return view('admin.salary-runs.print', ['run' => $this->service->find($salaryRun->id)]);
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

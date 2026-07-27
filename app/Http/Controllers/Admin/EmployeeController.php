<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Employee master screens (M14).
 */
class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $service,
        protected ShiftService $shifts
    ) {}

    public function index(): View
    {
        return view('admin.employees.index', ['shifts' => $this->shifts->selectable()]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.employees.create', $this->lookups());
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        try {
            $employee = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Employee '.$employee->employee_code.' created.',
                'data' => $employee,
                'redirect' => route('admin.employees.index'),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function edit(Employee $employee): View
    {
        return view('admin.employees.edit', array_merge($this->lookups(), [
            'employee' => $this->service->find($employee->id),
        ]));
    }

    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $updated = $this->service->update($employee->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Employee updated.',
                'data' => $updated,
                'redirect' => route('admin.employees.index'),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function destroy(Employee $employee): JsonResponse
    {
        try {
            $this->service->delete($employee->id);

            return response()->json(['status' => true, 'message' => 'Employee deleted.']);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookups(): array
    {
        return [
            'shifts' => $this->shifts->selectable(),
            'branches' => Branch::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ];
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

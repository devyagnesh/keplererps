<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Models\State;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Branch master CRUD (M01).
 */
class BranchController extends Controller
{
    public function __construct(
        protected BranchService $service
    ) {}

    /**
     * List branches.
     */
    public function index(): View
    {
        return view('admin.branches.index');
    }

    /**
     * DataTables JSON.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        return view('admin.branches.create', [
            'states' => State::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a branch.
     */
    public function store(BranchRequest $request): JsonResponse
    {
        try {
            $branch = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Branch created successfully.',
                'data' => $branch,
                'redirect' => route('admin.branches.index'),
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create branch.',
            ], 500);
        }
    }

    /**
     * Edit form.
     */
    public function edit(Branch $branch): View
    {
        return view('admin.branches.edit', [
            'branch' => $branch->load('state'),
            'states' => State::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Update a branch.
     */
    public function update(BranchRequest $request, Branch $branch): JsonResponse
    {
        try {
            $updated = $this->service->update($branch->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Branch updated successfully.',
                'data' => $updated,
                'redirect' => route('admin.branches.index'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update branch.',
            ], 500);
        }
    }

    /**
     * Soft-delete a branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        try {
            $this->service->delete($branch->id);

            return response()->json([
                'status' => true,
                'message' => 'Branch deleted successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Unable to delete branch.',
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete branch.',
            ], 500);
        }
    }
}

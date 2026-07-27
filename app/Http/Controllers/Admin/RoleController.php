<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role and permission matrix management (M02).
 */
class RoleController extends Controller
{
    public function __construct(protected RoleService $service) {}

    public function index(): View
    {
        return view('admin.roles.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->service->permissionsGrouped(),
        ]);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully.',
            'data' => $role,
            'redirect' => route('admin.roles.index'),
        ], 201);
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->service->permissionsGrouped(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        try {
            $updated = $this->service->update($role->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Role updated successfully.',
                'data' => $updated,
                'redirect' => route('admin.roles.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        try {
            $this->service->delete($role->id);

            return response()->json(['status' => true, 'message' => 'Role deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function copy(Role $role): JsonResponse
    {
        $copy = $this->service->copy($role->id);

        return response()->json([
            'status' => true,
            'message' => 'Role copied successfully. Please rename it.',
            'data' => $copy,
            'redirect' => route('admin.roles.edit', $copy),
        ], 201);
    }
}

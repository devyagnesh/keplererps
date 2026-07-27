<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DataScopeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * User management screens (M02).
 */
class UserController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function index(): View
    {
        return view('admin.users.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.users.create', $this->formData());
    }

    public function store(UserRequest $request): JsonResponse
    {
        try {
            $user = $this->service->create($request->validated(), $request->user());

            return response()->json([
                'status' => true,
                'message' => 'User created successfully.',
                'data' => $user,
                'redirect' => route('admin.users.index'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', array_merge($this->formData(), [
            'userModel' => $user->load(['roles', 'dataScope', 'branch']),
        ]));
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        try {
            $updated = $this->service->update($user->id, $request->validated(), $request->user());

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully.',
                'data' => $updated,
                'redirect' => route('admin.users.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $this->service->delete($user->id, request()->user());

            return response()->json(['status' => true, 'message' => 'User deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function permissions(User $user): View
    {
        return view('admin.users.permissions', [
            'userModel' => $user,
            'permissions' => $this->service->effectivePermissions($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id']),
            'scopeTypes' => DataScopeType::cases(),
        ];
    }
}

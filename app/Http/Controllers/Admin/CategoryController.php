<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Category master CRUD.
 */
class CategoryController extends Controller
{
    public function __construct(protected CategoryService $service) {}

    public function index(): View
    {
        return view('admin.categories.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->dataTable($request->all()));
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'parents' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully.',
            'data' => $record,
            'redirect' => route('admin.categories.index'),
        ], 201);
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::query()
                ->where('is_active', true)
                ->where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $record = $this->service->update($category->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully.',
            'data' => $record,
            'redirect' => route('admin.categories.index'),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->service->delete($category->id);

            return response()->json(['status' => true, 'message' => 'Category deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}

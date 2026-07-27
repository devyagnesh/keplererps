<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Shift master screens (M14).
 */
class ShiftController extends Controller
{
    public function __construct(protected ShiftService $service) {}

    public function index(): View
    {
        return view('admin.shifts.index', ['shifts' => $this->service->all()]);
    }

    public function store(ShiftRequest $request): JsonResponse
    {
        $shift = $this->service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Shift created.',
            'data' => $shift,
            'redirect' => route('admin.shifts.index'),
        ], 201);
    }

    public function update(ShiftRequest $request, Shift $shift): JsonResponse
    {
        $updated = $this->service->update($shift->id, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Shift updated.',
            'data' => $updated,
            'redirect' => route('admin.shifts.index'),
        ]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        try {
            $this->service->delete($shift->id);

            return response()->json(['status' => true, 'message' => 'Shift deleted.']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}

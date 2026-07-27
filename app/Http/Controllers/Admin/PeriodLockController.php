<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PeriodLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Accounting period lock screen (M13).
 */
class PeriodLockController extends Controller
{
    public function __construct(protected PeriodLockService $service) {}

    public function index(): View
    {
        return view('admin.period-locks.index', [
            'current' => $this->service->current(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locked_to' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $lock = $this->service->lock($validated);

            return response()->json([
                'status' => true,
                'message' => 'Period locked through '.$lock->locked_to->toDateString().'.',
                'data' => $lock,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupplierRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supplier performance rating screen (M07).
 */
class SupplierRatingController extends Controller
{
    public function __construct(protected SupplierRatingService $service) {}

    public function index(): View
    {
        return view('admin.supplier-ratings.index', [
            'ratings' => $this->service->latest(),
        ]);
    }

    public function recompute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_from' => ['nullable', 'date'],
            'period_to' => ['nullable', 'date', 'after_or_equal:period_from'],
        ]);

        $ratings = $this->service->recompute(
            $validated['period_from'] ?? null,
            $validated['period_to'] ?? null
        );

        return response()->json([
            'status' => true,
            'message' => count($ratings).' supplier rating(s) recomputed.',
            'data' => $ratings,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * System utilities: health check and cache clear.
 */
class SystemUtilityController extends Controller
{
    public function __construct(protected SystemHealthService $service) {}

    public function health(): View
    {
        return view('admin.system.health', [
            'checks' => $this->service->checks(),
        ]);
    }

    public function clearCache(): JsonResponse
    {
        $this->service->clearCaches();

        return response()->json([
            'status' => true,
            'message' => 'Application caches cleared successfully.',
        ]);
    }
}

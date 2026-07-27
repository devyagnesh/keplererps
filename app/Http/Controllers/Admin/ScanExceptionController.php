<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanException;
use App\Services\ScanExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Scan exception listing and resolution.
 */
class ScanExceptionController extends Controller
{
    public function __construct(protected ScanExceptionService $service) {}

    public function index(): View
    {
        return view('admin.scan-exceptions.index', [
            'exceptions' => $this->service->open(),
        ]);
    }

    public function resolve(ScanException $scanException): JsonResponse
    {
        $record = $this->service->resolve($scanException->id);

        return response()->json([
            'status' => true,
            'message' => 'Scan exception resolved.',
            'data' => $record,
        ]);
    }
}

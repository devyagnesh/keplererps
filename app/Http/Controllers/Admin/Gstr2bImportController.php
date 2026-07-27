<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Gstr2bImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * GSTR-2B import / mismatch screen (M13).
 */
class Gstr2bImportController extends Controller
{
    public function __construct(protected Gstr2bImportService $service) {}

    public function index(): View
    {
        return view('admin.gst-reports.gstr2b', [
            'imports' => \App\Models\Gstr2bImport::query()->latest('id')->limit(20)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:7'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {
            $result = $this->service->import($data['file'], $data['period']);

            return response()->json([
                'status' => true,
                'message' => 'GSTR-2B imported. Mismatches: '.$result['import']->mismatch_count,
                'data' => $result['import'],
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

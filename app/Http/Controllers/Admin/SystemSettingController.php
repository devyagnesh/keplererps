<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CostingMethod;
use App\Enums\NumberFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SystemSettingRequest;
use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * General system settings screen (M16).
 */
class SystemSettingController extends Controller
{
    public function __construct(protected SystemSettingService $service) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'grouped' => $this->service->grouped(),
            'costingMethods' => CostingMethod::cases(),
            'numberFormats' => NumberFormat::cases(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(SystemSettingRequest $request): JsonResponse
    {
        try {
            $this->service->update($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Settings saved successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}

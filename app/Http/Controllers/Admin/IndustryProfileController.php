<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IndustryProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Industry profile pack management (install-time / settings activation).
 */
class IndustryProfileController extends Controller
{
    public function __construct(protected IndustryProfileService $industries) {}

    public function index(): View
    {
        return view('admin.customization.industry-profiles', [
            'profiles' => $this->industries->all(),
            'active' => $this->industries->active(),
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        $profile = $this->industries->activate($data['code']);

        return response()->json([
            'status' => true,
            'message' => "Industry profile {$profile->name} activated.",
            'data' => $profile,
        ]);
    }
}

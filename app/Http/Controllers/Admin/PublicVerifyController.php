<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PublicVerifyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public package / CoA verification (no auth) (M17 / M10).
 */
class PublicVerifyController extends Controller
{
    public function __construct(protected PublicVerifyService $service) {}

    public function show(Request $request, string $token): View
    {
        $payload = $this->service->resolve($token);

        return view('public.verify', [
            'payload' => $payload,
            'token' => $token,
        ]);
    }
}

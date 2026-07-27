<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin authentication screens and AJAX login.
 */
class LoginController extends Controller
{
    public function __construct(
        protected LoginService $loginService
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Authenticate via AJAX.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->loginService->attempt(
                $request->validated(),
                $request->ip() ?? '0.0.0.0'
            );

            return response()->json([
                'status' => true,
                'message' => 'Login successful.',
                'data' => [
                    'redirect' => route('admin.dashboard'),
                    'name' => $user->name,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to sign in. Please try again.',
            ], 500);
        }
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->loginService->logout();

        return redirect()->route('admin.login');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is linked to a customer party (portal).
 */
class EnsurePortalCustomer
{
    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->party_id === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer portal access required.',
                ], 403);
            }

            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}

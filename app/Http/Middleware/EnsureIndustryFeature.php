<?php

namespace App\Http\Middleware;

use App\Services\IndustryProfileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks admin routes when the active industry profile has disabled the module.
 */
class EnsureIndustryFeature
{
    public function __construct(protected IndustryProfileService $profiles) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $feature = $this->featureForPath($request->path());

        if ($feature !== null && ! $this->profiles->feature($feature, true)) {
            abort(403, 'This module is not enabled for your industry profile.');
        }

        return $next($request);
    }

    protected function featureForPath(string $path): ?string
    {
        if (preg_match('#^admin/(production-plans|work-orders|production-entries)(/|$)#', $path)) {
            return 'production';
        }

        if (preg_match('#^admin/qc-#', $path)) {
            return 'quality';
        }

        if (preg_match('#^admin/(work-centres|maintenance-orders)(/|$)#', $path)) {
            return 'maintenance';
        }

        return null;
    }
}

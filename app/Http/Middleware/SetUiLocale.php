<?php

namespace App\Http\Middleware;

use App\Services\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies en/hi/gu locale from session or system settings.
 */
class SetUiLocale
{
    public function __construct(protected SystemSettingService $settings) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('ui_locale')
            ?: (string) $this->settings->get('ui_locale', config('app.locale', 'en'));

        if (! in_array($locale, ['en', 'hi', 'gu'], true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}

<?php

use App\Http\Middleware\EnsureIndustryFeature;
use App\Http\Middleware\EnsurePortalCustomer;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            \App\Http\Middleware\SetUiLocale::class,
        ]);
        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
            'portal.customer' => EnsurePortalCustomer::class,
            'industry.feature' => EnsureIndustryFeature::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('portal*') ? route('portal.login') : route('admin.login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            if ($user && $user->party_id) {
                return route('portal.dashboard');
            }

            return route('admin.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || $request->is('admin/*') || $request->is('api/*'),
        );
    })->create();

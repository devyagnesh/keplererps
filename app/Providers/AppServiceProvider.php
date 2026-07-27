<?php

namespace App\Providers;

use App\Models\User;
use App\Services\IndustryProfileService;
use App\Services\UiLabelService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->hasPermissionTo($ability)) {
                return true;
            }

            return null;
        });

        View::composer('admin.partials.sidebar', function ($view): void {
            $view->with('industry', app(IndustryProfileService::class));
            $view->with('uiLabel', app(UiLabelService::class));
        });
    }
}

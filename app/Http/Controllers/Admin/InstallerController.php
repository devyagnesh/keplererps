<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallLock;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Public web installer for cPanel / no-SSH deployments.
 */
class InstallerController extends Controller
{
    /**
     * Show the installation form.
     */
    public function show(): View
    {
        $this->guardNotInstalled();

        return view('installer.index');
    }

    /**
     * Run migrations and seed core data after verifying the install key.
     */
    public function run(Request $request): RedirectResponse
    {
        $this->guardNotInstalled();

        $request->validate([
            'install_key' => ['required', 'string'],
        ]);

        if (! $this->validInstallKey($request)) {
            return back()
                ->withInput()
                ->withErrors(['install_key' => 'Invalid installation key.']);
        }

        Artisan::call('migrate', ['--force' => true]);

        (new PermissionSeeder)->run();
        (new RoleSeeder)->run();
        (new SystemSettingSeeder)->run();

        InstallLock::query()->updateOrCreate(
            ['id' => 1],
            [
                'install_key_hash' => hash('sha256', (string) $request->input('install_key')),
                'is_installed' => true,
                'installed_at' => now(),
                'app_version' => (string) config('app.version', '1.0.0'),
            ]
        );

        $this->symlinkPublicStorage();

        return redirect()
            ->route('admin.login')
            ->with('status', 'Installation completed successfully. You may now log in.');
    }

    /**
     * Show the update form for an already-installed application.
     */
    public function showUpdate(): View
    {
        $this->guardInstalled();

        return view('installer.update');
    }

    /**
     * Run migrations, clear caches, and refresh storage link for updates.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->guardInstalled();

        if (! $this->authorizedForUpdate($request)) {
            return back()
                ->withInput()
                ->withErrors(['install_key' => 'Invalid installation key or insufficient permissions.']);
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');

        $linkResult = $this->symlinkPublicStorage();

        InstallLock::query()->updateOrCreate(
            ['id' => 1],
            [
                'is_installed' => true,
                'app_version' => (string) config('app.version', '1.0.0'),
                'installed_at' => InstallLock::query()->value('installed_at') ?? now(),
            ]
        );

        return redirect()
            ->route('install.update.show')
            ->with('status', 'Update completed. '.$linkResult);
    }

    /**
     * Block access when the application is already installed.
     */
    protected function guardNotInstalled(): void
    {
        if (InstallLock::query()->where('is_installed', true)->exists()) {
            abort(403, 'This application is already installed.');
        }
    }

    /**
     * Block access when the application is not yet installed.
     */
    protected function guardInstalled(): void
    {
        if (! InstallLock::query()->where('is_installed', true)->exists()) {
            abort(404, 'Application is not installed yet.');
        }
    }

    protected function validInstallKey(Request $request): bool
    {
        $envKey = (string) env('INSTALL_KEY', '');

        return $envKey !== '' && hash_equals($envKey, (string) $request->input('install_key'));
    }

    protected function authorizedForUpdate(Request $request): bool
    {
        $user = Auth::user();
        if ($user !== null && $user->hasRole('Super Admin')) {
            return true;
        }

        return $this->validInstallKey($request);
    }

    /**
     * Attempt storage:link; fall back to copying public/storage when symlinks fail.
     */
    protected function symlinkPublicStorage(): string
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        try {
            Artisan::call('storage:link');

            if (is_link($link) || is_dir($link)) {
                return 'Storage link ready.';
            }
        } catch (\Throwable) {
            // Fall through to directory copy on hosts that disallow symlinks.
        }

        if (! is_dir($target)) {
            File::ensureDirectoryExists($target);
        }

        if (is_link($link)) {
            @unlink($link);
        }

        if (is_dir($link)) {
            return 'Public storage directory already exists.';
        }

        if (@File::copyDirectory($target, $link)) {
            return 'Storage link unavailable — copied storage/app/public to public/storage.';
        }

        return 'Storage link failed — copy storage/app/public to public/storage manually if uploads are missing.';
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * System health checks and maintenance utilities (M16).
 */
class SystemHealthService
{
    /**
     * @return list<array{key: string, label: string, status: string, detail: string}>
     */
    public function checks(): array
    {
        return [
            $this->phpVersionCheck(),
            $this->extensionCheck(),
            $this->databaseCheck(),
            $this->storageWritableCheck(),
            $this->diskSpaceCheck(),
            $this->appKeyCheck(),
        ];
    }

    public function clearCaches(): void
    {
        Artisan::call('optimize:clear');
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function phpVersionCheck(): array
    {
        $ok = version_compare(PHP_VERSION, '8.2.0', '>=');

        return [
            'key' => 'php_version',
            'label' => 'PHP version',
            'status' => $ok ? 'ok' : 'fail',
            'detail' => 'Running '.PHP_VERSION.($ok ? '' : ' (requires 8.2+)'),
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function extensionCheck(): array
    {
        $required = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        $missing = array_values(array_filter($required, fn (string $ext): bool => ! extension_loaded($ext)));

        return [
            'key' => 'php_extensions',
            'label' => 'PHP extensions',
            'status' => $missing === [] ? 'ok' : 'fail',
            'detail' => $missing === [] ? 'All required extensions loaded' : 'Missing: '.implode(', ', $missing),
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();
            $name = (string) DB::connection()->getDatabaseName();

            return [
                'key' => 'database',
                'label' => 'Database connectivity',
                'status' => 'ok',
                'detail' => 'Connected to '.$name,
            ];
        } catch (\Throwable $e) {
            return [
                'key' => 'database',
                'label' => 'Database connectivity',
                'status' => 'fail',
                'detail' => 'Connection failed',
            ];
        }
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function storageWritableCheck(): array
    {
        $paths = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];
        $bad = [];
        foreach ($paths as $path) {
            if (! File::isDirectory($path) || ! File::isWritable($path)) {
                $bad[] = $path;
            }
        }

        return [
            'key' => 'storage',
            'label' => 'Folder permissions',
            'status' => $bad === [] ? 'ok' : 'fail',
            'detail' => $bad === [] ? 'Storage and cache directories writable' : 'Not writable: '.implode(', ', $bad),
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function diskSpaceCheck(): array
    {
        $free = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());
        if ($free === false || $total === false || $total <= 0) {
            return [
                'key' => 'disk',
                'label' => 'Disk space',
                'status' => 'warn',
                'detail' => 'Unable to determine free disk space',
            ];
        }

        $freeGb = round($free / 1024 / 1024 / 1024, 2);
        $pct = round(($free / $total) * 100, 1);
        $status = $freeGb < 1 ? 'fail' : ($freeGb < 5 ? 'warn' : 'ok');

        return [
            'key' => 'disk',
            'label' => 'Disk space',
            'status' => $status,
            'detail' => "{$freeGb} GB free ({$pct}%)",
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function appKeyCheck(): array
    {
        $ok = filled(config('app.key'));

        return [
            'key' => 'app_key',
            'label' => 'Application key',
            'status' => $ok ? 'ok' : 'fail',
            'detail' => $ok ? 'APP_KEY is set' : 'APP_KEY is missing',
        ];
    }
}

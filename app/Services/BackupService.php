<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Manual application backup (DB dump + storage) for M16 utilities.
 */
class BackupService
{
    /**
     * @return \Illuminate\Support\Collection<int, BackupLog>
     */
    public function recent()
    {
        return BackupLog::query()->latest('id')->limit(20)->get();
    }

    /**
     * Create a ZIP of storage/app (except backups) plus a SQL dump when mysqldump is available.
     */
    public function create(string $notes = ''): BackupLog
    {
        $stamp = now()->format('Ymd_His');
        $relative = "backups/kepler_backup_{$stamp}.zip";
        Storage::disk('local')->makeDirectory('backups');
        $absolute = Storage::disk('local')->path($relative);

        $zip = new ZipArchive;
        if ($zip->open($absolute, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return BackupLog::query()->create([
                'disk_path' => $relative,
                'size_bytes' => 0,
                'status' => 'failed',
                'notes' => 'Unable to open zip archive.',
                'created_by' => Auth::id(),
            ]);
        }

        $appPath = storage_path('app');
        foreach (File::allFiles($appPath) as $file) {
            $path = $file->getPathname();
            if (str_contains($path, DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            $zip->addFile($path, 'storage-app/'.ltrim(str_replace($appPath, '', $path), '\\/'));
        }

        $sql = $this->dumpDatabase();
        if ($sql !== null) {
            $zip->addFromString('database.sql', $sql);
        } else {
            $zip->addFromString('database.txt', 'mysqldump unavailable — restore storage files only; run migrate/seed as needed.');
        }

        $zip->close();

        return BackupLog::query()->create([
            'disk_path' => $relative,
            'size_bytes' => (int) filesize($absolute),
            'status' => 'ready',
            'notes' => $notes !== '' ? $notes : ($sql !== null ? 'DB + storage' : 'Storage only'),
            'created_by' => Auth::id(),
        ]);
    }

    public function absolutePath(BackupLog $log): string
    {
        return Storage::disk('local')->path($log->disk_path);
    }

    /**
     * Restore storage-app files from a backup ZIP after confirmation.
     */
    public function restore(BackupLog $log, string $confirmation): BackupLog
    {
        $expected = Company::query()->value('legal_name') ?: (string) config('app.name');

        if (! hash_equals($expected, $confirmation)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Confirmation must match the company legal name exactly.',
            ]);
        }

        abort_unless($log->status === 'ready', 422, 'Backup is not ready for restore.');

        $absolute = $this->absolutePath($log);
        abort_unless(is_file($absolute), 404, 'Backup file not found.');

        $zip = new ZipArchive;
        if ($zip->open($absolute) !== true) {
            throw ValidationException::withMessages([
                'confirmation' => 'Unable to open backup archive.',
            ]);
        }

        $appPath = storage_path('app');
        $restored = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if ($entry === false || ! str_starts_with($entry, 'storage-app/')) {
                continue;
            }

            $relative = ltrim(substr($entry, strlen('storage-app/')), '\\/');
            if ($relative === '' || str_starts_with($relative, 'backups'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $target = $appPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $contents = $zip->getFromIndex($index);
            if ($contents === false) {
                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::put($target, $contents);
            $restored++;
        }

        $zip->close();

        $log->forceFill([
            'notes' => trim(($log->notes ?? '')." | Restored {$restored} file(s) at ".now()->toDateTimeString()),
        ])->save();

        return $log->fresh();
    }

    protected function dumpDatabase(): ?string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        if (($config['driver'] ?? '') !== 'mysql') {
            return null;
        }

        $mysqldump = PHP_OS_FAMILY === 'Windows' ? 'mysqldump' : 'mysqldump';
        $host = escapeshellarg((string) ($config['host'] ?? '127.0.0.1'));
        $port = (int) ($config['port'] ?? 3306);
        $user = escapeshellarg((string) ($config['username'] ?? 'root'));
        $pass = (string) ($config['password'] ?? '');
        $db = escapeshellarg((string) ($config['database'] ?? ''));
        $passArg = $pass !== '' ? '-p'.escapeshellarg($pass) : '';

        $cmd = "{$mysqldump} -h {$host} -P {$port} -u {$user} {$passArg} {$db} 2>NUL";
        if (PHP_OS_FAMILY !== 'Windows') {
            $cmd = "{$mysqldump} -h {$host} -P {$port} -u {$user} ".($pass !== '' ? '-p'.escapeshellarg($pass).' ' : '')."{$db} 2>/dev/null";
        }

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        if ($code !== 0 || $output === []) {
            return null;
        }

        return implode("\n", $output);
    }
}

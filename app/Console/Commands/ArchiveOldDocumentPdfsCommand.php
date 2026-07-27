<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Archives document PDF files older than 30 days into monthly ZIP files.
 */
#[Signature('documents:archive-old-pdfs')]
#[Description('Zip document PDF files older than 30 days and remove originals')]
class ArchiveOldDocumentPdfsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(30)->getTimestamp();
        $appPath = storage_path('app');
        $archiveRoot = $appPath.DIRECTORY_SEPARATOR.'archives'.DIRECTORY_SEPARATOR.'pdfs';
        File::ensureDirectoryExists($archiveRoot);

        /** @var array<string, array<string, string>> $grouped */
        $grouped = [];

        foreach (File::allFiles($appPath) as $file) {
            $path = $file->getPathname();

            if (! str_ends_with(strtolower($path), '.pdf')) {
                continue;
            }

            if (str_contains($path, DIRECTORY_SEPARATOR.'archives'.DIRECTORY_SEPARATOR)
                || str_contains($path, DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            if ($file->getMTime() >= $cutoff) {
                continue;
            }

            $month = date('Y-m', $file->getMTime());
            $relative = ltrim(str_replace($appPath, '', $path), '\\/');
            $grouped[$month][$relative] = $path;
        }

        if ($grouped === []) {
            $this->info('No PDF files older than 30 days found.');

            return self::SUCCESS;
        }

        $archived = 0;

        foreach ($grouped as $month => $files) {
            $zipPath = $archiveRoot.DIRECTORY_SEPARATOR.$month.'.zip';
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                $this->error("Unable to open archive {$zipPath}");

                continue;
            }

            foreach ($files as $relative => $absolute) {
                if ($zip->locateName($relative) === false) {
                    $zip->addFile($absolute, $relative);
                }
            }

            $zip->close();

            foreach ($files as $absolute) {
                if (is_file($absolute)) {
                    @unlink($absolute);
                    $archived++;
                }
            }

            $this->info("Archived {$month}: ".count($files).' file(s).');
        }

        $this->info("Archived {$archived} PDF file(s) total.");

        return self::SUCCESS;
    }
}

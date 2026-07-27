<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams keyed report rows as a CSV download.
 *
 * Shared by every register and worksheet export so the header/escaping rules live in one place.
 */
class CsvExportService
{
    /**
     * @param  list<array<string, mixed>>  $rows  Rows sharing the same keys; the first row supplies the header.
     */
    public function stream(array $rows, string $filename): StreamedResponse
    {
        $headers = $rows === [] ? [] : array_keys($rows[0]);

        return response()->streamDownload(function () use ($rows, $headers): void {
            $handle = fopen('php://output', 'wb');

            if ($headers !== []) {
                fputcsv($handle, array_map(
                    fn (string $header): string => str_replace('_', ' ', ucfirst($header)),
                    $headers
                ));
            }

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

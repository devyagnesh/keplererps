<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * Renders printable HTML documents to PDF via DomPDF.
 */
class DocumentPdfService
{
    /**
     * Convert an HTML document snapshot into a PDF binary string.
     */
    public function fromHtml(
        string $html,
        string $paper = 'a4',
        string $orientation = 'portrait',
        ?string $userPassword = null
    ): string {
        try {
            $pdf = Pdf::loadHTML($html)->setPaper($paper, $orientation);

            if ($userPassword !== null && $userPassword !== '') {
                $pdf->setEncryption($userPassword);
            }

            return $pdf->output();
        } catch (\Throwable $e) {
            Log::warning('DomPDF render failed; falling back unavailable.', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

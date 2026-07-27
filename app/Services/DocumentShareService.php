<?php

namespace App\Services;

use App\Models\DocumentShare;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Generates shareable document snapshots (HTML + DomPDF) and signed public links.
 */
class DocumentShareService
{
    public function __construct(
        protected DocumentPrintService $prints,
        protected DocumentPdfService $pdf,
        protected WhatsAppService $whatsApp
    ) {}

    /**
     * @param  array{channel?: string, recipient?: string|null, expires_hours?: int}  $options
     */
    public function share(string $documentType, int $documentId, array $options = []): DocumentShare
    {
        [$documentNo, $html] = $this->render($documentType, $documentId);
        $token = Str::random(40);
        $htmlPath = "document-shares/{$token}.html";
        $pdfPath = "document-shares/{$token}.pdf";

        Storage::disk('local')->put($htmlPath, $html);

        try {
            Storage::disk('local')->put($pdfPath, $this->pdf->fromHtml($html));
        } catch (\Throwable) {
            $pdfPath = null;
        }

        $share = DocumentShare::query()->create([
            'token' => $token,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'document_no' => $documentNo,
            'channel' => $options['channel'] ?? 'link',
            'recipient' => $options['recipient'] ?? null,
            'storage_path' => $htmlPath,
            'pdf_storage_path' => $pdfPath,
            'status' => 'ready',
            'created_by' => Auth::id(),
            'expires_at' => now()->addHours((int) ($options['expires_hours'] ?? 72)),
            'meta' => [
                'has_pdf' => $pdfPath !== null,
            ],
        ]);

        $share->forceFill([
            'public_url' => URL::temporarySignedRoute(
                'public.document-share',
                $share->expires_at ?? now()->addDays(3),
                ['token' => $token]
            ),
        ])->save();

        return $share->fresh();
    }

    /**
     * Create a share and send it on WhatsApp to the given mobile.
     */
    public function sendWhatsApp(string $documentType, int $documentId, string $mobile): DocumentShare
    {
        $share = $this->share($documentType, $documentId, [
            'channel' => 'whatsapp',
            'recipient' => $mobile,
        ]);

        $body = "Your {$documentType} {$share->document_no} is ready.\n{$share->public_url}";
        if ($share->pdf_storage_path) {
            $body .= "\nPDF available via the link above.";
        }

        $result = $this->whatsApp->sendText($mobile, $body, [
            'document_type' => $documentType,
            'document_id' => $documentId,
        ]);

        $share->forceFill([
            'status' => $result['status'],
            'meta' => array_merge((array) $share->meta, $result),
        ])->save();

        return $share->fresh();
    }

    public function findValid(string $token): DocumentShare
    {
        $share = DocumentShare::query()->where('token', $token)->firstOrFail();

        if ($share->expires_at !== null && $share->expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => 'This document link has expired.']);
        }

        return $share;
    }

    public function htmlFor(DocumentShare $share): string
    {
        if ($share->storage_path && Storage::disk('local')->exists($share->storage_path)) {
            return Storage::disk('local')->get($share->storage_path);
        }

        [, $html] = $this->render($share->document_type, $share->document_id);

        return $html;
    }

    /**
     * Binary PDF for a share when DomPDF snapshot exists (or regenerate).
     */
    public function pdfFor(DocumentShare $share): string
    {
        if ($share->pdf_storage_path && Storage::disk('local')->exists($share->pdf_storage_path)) {
            return Storage::disk('local')->get($share->pdf_storage_path);
        }

        $html = $this->htmlFor($share);
        $binary = $this->pdf->fromHtml($html);

        if ($share->pdf_storage_path) {
            Storage::disk('local')->put($share->pdf_storage_path, $binary);
        }

        return $binary;
    }

    public function hasPdf(DocumentShare $share): bool
    {
        return filled($share->pdf_storage_path)
            && Storage::disk('local')->exists($share->pdf_storage_path);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function render(string $documentType, int $documentId): array
    {
        $payload = match ($documentType) {
            'sales_quotation' => $this->prints->salesQuotation($documentId),
            'sales_invoice' => $this->prints->salesInvoice($documentId),
            'purchase_order' => $this->prints->purchaseOrder($documentId),
            'delivery_challan' => $this->prints->deliveryChallan($documentId),
            default => throw ValidationException::withMessages([
                'document_type' => 'Unsupported document type for sharing.',
            ]),
        };

        $html = view('admin.print.document', $payload)->render();
        $document = $payload['document'] ?? null;
        $documentNo = is_object($document) && isset($document->document_no)
            ? (string) $document->document_no
            : (string) ($payload['title'] ?? $documentId);

        return [$documentNo, $html];
    }
}

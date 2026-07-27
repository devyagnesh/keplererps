<?php

namespace App\Services;

use App\Models\EinvoiceLog;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin e-invoice GSP client — dry-runs without credentials, pushes when configured.
 */
class EinvoiceService
{
    public function __construct(
        protected SystemSettingService $settings
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('einvoice_enabled', false);
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->get('einvoice_base_url'))
            && filled($this->settings->get('einvoice_api_key'));
    }

    /**
     * Build and push (or dry-run) an e-invoice for the sales invoice.
     *
     * @return array{log: EinvoiceLog, status: string}
     */
    public function push(SalesInvoice $invoice): array
    {
        $invoice->loadMissing('customer:id,party_name,gstin');

        $payload = [
            'document_no' => $invoice->document_no,
            'document_date' => $invoice->document_date?->toDateString(),
            'party_gstin' => $invoice->customer?->gstin,
            'party_name' => $invoice->customer?->party_name,
            'grand_total' => round((float) $invoice->grand_total, 2),
        ];

        if (! $this->isEnabled()) {
            $log = $this->storeLog($invoice, $payload, 'queued', null, null, [
                'reason' => 'einvoice_disabled',
            ]);

            return ['log' => $log, 'status' => 'queued'];
        }

        if (! $this->isConfigured()) {
            $irn = $this->stubIrn($invoice);
            Log::info('E-invoice dry-run (credentials missing).', [
                'invoice_id' => $invoice->id,
                'document_no' => $invoice->document_no,
                'irn' => $irn,
            ]);

            $log = $this->storeLog($invoice, $payload, 'dry_run', $irn, 'DRY-'.now()->format('YmdHis'), [
                'dry_run' => true,
                'message' => 'E-invoice credentials not configured.',
            ]);

            return ['log' => $log, 'status' => 'dry_run'];
        }

        if ($this->isDemoMode()) {
            $irn = $this->stubIrn($invoice);
            Log::info('E-invoice demo push (no outbound HTTP).', [
                'invoice_id' => $invoice->id,
                'document_no' => $invoice->document_no,
                'irn' => $irn,
            ]);

            $log = $this->storeLog($invoice, $payload, 'demo_pushed', $irn, 'DEMO-'.now()->format('YmdHis'), [
                'demo' => true,
                'message' => 'E-invoice demo mode — no outbound HTTP.',
            ]);

            return ['log' => $log, 'status' => 'demo_pushed'];
        }

        $baseUrl = rtrim((string) $this->settings->get('einvoice_base_url'), '/');
        $apiKey = (string) $this->settings->get('einvoice_api_key');
        $endpoint = $baseUrl.'/einvoice/generate';

        $response = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            Log::warning('E-invoice push failed.', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            $log = $this->storeLog($invoice, $payload, 'failed', null, null, [
                'http_status' => $response->status(),
                'body' => $response->json(),
            ]);

            return ['log' => $log, 'status' => 'failed'];
        }

        $body = $response->json() ?? [];
        $log = $this->storeLog(
            $invoice,
            $payload,
            'pushed',
            $body['irn'] ?? $body['Irn'] ?? null,
            $body['ack_no'] ?? $body['AckNo'] ?? null,
            $body
        );

        return ['log' => $log, 'status' => 'pushed'];
    }

    protected function stubIrn(SalesInvoice $invoice): string
    {
        return hash('sha256', $invoice->id.'|'.now()->timestamp);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $response
     */
    protected function storeLog(
        SalesInvoice $invoice,
        array $payload,
        string $status,
        ?string $irn,
        ?string $ackNo,
        array $response
    ): EinvoiceLog {
        return EinvoiceLog::query()->create([
            'sales_invoice_id' => $invoice->id,
            'status' => $status,
            'irn' => $irn,
            'ack_no' => $ackNo,
            'payload' => $payload,
            'response' => $response,
            'created_by' => Auth::id(),
        ]);
    }

    protected function isDemoMode(): bool
    {
        return IntegrationDemoDetector::isDemoCredential((string) $this->settings->get('einvoice_api_key'))
            || IntegrationDemoDetector::isDemoUrl((string) $this->settings->get('einvoice_base_url'));
    }
}

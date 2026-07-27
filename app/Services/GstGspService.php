<?php

namespace App\Services;

use App\Models\GspFilingLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin GST GSP client — dry-runs without credentials, pushes worksheet JSON when configured.
 */
class GstGspService
{
    public function __construct(
        protected SystemSettingService $settings,
        protected GstReportService $reports
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('gsp_enabled', false);
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->get('gsp_base_url'))
            && filled($this->settings->get('gsp_api_key'));
    }

    /**
     * Build and push (or dry-run) a GSTR-1 style outward worksheet for the period.
     *
     * @return array{log: GspFilingLog, status: string}
     */
    public function pushOutward(string $fromDate, string $toDate): array
    {
        $rows = $this->reports->outwardSupplies($fromDate, $toDate);
        $payload = [
            'return_type' => 'gstr1',
            'period_from' => $fromDate,
            'period_to' => $toDate,
            'gstin' => (string) $this->settings->get('gsp_gstin', ''),
            'rows' => $rows,
            'summary' => $this->reports->summary($fromDate, $toDate)['outward'] ?? [],
        ];

        return $this->dispatch('gstr1', $fromDate, $toDate, $payload, count($rows));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{log: GspFilingLog, status: string}
     */
    protected function dispatch(string $returnType, string $fromDate, string $toDate, array $payload, int $rowCount): array
    {
        if (! $this->isEnabled()) {
            $log = $this->storeLog($returnType, $fromDate, $toDate, $rowCount, $payload, 'queued', [
                'reason' => 'gsp_disabled',
            ]);

            return ['log' => $log, 'status' => 'queued'];
        }

        if (! $this->isConfigured()) {
            Log::info('GSP dry-run (credentials missing).', [
                'return_type' => $returnType,
                'from' => $fromDate,
                'to' => $toDate,
                'rows' => $rowCount,
            ]);

            $log = $this->storeLog($returnType, $fromDate, $toDate, $rowCount, $payload, 'dry_run', [
                'dry_run' => true,
                'message' => 'GSP credentials not configured.',
            ]);

            return ['log' => $log, 'status' => 'dry_run'];
        }

        if ($this->isDemoMode()) {
            Log::info('GSP demo push (no outbound HTTP).', [
                'return_type' => $returnType,
                'from' => $fromDate,
                'to' => $toDate,
                'rows' => $rowCount,
            ]);

            $log = $this->storeLog($returnType, $fromDate, $toDate, $rowCount, $payload, 'demo_pushed', [
                'demo' => true,
                'message' => 'GSP demo mode — no outbound HTTP.',
            ]);

            return ['log' => $log, 'status' => 'demo_pushed'];
        }

        $baseUrl = rtrim((string) $this->settings->get('gsp_base_url'), '/');
        $apiKey = (string) $this->settings->get('gsp_api_key');
        $endpoint = $baseUrl.'/gstr1/push';

        $response = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            Log::warning('GSP push failed.', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            $log = $this->storeLog($returnType, $fromDate, $toDate, $rowCount, $payload, 'failed', [
                'http_status' => $response->status(),
                'body' => $response->json(),
            ]);

            return ['log' => $log, 'status' => 'failed'];
        }

        $log = $this->storeLog($returnType, $fromDate, $toDate, $rowCount, $payload, 'pushed', [
            'http_status' => $response->status(),
            'body' => $response->json(),
        ]);

        return ['log' => $log, 'status' => 'pushed'];
    }

    /**
     * Submit an e-way bill payload to the GSP (dry-run without credentials).
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, eway_bill_number: string|null, response: array<string, mixed>}
     */
    public function submitEwayBill(array $payload): array
    {
        if (! $this->isEnabled()) {
            return [
                'status' => 'queued',
                'eway_bill_number' => null,
                'response' => ['reason' => 'gsp_disabled'],
            ];
        }

        if (! $this->isConfigured()) {
            $stub = '9'.substr(preg_replace('/\D+/', '', (string) ($payload['document_no'] ?? uniqid())) ?: '00000000000', 0, 11);
            $stub = str_pad(substr($stub, 0, 12), 12, '0');

            Log::info('GSP e-way dry-run (credentials missing).', [
                'document_no' => $payload['document_no'] ?? null,
                'stub_eway' => $stub,
            ]);

            return [
                'status' => 'dry_run',
                'eway_bill_number' => $stub,
                'response' => ['dry_run' => true, 'eway_bill_number' => $stub],
            ];
        }

        if ($this->isDemoMode()) {
            $stub = $this->stubEwayNumber($payload);

            Log::info('GSP e-way demo push (no outbound HTTP).', [
                'document_no' => $payload['document_no'] ?? null,
                'stub_eway' => $stub,
            ]);

            return [
                'status' => 'demo_pushed',
                'eway_bill_number' => $stub,
                'response' => ['demo' => true, 'eway_bill_number' => $stub],
            ];
        }

        $baseUrl = rtrim((string) $this->settings->get('gsp_base_url'), '/');
        $apiKey = (string) $this->settings->get('gsp_api_key');
        $response = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->post($baseUrl.'/eway/generate', $payload);

        if (! $response->successful()) {
            Log::warning('GSP e-way submit failed.', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'status' => 'failed',
                'eway_bill_number' => null,
                'response' => is_array($response->json()) ? $response->json() : ['http_status' => $response->status()],
            ];
        }

        $json = $response->json();

        return [
            'status' => 'pushed',
            'eway_bill_number' => (string) (data_get($json, 'eway_bill_number') ?? data_get($json, 'ewbNo') ?? ''),
            'response' => is_array($json) ? $json : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $response
     */
    protected function storeLog(
        string $returnType,
        string $fromDate,
        string $toDate,
        int $rowCount,
        array $payload,
        string $status,
        array $response
    ): GspFilingLog {
        return GspFilingLog::query()->create([
            'return_type' => $returnType,
            'period_from' => $fromDate,
            'period_to' => $toDate,
            'status' => $status,
            'row_count' => $rowCount,
            'payload' => $payload,
            'response' => $response,
            'created_by' => Auth::id(),
        ]);
    }

    protected function isDemoMode(): bool
    {
        return IntegrationDemoDetector::isDemoCredential((string) $this->settings->get('gsp_api_key'))
            || IntegrationDemoDetector::isDemoUrl((string) $this->settings->get('gsp_base_url'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function stubEwayNumber(array $payload): string
    {
        $stub = '9'.substr(preg_replace('/\D+/', '', (string) ($payload['document_no'] ?? uniqid())) ?: '00000000000', 0, 11);

        return str_pad(substr($stub, 0, 12), 12, '0');
    }
}

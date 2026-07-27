<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API client — templates for business-initiated outbound (§7.4).
 */
class WhatsAppService
{
    public function __construct(protected SystemSettingService $settings) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('whatsapp_enabled', false);
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->get('whatsapp_token'))
            && filled($this->settings->get('whatsapp_phone_number_id'));
    }

    /**
     * Send a Meta-approved template message (preferred for business-initiated alerts).
     *
     * @param  list<string>  $bodyParams
     * @param  array<string, mixed>  $context
     * @return array{status: string, message_id: string|null, dry_run: bool, response: array<string, mixed>|null}
     */
    public function sendTemplate(
        string $toMobile,
        string $templateName,
        array $bodyParams = [],
        string $languageCode = 'en',
        array $context = []
    ): array {
        $to = $this->normalizeMobile($toMobile);
        $components = [];
        if ($bodyParams !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $text): array => ['type' => 'text', 'text' => $text],
                    $bodyParams
                ),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->dispatch($to, $payload, array_merge($context, [
            'template' => $templateName,
            'params' => $bodyParams,
        ]));
    }

    /**
     * Free-text send — only for session replies; prefer sendTemplate for outbound alerts.
     *
     * @param  array<string, mixed>  $context
     * @return array{status: string, message_id: string|null, dry_run: bool, response: array<string, mixed>|null}
     */
    public function sendText(string $toMobile, string $body, array $context = []): array
    {
        $to = $this->normalizeMobile($toMobile);

        return $this->dispatch($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => true, 'body' => $body],
        ], array_merge($context, ['body' => $body]));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{status: string, message_id: string|null, dry_run: bool, response: array<string, mixed>|null}
     */
    protected function dispatch(string $to, array $payload, array $context = []): array
    {
        if (! $this->isEnabled()) {
            Log::info('WhatsApp skipped: channel disabled.', ['to' => $to, 'context' => $context]);

            return [
                'status' => 'skipped',
                'message_id' => null,
                'dry_run' => true,
                'response' => ['reason' => 'whatsapp_disabled'],
            ];
        }

        if (! $this->isConfigured()) {
            Log::info('WhatsApp dry-run (credentials missing).', [
                'to' => $to,
                'payload' => $payload,
                'context' => $context,
            ]);

            return [
                'status' => 'queued_dry_run',
                'message_id' => 'dry-'.uniqid(),
                'dry_run' => true,
                'response' => $payload,
            ];
        }

        if ($this->isDemoMode()) {
            Log::info('WhatsApp demo send (no outbound HTTP).', [
                'to' => $to,
                'context' => $context,
            ]);

            return [
                'status' => 'demo_sent',
                'message_id' => 'demo-'.uniqid(),
                'dry_run' => true,
                'response' => ['demo' => true, 'payload' => $payload],
            ];
        }

        $phoneNumberId = (string) $this->settings->get('whatsapp_phone_number_id');
        $token = (string) $this->settings->get('whatsapp_token');
        $version = (string) $this->settings->get('whatsapp_api_version', 'v19.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('WhatsApp send failed.', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'status' => 'failed',
                'message_id' => null,
                'dry_run' => false,
                'response' => $response->json(),
            ];
        }

        $json = $response->json();

        return [
            'status' => 'sent',
            'message_id' => data_get($json, 'messages.0.id'),
            'dry_run' => false,
            'response' => is_array($json) ? $json : null,
        ];
    }

    protected function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?: '';

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return $digits;
    }

    protected function isDemoMode(): bool
    {
        return IntegrationDemoDetector::isDemoCredential((string) $this->settings->get('whatsapp_token'))
            || IntegrationDemoDetector::isDemoCredential((string) $this->settings->get('whatsapp_phone_number_id'));
    }
}

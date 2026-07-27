<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging thin client (dry-run without server key).
 */
class FirebasePushService
{
    public function __construct(protected SystemSettingService $settings) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('firebase_enabled', false);
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->get('firebase_server_key'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{status: string, dry_run: bool}
     */
    public function sendToToken(string $deviceToken, string $title, string $body, array $data = []): array
    {
        if (! $this->isEnabled()) {
            return ['status' => 'skipped', 'dry_run' => true];
        }

        if (! $this->isConfigured() || $deviceToken === '') {
            Log::info('Firebase dry-run push.', compact('deviceToken', 'title', 'body', 'data'));

            return ['status' => 'queued_dry_run', 'dry_run' => true];
        }

        if ($this->isDemoMode()) {
            Log::info('Firebase demo push (no outbound HTTP).', compact('title', 'body', 'data'));

            return ['status' => 'demo_sent', 'dry_run' => true];
        }

        $response = Http::withHeaders([
            'Authorization' => 'key='.$this->settings->get('firebase_server_key'),
            'Content-Type' => 'application/json',
        ])->timeout(15)->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $deviceToken,
            'notification' => ['title' => $title, 'body' => $body],
            'data' => $data,
        ]);

        return [
            'status' => $response->successful() ? 'sent' : 'failed',
            'dry_run' => false,
        ];
    }

    protected function isDemoMode(): bool
    {
        return IntegrationDemoDetector::isDemoCredential((string) $this->settings->get('firebase_server_key'));
    }
}

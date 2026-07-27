<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Meta WhatsApp webhook verification and inbound message handler.
 */
class WhatsAppWebhookService
{
    public function __construct(
        protected SystemSettingService $settings
    ) {}

    /**
     * Verify webhook subscription challenge from Meta.
     */
    public function verify(string $mode, string $token, string $challenge): ?string
    {
        $expected = (string) $this->settings->get('whatsapp_verify_token', '');

        if ($mode === 'subscribe' && filled($expected) && hash_equals($expected, $token)) {
            return $challenge;
        }

        return null;
    }

    /**
     * Handle inbound webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string}
     */
    public function handle(array $payload): array
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    Log::info('WhatsApp inbound message.', [
                        'from' => $message['from'] ?? null,
                        'type' => $message['type'] ?? null,
                        'message_id' => $message['id'] ?? null,
                        'payload' => $message,
                    ]);
                }
            }
        }

        return ['status' => 'ok'];
    }
}

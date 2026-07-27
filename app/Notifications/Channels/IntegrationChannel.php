<?php

namespace App\Notifications\Channels;

use App\Enums\NotificationEvent;
use App\Notifications\CatalogueNotification;
use App\Services\FirebasePushService;
use App\Services\SystemSettingService;
use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;

/**
 * Custom Laravel notification channel for WhatsApp / Firebase catalogue rules.
 */
class IntegrationChannel
{
    public function __construct(
        protected WhatsAppService $whatsApp,
        protected FirebasePushService $firebase,
        protected SystemSettingService $settings
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof CatalogueNotification) {
            return;
        }

        $channel = $notification->rule->channel->value;
        $payload = $notification->toArray($notifiable);
        $subject = (string) ($payload['subject'] ?? '');
        $body = (string) ($payload['body'] ?? '');
        $url = $notification->url;
        $event = $notification->rule->event;

        if ($channel === 'whatsapp') {
            $mobile = (string) ($notifiable->mobile ?? '');
            if ($mobile === '') {
                return;
            }

            $template = $this->resolveWhatsAppTemplate($event);
            $context = [
                'rule' => $notification->rule->code,
                'event' => $event->value,
            ];

            if ($template !== '') {
                $this->whatsApp->sendTemplate(
                    $mobile,
                    $template,
                    $this->templateBodyParams($event, $subject, $body),
                    'en',
                    $context
                );

                return;
            }

            $message = trim($subject."\n".$body.($url ? "\n".$url : ''));
            $this->whatsApp->sendText($mobile, $message, $context);

            return;
        }

        if ($channel === 'firebase') {
            $token = (string) ($notifiable->fcm_token ?? '');
            $this->firebase->sendToToken($token, $subject, $body, [
                'url' => (string) $url,
                'rule' => $notification->rule->code,
            ]);
        }
    }

    protected function resolveWhatsAppTemplate(NotificationEvent $event): string
    {
        $dispatchEvents = [
            NotificationEvent::GoodsDispatchedCustomer,
            NotificationEvent::DeliveryChallanDispatched,
            NotificationEvent::GoodsReceiptPosted,
        ];

        $salaryEvents = [
            NotificationEvent::SalaryRunPosted,
            NotificationEvent::SalarySlipGenerated,
        ];

        if (in_array($event, $dispatchEvents, true)) {
            return trim((string) $this->settings->get('whatsapp_template_dispatch', ''));
        }

        if (in_array($event, $salaryEvents, true)) {
            return trim((string) $this->settings->get('whatsapp_template_salary_slip', ''));
        }

        return 'erp_alert';
    }

    /**
     * @return list<string>
     */
    protected function templateBodyParams(NotificationEvent $event, string $subject, string $body): array
    {
        $salaryEvents = [
            NotificationEvent::SalaryRunPosted,
            NotificationEvent::SalarySlipGenerated,
        ];

        if (in_array($event, $salaryEvents, true)) {
            return array_values(array_filter([
                $this->truncate($subject, 60),
                $this->truncate($body, 60),
            ]));
        }

        $dispatchEvents = [
            NotificationEvent::GoodsDispatchedCustomer,
            NotificationEvent::DeliveryChallanDispatched,
            NotificationEvent::GoodsReceiptPosted,
        ];

        if (in_array($event, $dispatchEvents, true)) {
            return array_values(array_filter([
                $this->truncate($subject, 60),
                $this->truncate($body, 120),
            ]));
        }

        return array_values(array_filter([
            $this->truncate($subject, 60),
            $this->truncate($body, 120),
        ]));
    }

    protected function truncate(string $value, int $max): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1).'…' : $value;
    }
}

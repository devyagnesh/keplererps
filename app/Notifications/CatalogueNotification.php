<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Models\NotificationRule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Catalogue-driven notification delivered in-app and/or by email (M16).
 */
class CatalogueNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, string>  $placeholders  Keys without braces, e.g. document_no => PO-001.
     */
    public function __construct(
        public NotificationRule $rule,
        public array $placeholders = [],
        public ?string $url = null
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return match ($this->rule->channel) {
            NotificationChannel::InApp => ['database'],
            NotificationChannel::Email => ['mail'],
            NotificationChannel::WhatsApp, NotificationChannel::Firebase => [
                \App\Notifications\Channels\IntegrationChannel::class,
            ],
            default => [],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->render($this->rule->subject_template))
            ->line($this->render($this->rule->body_template));

        if ($this->url !== null && $this->url !== '') {
            $mail->action('Open in ERP', url($this->url));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'rule_code' => $this->rule->code,
            'event' => $this->rule->event->value,
            'subject' => $this->render($this->rule->subject_template),
            'body' => $this->render($this->rule->body_template),
            'url' => $this->url,
        ];
    }

    /**
     * Replace {{key}} placeholders in a template string.
     */
    protected function render(string $template): string
    {
        $replacements = [];
        foreach ($this->placeholders as $key => $value) {
            $replacements['{{'.$key.'}}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}

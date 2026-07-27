<?php

namespace App\Enums;

/**
 * Delivery channels for catalogue notification rules (M16).
 */
enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Firebase = 'firebase';

    /**
     * Human-readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::InApp => 'In-app',
            self::Email => 'Email',
            self::WhatsApp => 'WhatsApp',
            self::Firebase => 'Firebase push',
        };
    }

    /**
     * Whether this channel can actually deliver messages today.
     *
     * WhatsApp / Firebase support dry-run when credentials are not configured.
     */
    public function isSupported(): bool
    {
        return true;
    }

    /**
     * Bootstrap badge class for UI.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InApp => 'bg-primary-transparent',
            self::Email => 'bg-info-transparent',
            self::WhatsApp => 'bg-success-transparent',
            self::Firebase => 'bg-warning-transparent',
        };
    }
}

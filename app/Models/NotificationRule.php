<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Configurable notification rule in the M16 catalogue.
 *
 * @property string $code
 * @property string $name
 * @property NotificationEvent $event
 * @property NotificationChannel $channel
 * @property NotificationRecipientType $recipient_type
 * @property string $recipient_value
 * @property string $subject_template
 * @property string $body_template
 * @property bool $is_active
 * @property bool $is_system
 */
class NotificationRule extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationRuleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'event',
        'channel',
        'recipient_type',
        'recipient_value',
        'subject_template',
        'body_template',
        'is_active',
        'is_system',
        'sort_order',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'channel' => 'in_app',
        'is_active' => true,
        'is_system' => false,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => NotificationEvent::class,
            'channel' => NotificationChannel::class,
            'recipient_type' => NotificationRecipientType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

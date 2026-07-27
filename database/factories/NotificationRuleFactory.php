<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Models\NotificationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationRule>
 */
class NotificationRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('NR_##??')),
            'name' => fake()->sentence(3),
            'event' => NotificationEvent::PurchaseOrderApproved,
            'channel' => NotificationChannel::InApp,
            'recipient_type' => NotificationRecipientType::Role,
            'recipient_value' => 'admin',
            'subject_template' => 'PO {{document_no}} approved',
            'body_template' => 'Purchase order {{document_no}} was approved.',
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 0,
        ];
    }
}

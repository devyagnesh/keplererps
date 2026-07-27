<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Models\NotificationRule;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Database\Seeders\NotificationRuleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M16 notification rule catalogue and in-app dispatch.
 */
class NotificationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_rules_are_seeded_and_catalogue_renders(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(NotificationRuleSeeder::class);

        $user = User::factory()->superAdmin()->create();

        $this->assertGreaterThan(0, NotificationRule::query()->count());

        $this->actingAs($user)
            ->get(route('admin.notification-rules.index'))
            ->assertOk()
            ->assertSee('Notification Rules')
            ->assertSee('PO_APPROVED_ADMIN');
    }

    public function test_custom_rule_can_be_created_updated_toggled_and_deleted(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->superAdmin()->create();
        Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'is_active' => true, 'is_system' => true, 'level' => 10]
        );

        $id = $this->actingAs($user)->postJson(route('admin.notification-rules.store'), [
            'name' => 'Custom PO ping',
            'event' => NotificationEvent::PurchaseOrderApproved->value,
            'channel' => NotificationChannel::InApp->value,
            'recipient_type' => NotificationRecipientType::Role->value,
            'recipient_value' => 'admin',
            'subject_template' => 'Custom {{document_no}}',
            'body_template' => 'Body for {{document_no}}',
            'is_active' => true,
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->putJson(route('admin.notification-rules.update', $id), [
            'name' => 'Custom PO ping updated',
            'event' => NotificationEvent::PurchaseOrderApproved->value,
            'channel' => NotificationChannel::InApp->value,
            'recipient_type' => NotificationRecipientType::Role->value,
            'recipient_value' => 'admin',
            'subject_template' => 'Updated {{document_no}}',
            'body_template' => 'Updated body',
            'is_active' => true,
        ])->assertOk();

        $this->actingAs($user)->postJson(route('admin.notification-rules.toggle', $id))->assertOk();
        $this->assertFalse(NotificationRule::query()->findOrFail($id)->is_active);

        $this->actingAs($user)->deleteJson(route('admin.notification-rules.destroy', $id))->assertOk();
        $this->assertSoftDeleted('notification_rules', ['id' => $id]);
    }

    public function test_system_rules_cannot_be_deleted_and_unsupported_channels_are_rejected(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(NotificationRuleSeeder::class);

        $user = User::factory()->superAdmin()->create();
        $system = NotificationRule::query()->where('is_system', true)->firstOrFail();

        $this->actingAs($user)
            ->deleteJson(route('admin.notification-rules.destroy', $system))
            ->assertStatus(422);

        $this->actingAs($user)->postJson(route('admin.notification-rules.store'), [
            'name' => 'WhatsApp attempt',
            'event' => NotificationEvent::LeadConverted->value,
            'channel' => NotificationChannel::WhatsApp->value,
            'recipient_type' => NotificationRecipientType::Role->value,
            'recipient_value' => 'admin',
            'subject_template' => 'Hello',
            'body_template' => 'World',
        ])->assertStatus(422);
    }

    public function test_dispatch_writes_database_notifications_for_matching_recipients(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $recipient = User::factory()->create(['is_active' => true]);
        $recipient->syncRoles([$adminRole->id]);

        $outsider = User::factory()->create(['is_active' => true]);

        NotificationRule::factory()->create([
            'event' => NotificationEvent::SalaryRunPosted,
            'channel' => NotificationChannel::InApp,
            'recipient_type' => NotificationRecipientType::Role,
            'recipient_value' => 'admin',
            'subject_template' => 'Salary {{document_no}} posted',
            'body_template' => 'Period {{period}} net {{net_total}}',
            'is_active' => true,
        ]);

        $sent = app(NotificationDispatchService::class)->dispatch(
            NotificationEvent::SalaryRunPosted,
            [
                'document_no' => 'PAY-0001',
                'period' => 'Jun 2026',
                'net_total' => '12500.00',
            ],
            '/admin/salary-runs/1/edit'
        );

        $this->assertSame(1, $sent);
        $this->assertSame(1, $recipient->fresh()->notifications()->count());
        $this->assertSame(0, $outsider->fresh()->notifications()->count());

        $payload = $recipient->notifications()->first()->data;
        $this->assertSame('Salary PAY-0001 posted', $payload['subject']);
        $this->assertStringContainsString('12500.00', $payload['body']);
    }

    public function test_inactive_rules_are_skipped_and_inbox_can_mark_read(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $user = User::factory()->superAdmin()->create();
        $user->syncRoles([$adminRole->id]);

        NotificationRule::factory()->create([
            'event' => NotificationEvent::PurchaseOrderApproved,
            'recipient_type' => NotificationRecipientType::Role,
            'recipient_value' => 'admin',
            'is_active' => false,
        ]);

        $sent = app(NotificationDispatchService::class)->dispatch(
            NotificationEvent::PurchaseOrderApproved,
            ['document_no' => 'PO-1', 'party_name' => 'Acme']
        );
        $this->assertSame(0, $sent);

        NotificationRule::factory()->create([
            'event' => NotificationEvent::PurchaseOrderApproved,
            'recipient_type' => NotificationRecipientType::Role,
            'recipient_value' => 'admin',
            'is_active' => true,
            'subject_template' => 'PO {{document_no}}',
            'body_template' => '{{party_name}}',
        ]);

        app(NotificationDispatchService::class)->dispatch(
            NotificationEvent::PurchaseOrderApproved,
            ['document_no' => 'PO-2', 'party_name' => 'Acme']
        );

        $notificationId = $user->fresh()->unreadNotifications()->firstOrFail()->id;

        $this->actingAs($user)->get(route('admin.notifications.index'))->assertOk()->assertSee('PO-2');
        $this->actingAs($user)->postJson(route('admin.notifications.mark-read', $notificationId))->assertOk();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}

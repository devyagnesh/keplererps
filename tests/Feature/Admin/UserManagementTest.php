<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for user management (M02).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_role_and_scope(): void
    {
        $this->seed(StateSeeder::class);
        $this->seed(PermissionSeeder::class);
        $branch = Branch::factory()->create();
        $role = Role::factory()->create();
        $role->permissions()->sync(Permission::query()->take(2)->pluck('id'));
        $actor = User::factory()->superAdmin()->create();

        $this->actingAs($actor)
            ->postJson(route('admin.users.store'), [
                'name' => 'Ramesh Patel',
                'username' => 'ramesh',
                'email' => 'ramesh@example.com',
                'mobile' => '9876501234',
                'password' => 'Password1',
                'password_confirmation' => 'Password1',
                'branch_id' => $branch->id,
                'role_ids' => [$role->id],
                'scope_type' => 'own',
                'require_2fa' => 0,
                'is_active' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $user = User::query()->where('username', 'ramesh')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles->contains('id', $role->id));
        $this->assertSame('own', $user->dataScope?->scope_type->value);
    }

    public function test_user_cannot_edit_own_roles(): void
    {
        $this->seed(PermissionSeeder::class);
        $branch = Branch::factory()->create();
        $roleA = Role::factory()->create(['slug' => 'role-a']);
        $roleB = Role::factory()->create(['slug' => 'role-b']);
        $roleA->permissions()->sync(Permission::query()->pluck('id'));
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles([$roleA->id]);

        $this->actingAs($user)
            ->putJson(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile ?: '9876501234',
                'branch_id' => $branch->id,
                'role_ids' => [$roleB->id],
                'scope_type' => 'all',
                'require_2fa' => 0,
                'is_active' => 1,
            ])
            ->assertOk();

        $user->refresh()->load('roles');
        $this->assertTrue($user->roles->contains('id', $roleA->id));
        $this->assertFalse($user->roles->contains('id', $roleB->id));
    }

    public function test_effective_permissions_page_loads(): void
    {
        $this->seed(PermissionSeeder::class);
        $role = Role::factory()->create();
        $role->permissions()->sync(Permission::query()->pluck('id'));
        $user = User::factory()->create();
        $user->syncRoles([$role->id]);

        $this->actingAs($user)
            ->get(route('admin.users.permissions', $user))
            ->assertOk()
            ->assertSee('Effective Permissions');
    }
}

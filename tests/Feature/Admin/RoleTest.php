<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for role management.
 */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_be_created_with_permissions(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = User::factory()->superAdmin()->create();
        $permissionIds = Permission::query()->take(3)->pluck('id')->all();

        $this->actingAs($actor)
            ->postJson(route('admin.roles.store'), [
                'name' => 'Store Keeper',
                'slug' => 'store-keeper',
                'level' => 10,
                'require_2fa' => 0,
                'simplified_ui' => 1,
                'is_active' => 1,
                'permission_ids' => $permissionIds,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $role = Role::query()->where('slug', 'store-keeper')->first();
        $this->assertNotNull($role);
        $this->assertCount(3, $role->permissions);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $role = Role::query()->where('slug', 'super-admin')->firstOrFail();

        $this->actingAs($actor)
            ->deleteJson(route('admin.roles.destroy', $role))
            ->assertStatus(422);
    }

    public function test_role_can_be_copied(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = User::factory()->superAdmin()->create();
        $role = Role::factory()->create(['name' => 'Planner']);
        $role->permissions()->sync(Permission::query()->take(2)->pluck('id'));

        $this->actingAs($actor)
            ->postJson(route('admin.roles.copy', $role))
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('roles', ['name' => 'Planner (Copy)']);
    }
}

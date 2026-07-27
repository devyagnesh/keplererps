<?php

namespace Database\Seeders;

use App\Models\DashboardRoleWidget;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds system roles and assigns Super Admin.
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full system access',
                'is_system' => true,
                'level' => 1000,
                'require_2fa' => false,
                'simplified_ui' => false,
                'is_active' => true,
            ]
        );

        $admin = Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Operational administrator',
                'is_system' => true,
                'level' => 900,
                'is_active' => true,
            ]
        );

        $allPermissionIds = Permission::query()->pluck('id');
        $superAdmin->permissions()->sync($allPermissionIds);

        $adminPermissionIds = Permission::query()
            ->whereNotIn('name', ['role.delete', 'user.delete'])
            ->pluck('id');
        $admin->permissions()->sync($adminPermissionIds);

        $user = User::query()->where('email', 'admin@keplererp.local')->first();
        if ($user !== null) {
            $user->syncRoles([$superAdmin->id]);
        }

        DashboardRoleWidget::query()->updateOrCreate(
            ['role_name' => 'Admin'],
            ['widget_keys' => ['sales', 'purchase', 'inventory', 'finance', 'approvals', 'crm']]
        );
        DashboardRoleWidget::query()->updateOrCreate(
            ['role_name' => 'Super Admin'],
            ['widget_keys' => ['sales', 'purchase', 'inventory', 'production', 'maintenance', 'finance', 'approvals', 'crm']]
        );
    }
}

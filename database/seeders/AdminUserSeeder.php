<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the default Super Admin user and head-office branch.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $state = State::query()->where('code', '24')->first();

        $branch = Branch::query()->updateOrCreate(
            ['code' => 'HO'],
            [
                'name' => 'Head Office',
                'address' => 'Main Plant',
                'state_id' => $state?->id,
                'pin_code' => '380015',
                'phone' => '9876543210',
                'email' => 'ho@keplererp.local',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@keplererp.local'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'mobile' => '9876543210',
                'password' => Hash::make('Admin@123'),
                'is_active' => true,
                'must_change_password' => false,
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
            ]
        );
    }
}

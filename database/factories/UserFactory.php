<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => '9'.fake()->unique()->numerify('#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Assign Super Admin with all permissions (for feature tests and local seeding helpers).
     */
    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user): void {
            (new \Database\Seeders\PermissionSeeder)->run();

            $role = \App\Models\Role::query()->updateOrCreate(
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

            $role->permissions()->sync(\App\Models\Permission::query()->pluck('id'));
            $user->syncRoles([(int) $role->id]);
        });
    }
}

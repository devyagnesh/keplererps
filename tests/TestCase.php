<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create and authenticate a Super Admin user for feature tests.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function actingAsSuperAdmin(array $attributes = []): User
    {
        $user = User::factory()->superAdmin()->create($attributes);
        $this->actingAs($user);

        return $user;
    }
}

<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for admin login.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guests can view the login page.
     */
    public function test_login_page_is_displayed(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Sign In');
    }

    /**
     * Valid credentials authenticate the user.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Password1',
            'is_active' => true,
        ]);

        $this->postJson(route('admin.login.submit'), [
            'login' => 'admin@example.com',
            'password' => 'Password1',
        ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Inactive users cannot log in.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'Password1',
            'is_active' => false,
        ]);

        $this->postJson(route('admin.login.submit'), [
            'login' => 'inactive@example.com',
            'password' => 'Password1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        $this->assertGuest();
    }
}

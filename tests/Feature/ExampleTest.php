<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root URL redirects to the admin login screen.
     */
    public function test_the_application_redirects_to_admin_login(): void
    {
        $this->get('/')
            ->assertRedirect('/admin/login');
    }
}

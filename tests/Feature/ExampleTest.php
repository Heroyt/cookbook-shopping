<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_home_redirects_guests_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }

    public function test_home_redirects_authenticated_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('home'));

        $response->assertRedirect(route('dashboard'));
    }
}

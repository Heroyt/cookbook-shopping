<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class PasswordConfirmationTest extends TestCase
{
    public function test_authenticated_user_can_render_the_password_confirmation_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('auth/ConfirmPassword'));
    }

    public function test_valid_password_confirms_the_session_and_returns_to_the_intended_agent_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['url.intended' => route('agent-credentials.password-confirmation')])
            ->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertRedirect(route('agent-credentials.password-confirmation'));

        $this->assertIsInt(session('auth.password_confirmed_at'));
    }

    public function test_invalid_password_returns_czech_validation_feedback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('password.confirm'))
            ->post(route('password.confirm.store'), ['password' => 'nespravne-heslo'])
            ->assertRedirect(route('password.confirm'))
            ->assertSessionHasErrors(['password' => __('auth.password')]);
    }
}

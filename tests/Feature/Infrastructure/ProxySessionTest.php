<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ProxySessionTest extends TestCase
{
    public function test_forwarded_https_scheme_is_used_for_redirects(): void
    {
        $response = $this
            ->withHeaders([
                'X-Forwarded-Host' => 'cookbook.test',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/');

        $response->assertRedirect('https://cookbook.test/login');
    }

    public function test_expired_web_session_redirects_back_with_czech_feedback(): void
    {
        Route::post('/expired-session-test', fn () => abort(419));

        $response = $this
            ->from(route('login'))
            ->post('/expired-session-test');

        $response
            ->assertRedirect(route('login'))
            ->assertInertiaFlash('toast.type', 'warning')
            ->assertInertiaFlash(
                'toast.message',
                'Platnost relace vypršela. Obnovte stránku a zkuste to znovu.',
            );
    }
}

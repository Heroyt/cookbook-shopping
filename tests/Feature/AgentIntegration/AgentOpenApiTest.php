<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class AgentOpenApiTest extends TestCase
{
    public function test_public_versioned_documentation_routes_expose_only_the_agent_api(): void
    {
        $scramble = config('scramble');
        $scramble['renderers']['elements']['hideTryIt'] = true;
        Scramble::configure()->useConfig($scramble);

        $this->get('/docs/agent-api/v1')
            ->assertOk()
            ->assertSee('Agent API v1')
            ->assertSee('hideTryIt="true"', escape: false);

        $document = $this->getJson('/docs/agent-api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.version', '1.0.0')
            ->assertJsonPath('components.securitySchemes.http.type', 'http')
            ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
            ->json();

        $this->assertCount(1, $document['servers']);
        $this->assertStringEndsWith('/api/v1', $document['servers'][0]['url']);
        $paths = array_keys($document['paths']);
        sort($paths);
        $this->assertSame([
            '/catalog',
            '/catalog/{resourceType}/{id}',
            '/change-sets',
            '/change-sets/{changeSet}',
            '/change-sets/{changeSet}/apply',
        ], $paths);
        $this->assertArrayHasKey('AgentApiError', $document['components']['schemas']);
        $this->assertArrayHasKey('AgentChangeSetDocument', $document['components']['schemas']);

        $this->get('/docs/api')->assertNotFound();
        $this->get('/docs/api.json')->assertNotFound();
    }

    public function test_every_agent_data_route_is_protected_by_sanctum_and_an_ability(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1/'));

        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth:sanctum', $middleware, $route->uri());
            $this->assertContains('abilities:content:read', $middleware, $route->uri());
        }
    }
}

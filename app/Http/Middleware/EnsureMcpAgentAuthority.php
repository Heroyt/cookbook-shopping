<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Mcp\McpAgentFamilyContext;
use App\Mcp\Models\McpOAuthUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureMcpAgentAuthority
{
    public function __construct(private McpAgentFamilyContext $familyContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');
        if ( ! $user instanceof McpOAuthUser) {
            abort(401);
        }

        $this->familyContext->resolve($user);

        return $next($request);
    }
}

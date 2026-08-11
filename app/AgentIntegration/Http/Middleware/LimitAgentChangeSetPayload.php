<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Middleware;

use App\AgentIntegration\Http\AgentApiErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

final class LimitAgentChangeSetPayload
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $payloadBytes = strlen($request->getContent());
        $maxPayloadBytes = Config::integer('agent-integration.change_sets.max_payload_bytes');

        if ($payloadBytes > $maxPayloadBytes) {
            return AgentApiErrorResponse::make(
                'payload_limit_exceeded',
                'The Change Set JSON payload exceeds the configured byte limit.',
                413,
                details: [
                    'max_payload_bytes' => $maxPayloadBytes,
                    'payload_bytes' => $payloadBytes,
                ],
            );
        }

        return $next($request);
    }
}

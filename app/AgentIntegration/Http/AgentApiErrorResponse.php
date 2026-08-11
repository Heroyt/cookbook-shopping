<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http;

use Illuminate\Http\JsonResponse;

final class AgentApiErrorResponse
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<mixed>  $headers
     */
    public static function make(
        string $code,
        string $message,
        int $status,
        ?string $path = null,
        ?string $operationId = null,
        array $details = [],
        bool $retryable = false,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'path' => $path,
                'operation_id' => $operationId,
                'details' => $details === [] ? (object) [] : $details,
                'retryable' => $retryable,
            ],
        ], $status, $headers);
    }
}

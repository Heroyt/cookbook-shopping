<?php

declare(strict_types=1);

namespace App\AgentIntegration\Exceptions;

use RuntimeException;

final class AgentApiException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly ?string $path = null,
        public readonly ?string $operationId = null,
        public readonly array $details = [],
        public readonly bool $retryable = false,
    ) {
        parent::__construct($message);
    }
}

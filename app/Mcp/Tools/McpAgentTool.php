<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Exceptions\AgentApiException;
use App\Mcp\McpAgentFamilyContext;
use App\Mcp\Models\McpOAuthUser;
use App\Mcp\Values\McpAgentAuthority;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Throwable;

abstract class McpAgentTool extends Tool
{
    public function __construct(private readonly McpAgentFamilyContext $familyContext) {}

    protected function authority(Request $request): McpAgentAuthority
    {
        $oauthUser = $request->user('api');
        if ( ! $oauthUser instanceof McpOAuthUser) {
            throw new AuthenticationException();
        }

        return $this->familyContext->resolve($oauthUser);
    }

    protected function requireAbility(McpAgentAuthority $authority, AgentCredentialAbility $ability): void
    {
        if ( ! $authority->credential->can($ability->value)) {
            throw new AgentApiException(
                'ability_required',
                'The linked Agent Credential does not have the required ability.',
                403,
                details: ['required_abilities' => [$ability->value]],
            );
        }
    }

    protected function enforceRateLimit(McpAgentAuthority $authority, string $bucket, string $configKey): void
    {
        $key = 'mcp:' . $bucket . ':credential:' . $authority->credential->id;
        $maxAttempts = Config::integer('agent-integration.rates.' . $configKey);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new AgentApiException(
                'rate_limit_exceeded',
                'The per-credential MCP tool rate limit was exceeded.',
                429,
                details: ['retry_after_seconds' => RateLimiter::availableIn($key)],
                retryable: true,
            );
        }

        RateLimiter::hit($key, 60);
    }

    protected function stringArgument(Request $request, string $key): string
    {
        $value = $request->get($key);
        if ( ! is_string($value)) {
            throw new AgentApiException('validation_failed', "The {$key} argument must be a string.", 422);
        }

        return $value;
    }

    protected function nullableStringArgument(Request $request, string $key): ?string
    {
        $value = $request->get($key);
        if ($value === null) {
            return null;
        }
        if ( ! is_string($value)) {
            throw new AgentApiException('validation_failed', "The {$key} argument must be a string or null.", 422);
        }

        return $value;
    }

    protected function integerArgument(Request $request, string $key): int
    {
        $value = $request->get($key);
        if ( ! is_int($value)) {
            throw new AgentApiException('validation_failed', "The {$key} argument must be an integer.", 422);
        }

        return $value;
    }

    /** @return array<string, mixed> */
    protected function objectArgument(Request $request, string $key): array
    {
        $value = $request->get($key);
        if ( ! is_array($value)) {
            throw new AgentApiException('validation_failed', "The {$key} argument must be an object.", 422);
        }

        $object = [];
        foreach ($value as $field => $fieldValue) {
            if (is_string($field)) {
                $object[$field] = $fieldValue;
            }
        }

        return $object;
    }

    /** @return list<string> */
    protected function stringListArgument(Request $request, string $key): array
    {
        $value = $request->get($key);
        if ( ! is_array($value) || ! array_is_list($value)) {
            throw new AgentApiException('validation_failed', "The {$key} argument must be a list of strings.", 422);
        }

        $strings = [];
        foreach ($value as $item) {
            if ( ! is_string($item)) {
                throw new AgentApiException('validation_failed', "The {$key} argument must be a list of strings.", 422);
            }
            $strings[] = $item;
        }

        return $strings;
    }

    /** @param callable(): array<string, mixed> $operation */
    protected function respond(callable $operation): ResponseFactory
    {
        try {
            return Response::structured($operation());
        } catch (AgentApiException $exception) {
            return $this->error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->path,
                $exception->operationId,
                $exception->details,
                $exception->retryable,
            );
        } catch (ValidationException $exception) {
            return $this->error('validation_failed', 'The MCP tool arguments are invalid.', details: [
                'fields' => $exception->errors(),
            ]);
        } catch (ModelNotFoundException) {
            return $this->error('resource_not_found', 'The requested Family resource was not found.');
        } catch (AuthenticationException) {
            return $this->error('authentication_required', 'The MCP connection no longer has live authority.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'server_error',
                'The MCP tool failed before completing the operation.',
                retryable: true,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function error(
        string $code,
        string $message,
        ?string $path = null,
        ?string $operationId = null,
        array $details = [],
        bool $retryable = false,
    ): ResponseFactory {
        $error = array_filter([
            'code' => $code,
            'message' => $message,
            'path' => $path,
            'operation_id' => $operationId,
            'details' => $details,
            'retryable' => $retryable,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);

        return Response::make(Response::error((string) json_encode(
            ['error' => $error],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        )))->withStructuredContent(['error' => $error]);
    }
}

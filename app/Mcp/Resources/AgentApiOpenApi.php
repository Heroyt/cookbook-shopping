<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\Scramble;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('cookbook://agent-api/v1/openapi.json')]
#[MimeType('application/json')]
final class AgentApiOpenApi extends Resource
{
    protected string $name = 'agent_api_openapi';

    protected string $title = 'Agent API OpenAPI 3.1 Contract';

    protected string $description = 'The runtime OpenAPI 3.1 contract used by the REST Agent API and the MCP Change Set tools.';

    public function __construct(private readonly CacheableGenerator $generator) {}

    public function handle(): Response
    {
        return Response::json(($this->generator)(Scramble::configure()));
    }
}

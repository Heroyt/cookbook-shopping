<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Resources\AgentApiOpenApi;
use App\Mcp\Tools\ApplyChangeSet;
use App\Mcp\Tools\GetChangeSet;
use App\Mcp\Tools\GetChangeSetExamples;
use App\Mcp\Tools\GetFamilyResource;
use App\Mcp\Tools\ListChangeSets;
use App\Mcp\Tools\ListFamilyCatalog;
use App\Mcp\Tools\PreviewChangeSet;
use App\Mcp\Tools\RestrictConnection;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Cookbook Agent')]
#[Version('1.0.0')]
#[Instructions('Work interactively in the authorized Family. Read the catalog before proposing changes. Preview every structured mutation, explain warnings to the User, and apply only after explicit confirmation.')]
final class AgentServer extends Server
{
    protected array $tools = [
        ListFamilyCatalog::class,
        GetFamilyResource::class,
        GetChangeSetExamples::class,
        ListChangeSets::class,
        GetChangeSet::class,
        PreviewChangeSet::class,
        ApplyChangeSet::class,
        RestrictConnection::class,
    ];

    protected array $resources = [
        AgentApiOpenApi::class,
    ];

    protected array $prompts = [

    ];
}

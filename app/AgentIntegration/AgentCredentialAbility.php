<?php

declare(strict_types=1);

namespace App\AgentIntegration;

enum AgentCredentialAbility: string
{
    case ContentRead = 'content:read';
    case CookbookWrite = 'cookbook:write';
    case PlanningWrite = 'planning:write';
    case DestructiveWrite = 'destructive:write';
}

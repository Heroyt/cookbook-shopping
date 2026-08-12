<?php

declare(strict_types=1);

namespace App\AgentIntegration;

enum AgentCredentialRestrictionAction: string
{
    case ShortenExpiry = 'shorten_expiry';
    case Revoke = 'revoke';
}

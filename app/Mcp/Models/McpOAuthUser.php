<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

final class McpOAuthUser extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    protected $table = 'users';
}

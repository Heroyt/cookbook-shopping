<?php

declare(strict_types=1);

namespace App\Mcp\Actions;

use Laravel\Passport\Passport;

final readonly class RevokeMcpOAuthTokens
{
    public function handle(int $userId, string $clientId): void
    {
        $tokenIds = Passport::token()->newQuery()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->pluck('id');

        if ($tokenIds->isEmpty()) {
            return;
        }

        Passport::token()->newQuery()
            ->whereIn('id', $tokenIds)
            ->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()
            ->whereIn('access_token_id', $tokenIds)
            ->update(['revoked' => true]);
    }
}

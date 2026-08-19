<?php

declare(strict_types=1);

namespace App\Mcp;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use JsonException;

final readonly class McpConsentFamilyBinding
{
    public function issue(User $user, Family $family, string $clientId, string $authToken): string
    {
        $payload = json_encode([
            'version' => 1,
            'user_id' => $user->id,
            'family_id' => $family->id,
            'client_id' => $clientId,
            'auth_token_digest' => hash('sha256', $authToken),
        ], JSON_THROW_ON_ERROR);

        return Crypt::encryptString($payload);
    }

    public function resolve(User $user, string $clientId, string $authToken, string $binding): Family
    {
        try {
            $payload = json_decode(Crypt::decryptString($binding), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            throw $this->invalidBinding();
        }

        if ( ! is_array($payload)
            || ($payload['version'] ?? null) !== 1
            || ($payload['user_id'] ?? null) !== $user->id
            || ($payload['client_id'] ?? null) !== $clientId
            || ! is_string($payload['auth_token_digest'] ?? null)
            || ! hash_equals($payload['auth_token_digest'], hash('sha256', $authToken))
            || ! is_int($payload['family_id'] ?? null)) {
            throw $this->invalidBinding();
        }

        $familyId = $payload['family_id'];
        $family = Family::query()->find($familyId);
        $hasMembership = $family instanceof Family
            && FamilyMembership::query()
                ->where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists();

        if ( ! $hasMembership) {
            throw $this->invalidBinding();
        }

        return $family;
    }

    private function invalidBinding(): ValidationException
    {
        return ValidationException::withMessages([
            'family_binding' => __('Vybraná rodina už pro toto připojení není dostupná. Zahajte připojení znovu.'),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\AgentIntegration\Models;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Database\Factories\AgentCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property int $family_id
 * @property string $issuer_name
 * @property string $name
 * @property string $token
 * @property list<string> $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by_user_id
 * @property string|null $revocation_reason
 * @property int|null $rotated_to_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'family_id',
    'issuer_name',
    'name',
    'token',
    'abilities',
    'last_used_at',
    'expires_at',
    'revoked_at',
    'revoked_by_user_id',
    'revocation_reason',
    'rotated_to_id',
])]
final class AgentCredential extends PersonalAccessToken
{
    /** @use HasFactory<AgentCredentialFactory> */
    use HasFactory;

    protected $table = 'agent_credentials';

    /** @param mixed $token */
    public static function findToken($token): ?static
    {
        if ( ! is_string($token)) {
            return null;
        }

        $credential = parent::findToken($token);

        if ( ! $credential instanceof self || $credential->revoked_at !== null) {
            return null;
        }

        $issuerHasMembership = $credential->tokenable_type === User::class
            && FamilyMembership::query()
                ->where('family_id', $credential->family_id)
                ->where('user_id', $credential->tokenable_id)
                ->exists();

        return $issuerHasMembership ? $credential : null;
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tokenable_id');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    /** @return BelongsTo<AgentCredential, $this> */
    public function rotatedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rotated_to_id');
    }

    protected static function newFactory(): AgentCredentialFactory
    {
        return AgentCredentialFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}

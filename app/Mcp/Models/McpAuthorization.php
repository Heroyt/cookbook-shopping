<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Passport\Client;

/**
 * @property int $id
 * @property int $user_id
 * @property int $family_id
 * @property string $passport_client_id
 * @property int $agent_credential_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'family_id', 'passport_client_id', 'agent_credential_id'])]
final class McpAuthorization extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function passportClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'passport_client_id');
    }

    /** @return BelongsTo<AgentCredential, $this> */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(AgentCredential::class, 'agent_credential_id');
    }
}

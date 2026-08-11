<?php

declare(strict_types=1);

namespace App\AgentIntegration\Models;

use App\FamilyAccess\Models\Family;
use Database\Factories\AgentChangeSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $family_id
 * @property int $agent_credential_id
 * @property int|null $issuer_user_id
 * @property string $issuer_name
 * @property string $credential_name
 * @property string $client_request_id
 * @property string $status
 * @property string $digest
 * @property int $document_version
 * @property array<string, mixed> $canonical_request
 * @property array<string, mixed> $preview_document
 * @property list<string>|null $warning_acknowledgements
 * @property array<string, int>|null $identifier_mappings
 * @property array<string, mixed>|null $result_document
 * @property list<string> $resource_types
 * @property string|null $outcome
 * @property string|null $title
 * @property list<string> $source_urls
 * @property string|null $note
 * @property string|null $supersedes_id
 * @property int $payload_bytes
 * @property int $operation_count
 * @property Carbon $expires_at
 * @property Carbon|null $applied_at
 * @property Carbon|null $terminal_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'id',
    'family_id',
    'agent_credential_id',
    'issuer_user_id',
    'issuer_name',
    'credential_name',
    'client_request_id',
    'status',
    'digest',
    'document_version',
    'canonical_request',
    'preview_document',
    'warning_acknowledgements',
    'identifier_mappings',
    'result_document',
    'resource_types',
    'outcome',
    'title',
    'source_urls',
    'note',
    'supersedes_id',
    'payload_bytes',
    'operation_count',
    'expires_at',
    'applied_at',
    'terminal_at',
])]
final class AgentChangeSet extends Model
{
    /** @use HasFactory<AgentChangeSetFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<AgentCredential, $this> */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(AgentCredential::class, 'agent_credential_id');
    }

    /** @return BelongsTo<AgentChangeSet, $this> */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    protected static function newFactory(): AgentChangeSetFactory
    {
        return AgentChangeSetFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'canonical_request' => 'array',
            'preview_document' => 'array',
            'warning_acknowledgements' => 'array',
            'identifier_mappings' => 'array',
            'result_document' => 'array',
            'resource_types' => 'array',
            'source_urls' => 'array',
            'expires_at' => 'datetime',
            'applied_at' => 'datetime',
            'terminal_at' => 'datetime',
        ];
    }
}

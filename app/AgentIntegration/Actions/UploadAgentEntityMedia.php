<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Exceptions\AgentApiException;
use App\AgentIntegration\Exceptions\InvalidAgentCredentialAuthority;
use App\AgentIntegration\Media\AgentMediaResourceType;
use App\AgentIntegration\Models\AgentCredential;
use App\Cookbook\Actions\ManageEntityMedia;
use App\Cookbook\Exceptions\EntityMediaRejected;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UploadAgentEntityMedia
{
    public function __construct(
        private ManageEntityMedia $media,
        private ResolveLiveAgentCredential $liveCredential,
    ) {}

    public function handle(
        AuthorizedFamilyContext $context,
        AgentCredential $credential,
        AgentMediaResourceType $resourceType,
        int $entityId,
        UploadedFile $image,
    ): void {
        try {
            DB::transaction(function () use ($context, $credential, $resourceType, $entityId, $image): void {
                $liveCredential = $this->liveCredential->handle($context, $credential);
                if ($liveCredential->cant(AgentCredentialAbility::CookbookWrite->value)) {
                    throw new AgentApiException(
                        'ability_required',
                        'The Agent Credential lacks the ability required to upload entity media.',
                        403,
                        details: ['required_abilities' => [AgentCredentialAbility::CookbookWrite->value]],
                    );
                }

                $this->media->store($context, $resourceType->mediaType(), $entityId, $image);
            }, attempts: 3);
        } catch (EntityMediaRejected $exception) {
            throw ValidationException::withMessages([
                'image' => $exception->failure->message(),
            ]);
        } catch (InvalidAgentCredentialAuthority) {
            throw new AuthenticationException();
        }
    }
}

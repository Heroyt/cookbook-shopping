<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Exceptions\AnimatedEntityMedia;
use App\Cookbook\Exceptions\EntityMediaRejected;
use App\Cookbook\Exceptions\InvalidEntityMedia;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaFailure;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ManageEntityMedia
{
    public function __construct(
        private EntityMediaStorage $storage,
    ) {}

    public function store(AuthorizedFamilyContext $context, EntityMediaType $type, int $entityId, UploadedFile $upload): void
    {
        $entity = $this->entity($context->family, $type, $entityId, true);

        if (($entity instanceof Ingredient || $entity instanceof Recipe) && $entity->archived_at !== null) {
            throw new EntityMediaRejected(EntityMediaFailure::ArchivedEntity);
        }

        try {
            $this->storage->store($context->family, $type, $entityId, $upload);
        } catch (AnimatedEntityMedia) {
            throw new EntityMediaRejected(EntityMediaFailure::AnimatedWebp);
        } catch (InvalidEntityMedia) {
            throw new EntityMediaRejected(EntityMediaFailure::UnsafeImage);
        } catch (RuntimeException) {
            throw new EntityMediaRejected(EntityMediaFailure::StorageFailed);
        }
    }

    public function response(AuthorizedFamilyContext $context, EntityMediaType $type, int $entityId, string $variant): StreamedResponse
    {
        $this->entity($context->family, $type, $entityId, false);

        return $this->storage->response($context->family, $type, $entityId, $variant);
    }

    private function entity(Family $family, EntityMediaType $type, int $entityId, bool $lock): Model
    {
        $query = match ($type) {
            EntityMediaType::StoreLogo => Store::query(),
            EntityMediaType::StoreSectionIcon => StoreSection::query(),
            EntityMediaType::IngredientPhoto => Ingredient::query(),
            EntityMediaType::RecipeCover => Recipe::query(),
        };
        $query->whereBelongsTo($family)->whereKey($entityId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}

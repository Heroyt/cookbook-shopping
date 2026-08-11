<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Exceptions\InvalidEntityMedia;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ManageEntityMedia
{
    public function __construct(
        private CurrentFamilyScope $scope,
        private EntityMediaStorage $storage,
    ) {}

    public function store(User $user, EntityMediaType $type, int $entityId, UploadedFile $upload): void
    {
        $this->scope->within($user, function (Family $family) use ($type, $entityId, $upload): void {
            $entity = $this->entity($family, $type, $entityId, true);

            if (($entity instanceof Ingredient || $entity instanceof Recipe) && $entity->archived_at !== null) {
                throw ValidationException::withMessages([
                    'image' => __('Restore the entity before changing its image.'),
                ]);
            }

            try {
                $this->storage->store($family, $type, $entityId, $upload);
            } catch (InvalidEntityMedia) {
                throw ValidationException::withMessages([
                    'image' => __('The image could not be decoded safely.'),
                ]);
            } catch (RuntimeException) {
                throw ValidationException::withMessages([
                    'image' => __('The image could not be saved. Please try again.'),
                ]);
            }
        });
    }

    public function response(User $user, EntityMediaType $type, int $entityId, string $variant): StreamedResponse
    {
        return $this->scope->within($user, function (Family $family) use ($type, $entityId, $variant): StreamedResponse {
            $this->entity($family, $type, $entityId, false);

            return $this->storage->response($family, $type, $entityId, $variant);
        });
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

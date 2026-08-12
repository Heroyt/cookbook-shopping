<?php

declare(strict_types=1);

namespace App\Cookbook\Queries;

use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\Models\Family;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;

final readonly class CurrentFamilyRelationSearch
{
    public function __construct(private EntityMediaStorage $entityMediaStorage) {}

    /** @return array{data: list<array{id: int, name: string}>, nextCursor: string|null} */
    public function recipes(Family $family, string $search, int $limit, ?Cursor $cursor): array
    {
        return $this->page(
            Recipe::query()
                ->whereBelongsTo($family)
                ->whereNull('archived_at')
                ->select(['id', 'name', 'normalized_name'])
                ->when($search !== '', fn (Builder $query): Builder => $query->whereLike('normalized_name', "%{$search}%")),
            $limit,
            $cursor,
            static fn (Recipe $recipe): array => ['id' => $recipe->id, 'name' => $recipe->name],
        );
    }

    /** @return array{data: list<array{id: int, name: string, logoUrl: string|null}>, nextCursor: string|null} */
    public function stores(Family $family, string $search, int $limit, ?Cursor $cursor): array
    {
        return $this->page(
            Store::query()
                ->whereBelongsTo($family)
                ->select(['id', 'name', 'normalized_name'])
                ->when($search !== '', fn (Builder $query): Builder => $query->whereLike('normalized_name', "%{$search}%")),
            $limit,
            $cursor,
            fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'logoUrl' => $this->entityMediaStorage->url($family, EntityMediaType::StoreLogo, $store->id),
            ],
        );
    }

    /** @return array{data: list<array{id: int, name: string, colour: string, icon: string, iconUrl: string|null}>, nextCursor: string|null} */
    public function storeSections(Family $family, int $storeId, string $search, int $limit, ?Cursor $cursor): array
    {
        $store = Store::query()->whereBelongsTo($family)->findOrFail($storeId);

        return $this->page(
            StoreSection::query()
                ->whereBelongsTo($family)
                ->whereHas('stores', fn (Builder $query): Builder => $query->whereKey($store->id))
                ->select(['id', 'name', 'normalized_name', 'colour', 'icon'])
                ->when($search !== '', fn (Builder $query): Builder => $query->whereLike('normalized_name', "%{$search}%")),
            $limit,
            $cursor,
            fn (StoreSection $section): array => [
                'id' => $section->id,
                'name' => $section->name,
                'colour' => $section->colour,
                'icon' => $section->icon->value,
                'iconUrl' => $this->entityMediaStorage->url($family, EntityMediaType::StoreSectionIcon, $section->id),
            ],
        );
    }

    /**
     * @template TModel of Model
     * @template TItem of array<string, mixed>
     *
     * @param  Builder<TModel>  $query
     * @param  Closure(TModel): TItem  $map
     * @return array{data: list<TItem>, nextCursor: string|null}
     */
    private function page(Builder $query, int $limit, ?Cursor $cursor, Closure $map): array
    {
        $paginator = $query
            ->orderBy('normalized_name')
            ->orderBy('id')
            ->cursorPaginate($limit, ['*'], 'cursor', $cursor);

        return [
            'data' => array_values($paginator->getCollection()->map($map)->all()),
            'nextCursor' => $paginator->nextCursor()?->encode(),
        ];
    }
}

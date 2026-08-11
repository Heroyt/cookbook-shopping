<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Http\Controllers;

use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Actions\DeleteSavedShoppingList;
use App\ShoppingGeneration\Http\Requests\SavedShoppingListRequest;
use App\ShoppingGeneration\Models\SavedShoppingList;
use App\ShoppingGeneration\Queries\CurrentFamilySavedShoppingLists;
use App\ShoppingGeneration\Snapshots\SavedShoppingListPayload;
use App\ShoppingGeneration\Snapshots\SavedShoppingListV1;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use UnexpectedValueException;

/**
 * @phpstan-import-type AlternativeChoiceV1 from SavedShoppingListV1
 * @phpstan-import-type ShoppingListV1 from SavedShoppingListV1
 * @phpstan-import-type SourceV1 from SavedShoppingListV1
 *
 * @phpstan-type SnapshotSummary array{id: int, generatedAt: string, sourceKind: 'simple_plan'|'calendar', sourceLabel: string, schemaVersion: int}
 * @phpstan-type SnapshotDetailAvailable array{id: int, generatedAt: string, sourceKind: 'simple_plan'|'calendar', sourceLabel: string, schemaVersion: int, status: 'available', locale: 'cs', source: SourceV1, appliedAlternatives: list<AlternativeChoiceV1>, shoppingList: ShoppingListV1}
 * @phpstan-type SnapshotDetailUnavailable array{id: int, generatedAt: string, sourceKind: 'simple_plan'|'calendar', sourceLabel: string, schemaVersion: int, status: 'unavailable', unavailableMessage: string}
 * @phpstan-type SnapshotDetail SnapshotDetailAvailable|SnapshotDetailUnavailable
 * @phpstan-type SnapshotIndexProps array{snapshots: list<SnapshotSummary>, pagination: array{previousUrl: string|null, nextUrl: string|null}}
 */
final readonly class SavedShoppingListController
{
    public function __construct(
        private CurrentFamilyScope $scope,
        private DeleteSavedShoppingList $delete,
        private CurrentFamilySavedShoppingLists $snapshots,
        private SavedShoppingListV1 $v1,
    ) {}

    public function index(SavedShoppingListRequest $request): Response
    {
        $history = $this->scope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => $this->indexProps($family),
        );

        return Inertia::render('shopping-list-history/Index', $history);
    }

    public function show(SavedShoppingListRequest $request, int $snapshot): Response
    {
        $presentation = $this->scope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => $this->detail($this->snapshots->find($family, $snapshot)),
        );

        return Inertia::render('shopping-list-history/Show', ['snapshot' => $presentation]);
    }

    public function destroy(SavedShoppingListRequest $request, int $snapshot): RedirectResponse
    {
        $this->scope->within(
            $request->authenticatedUser(),
            function (Family $family) use ($snapshot): void {
                $this->delete->handle($family, $snapshot);
            },
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved Shopping List deleted.')]);

        return to_route('shopping-list-history.index');
    }

    /** @return SnapshotSummary */
    private function summary(SavedShoppingList $snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'generatedAt' => $this->generatedAt($snapshot),
            'sourceKind' => $snapshot->source_kind->value,
            'sourceLabel' => $snapshot->source_kind->label(),
            'schemaVersion' => $snapshot->payload_schema_version,
        ];
    }

    /** @return SnapshotDetail */
    private function detail(SavedShoppingList $snapshot): array
    {
        if ($snapshot->payload_schema_version !== SavedShoppingListPayload::SCHEMA_VERSION) {
            return $this->unavailable($snapshot, __('This Saved Shopping List version is not supported.'));
        }

        try {
            $payload = $this->v1->decode($snapshot->payload);
        } catch (UnexpectedValueException) {
            return $this->unavailable($snapshot, __('The Saved Shopping List is corrupt and cannot be displayed.'));
        }

        return [
            ...$this->summary($snapshot),
            'status' => 'available',
            ...$payload,
        ];
    }

    /** @return SnapshotDetailUnavailable */
    private function unavailable(SavedShoppingList $snapshot, string $message): array
    {
        return [
            ...$this->summary($snapshot),
            'status' => 'unavailable',
            'unavailableMessage' => $message,
        ];
    }

    private function generatedAt(SavedShoppingList $snapshot): string
    {
        return $snapshot->generated_at->format('j. n. Y H:i:s') . ',' . $snapshot->generated_at->format('u');
    }

    /** @return SnapshotIndexProps */
    private function indexProps(Family $family): array
    {
        $page = $this->snapshots->page($family);

        return [
            'snapshots' => array_values(array_map(
                fn (SavedShoppingList $snapshot): array => $this->summary($snapshot),
                $page->items(),
            )),
            'pagination' => [
                'previousUrl' => $page->previousPageUrl(),
                'nextUrl' => $page->nextPageUrl(),
            ],
        ];
    }
}

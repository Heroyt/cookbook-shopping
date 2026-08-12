<script setup lang="ts">
import { WheatIcon } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import ArchiveIngredientAlertDialog from '@/components/ingredients/ArchiveIngredientAlertDialog.vue';
import EditIngredientDialog from '@/components/ingredients/EditIngredientDialog.vue';
import RestoreIngredientButton from '@/components/ingredients/RestoreIngredientButton.vue';
import EntityImagePreview from '@/components/media/EntityImagePreview.vue';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    HoverCard,
    HoverCardContent,
    HoverCardTrigger,
} from '@/components/ui/hover-card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { IngredientAlternativeOption, IngredientSummary } from '@/types';

const props = withDefaults(
    defineProps<{
        ingredients: IngredientSummary[];
        alternativeOptions?: IngredientAlternativeOption[];
        editIngredientId?: number | null;
    }>(),
    { alternativeOptions: () => [] },
);

const selectedIngredient = ref<IngredientSummary | null>(null);
const detailQueryParameter = 'ingredient';
const detailHistoryStateKey = 'ingredientDetailModal';
const detailUrl = (ingredientId?: number): string => {
    const url = new URL(window.location.href);

    if (ingredientId === undefined) {
        url.searchParams.delete(detailQueryParameter);
    } else {
        url.searchParams.set(detailQueryParameter, String(ingredientId));
    }

    return `${url.pathname}${url.search}${url.hash}`;
};
const syncDetailFromUrl = (): void => {
    const ingredientId = Number(
        new URL(window.location.href).searchParams.get(detailQueryParameter),
    );

    selectedIngredient.value =
        props.ingredients.find(
            (ingredient) => ingredient.id === ingredientId,
        ) ?? null;
};
const quantitiesWithoutRedundantSinglePiece = (
    ingredient: IngredientSummary,
): string[] =>
    ingredient.pieceCount !== null && Number(ingredient.pieceCount) === 1
        ? ingredient.quantities.filter((quantity) => quantity !== '1 ks')
        : ingredient.quantities;
const openDetail = (ingredient: IngredientSummary): void => {
    selectedIngredient.value = ingredient;
    window.history.pushState(
        { ...window.history.state, [detailHistoryStateKey]: true },
        '',
        detailUrl(ingredient.id),
    );
};
const closeDetail = (): void => {
    if (window.history.state?.[detailHistoryStateKey] === true) {
        window.history.back();

        return;
    }

    window.history.replaceState(window.history.state, '', detailUrl());
    selectedIngredient.value = null;
};
onMounted(() => {
    syncDetailFromUrl();
    window.addEventListener('popstate', syncDetailFromUrl);
});
onBeforeUnmount(() => {
    window.removeEventListener('popstate', syncDetailFromUrl);
});
</script>

<template>
    <Empty v-if="ingredients.length === 0" class="min-h-64 border">
        <EmptyHeader>
            <EmptyMedia variant="icon"><WheatIcon /></EmptyMedia>
            <EmptyTitle>Zatím nemáte žádné suroviny</EmptyTitle>
            <EmptyDescription
                >Vytvořte první surovinu pro aktuální rodinu.</EmptyDescription
            >
        </EmptyHeader>
    </Empty>

    <Table v-else>
        <TableHeader>
            <TableRow>
                <TableHead>Název</TableHead>
                <TableHead>Obsah balení</TableHead>
                <TableHead>Umístění</TableHead>
                <TableHead>Alternativy</TableHead>
                <TableHead class="text-right">Akce</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow
                v-for="ingredient in ingredients"
                :key="ingredient.id"
                class="cursor-pointer"
                tabindex="0"
                :aria-label="`Zobrazit detail suroviny ${ingredient.name}`"
                @click="openDetail(ingredient)"
                @keydown.enter.prevent="openDetail(ingredient)"
                @keydown.space.prevent="openDetail(ingredient)"
            >
                <TableCell>
                    <div class="flex items-center gap-3">
                        <EntityImagePreview
                            v-if="ingredient.photoUrl"
                            variant="thumbnail"
                            :image-url="ingredient.photoUrl"
                            :image-alt="`Fotografie suroviny ${ingredient.name}`"
                        />
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{
                                ingredient.name
                            }}</span>
                            <Badge
                                v-if="ingredient.archived"
                                variant="secondary"
                                >Archivovaná</Badge
                            >
                        </div>
                    </div>
                </TableCell>
                <TableCell>
                    <span
                        v-if="
                            quantitiesWithoutRedundantSinglePiece(ingredient)
                                .length
                        "
                    >
                        {{
                            quantitiesWithoutRedundantSinglePiece(
                                ingredient,
                            ).join(' · ')
                        }}
                    </span>
                    <span v-else class="text-muted-foreground"
                        >Jedno balení</span
                    >
                </TableCell>
                <TableCell>{{
                    ingredient.placement || 'Bez obchodu'
                }}</TableCell>
                <TableCell>
                    <HoverCard v-if="ingredient.alternatives.length">
                        <HoverCardTrigger as-child>
                            <Badge
                                variant="outline"
                                class="hidden md:inline-flex"
                            >
                                {{ ingredient.alternatives.length }}
                            </Badge>
                        </HoverCardTrigger>
                        <HoverCardContent class="w-64">
                            <p class="mb-2 text-sm font-medium">
                                Přímé alternativy
                            </p>
                            <ul class="space-y-1 text-sm">
                                <li
                                    v-for="alternative in ingredient.alternatives"
                                    :key="alternative.id"
                                >
                                    {{ alternative.name
                                    }}<span v-if="alternative.archived">
                                        (archivovaná)</span
                                    >
                                </li>
                            </ul>
                        </HoverCardContent>
                    </HoverCard>
                    <Badge
                        v-if="ingredient.alternatives.length"
                        variant="outline"
                        class="md:hidden"
                    >
                        {{ ingredient.alternatives.length }}
                    </Badge>
                    <span v-else class="text-muted-foreground">0</span>
                </TableCell>
                <TableCell class="text-right" @click.stop @keydown.stop>
                    <div class="flex justify-end gap-2">
                        <RestoreIngredientButton
                            v-if="ingredient.archived"
                            :ingredient="ingredient"
                        />
                        <template v-else>
                            <EditIngredientDialog
                                :ingredient="ingredient"
                                :alternative-options="alternativeOptions"
                                :open-initially="
                                    editIngredientId === ingredient.id
                                "
                            />
                            <ArchiveIngredientAlertDialog
                                :ingredient="ingredient"
                            />
                        </template>
                    </div>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <Dialog
        :open="selectedIngredient !== null"
        @update:open="
            (open) => {
                if (!open) closeDetail();
            }
        "
    >
        <DialogContent
            v-if="selectedIngredient"
            class="max-h-[90vh] overflow-y-auto sm:max-w-xl"
        >
            <DialogHeader>
                <DialogTitle>{{ selectedIngredient.name }}</DialogTitle>
                <DialogDescription>{{
                    selectedIngredient.placement || 'Bez obchodu'
                }}</DialogDescription>
            </DialogHeader>
            <EntityImagePreview
                v-if="selectedIngredient.photoUrl"
                :image-url="selectedIngredient.photoUrl"
                :image-alt="`Fotografie suroviny ${selectedIngredient.name}`"
            />
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium">Obsah balení</dt>
                    <dd class="text-muted-foreground">
                        {{
                            quantitiesWithoutRedundantSinglePiece(
                                selectedIngredient,
                            ).join(' · ') || 'Jedno balení'
                        }}
                    </dd>
                </div>
                <div>
                    <dt class="font-medium">Popis</dt>
                    <dd class="text-muted-foreground">
                        {{ selectedIngredient.description || 'Bez popisu' }}
                    </dd>
                </div>
            </dl>
            <section>
                <h3 class="font-medium">Alternativy</h3>
                <ul
                    v-if="selectedIngredient.alternatives.length"
                    class="mt-2 flex flex-wrap gap-2"
                >
                    <li
                        v-for="alternative in selectedIngredient.alternatives"
                        :key="alternative.id"
                    >
                        <Badge variant="secondary">{{
                            alternative.name
                        }}</Badge>
                    </li>
                </ul>
                <p v-else class="mt-1 text-sm text-muted-foreground">
                    Bez alternativ
                </p>
            </section>
            <section>
                <h3 class="font-medium">Nutriční profil</h3>
                <p
                    v-if="selectedIngredient.nutrition"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ selectedIngredient.nutrition.energyKcal }} kcal · tuky
                    {{ selectedIngredient.nutrition.fatGrams }} g · bílkoviny
                    {{ selectedIngredient.nutrition.proteinGrams }} g ·
                    sacharidy
                    {{ selectedIngredient.nutrition.carbohydrateGrams }} g
                </p>
                <p v-else class="mt-1 text-sm text-muted-foreground">
                    Bez nutričního profilu
                </p>
            </section>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import EntityImagePreview from '@/components/media/EntityImagePreview.vue';
import EditRecipeDialog from '@/components/recipes/EditRecipeDialog.vue';
import RecipeLifecycleButton from '@/components/recipes/RecipeLifecycleButton.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
    EmptyTitle,
} from '@/components/ui/empty';
import type {
    RecipeIngredientOption,
    RecipeSummary,
    RecipeTagOption,
} from '@/types';

defineProps<{
    recipes: RecipeSummary[];
    ingredients: RecipeIngredientOption[];
    tags: RecipeTagOption[];
    editRecipeId?: number | null;
}>();

const selectedRecipe = ref<RecipeSummary | null>(null);
const format = (value: string): string =>
    new Intl.NumberFormat('cs-CZ', { maximumFractionDigits: 6 }).format(
        Number(value),
    );
const kindLabel = (kind: 'grams' | 'millilitres' | 'piece'): string =>
    ({ grams: 'g', millilitres: 'ml', piece: 'ks' })[kind];
</script>

<template>
    <Empty v-if="recipes.length === 0">
        <EmptyHeader>
            <EmptyTitle>Žádné recepty neodpovídají výběru</EmptyTitle>
            <EmptyDescription
                >Vytvořte první recept nebo změňte hledání a
                filtr.</EmptyDescription
            >
        </EmptyHeader>
    </Empty>

    <div
        v-else
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
    >
        <Card
            v-for="recipe in recipes"
            :key="recipe.id"
            class="relative overflow-hidden"
        >
            <button
                type="button"
                class="block w-full text-left outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :aria-label="`Zobrazit detail receptu ${recipe.name}`"
                @click="selectedRecipe = recipe"
            >
                <CardContent class="flex flex-col gap-3 p-3">
                    <div class="flex min-h-6 flex-wrap gap-1 pr-20">
                        <Badge
                            v-for="tag in recipe.tags"
                            :key="tag.id"
                            variant="secondary"
                            >{{ tag.name }}</Badge
                        >
                        <Badge v-if="recipe.archived" variant="outline"
                            >Archivovaný</Badge
                        >
                    </div>
                    <div
                        v-if="recipe.matchReasons.length"
                        class="flex flex-wrap gap-1"
                        aria-label="Důvody shody"
                    >
                        <Badge
                            v-for="reason in recipe.matchReasons"
                            :key="`${reason.kind}-${reason.label}`"
                            variant="outline"
                            >{{ reason.label }}</Badge
                        >
                    </div>
                    <EntityImagePreview
                        variant="card"
                        :image-url="recipe.coverUrl ?? null"
                        :image-alt="`Titulní fotografie receptu ${recipe.name}`"
                    />
                    <div>
                        <h2 class="leading-tight font-semibold">
                            {{ recipe.name }}
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ format(recipe.baseServings) }} porcí
                            <span v-if="recipe.preparationMinutes !== null">
                                · {{ recipe.preparationMinutes }} min
                                příprava</span
                            >
                            <span v-if="recipe.cookingMinutes !== null">
                                · {{ recipe.cookingMinutes }} min vaření</span
                            >
                        </p>
                    </div>
                    <p
                        v-if="recipe.nutrition.perServing"
                        class="text-xs text-muted-foreground"
                    >
                        {{ format(recipe.nutrition.perServing.energyKcal) }}
                        kcal · B
                        {{ format(recipe.nutrition.perServing.proteinGrams) }} g
                        · T {{ format(recipe.nutrition.perServing.fatGrams) }} g
                        · S
                        {{
                            format(
                                recipe.nutrition.perServing.carbohydrateGrams,
                            )
                        }}
                        g
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Nelze úplně vypočítat výživu
                    </p>
                </CardContent>
            </button>

            <div class="absolute top-3 right-3 flex gap-1" @click.stop>
                <EditRecipeDialog
                    v-if="!recipe.archived"
                    icon-only
                    :recipe="recipe"
                    :ingredients="ingredients"
                    :tags="tags"
                    :open-initially="editRecipeId === recipe.id"
                />
                <RecipeLifecycleButton icon-only :recipe="recipe" />
            </div>
        </Card>
    </div>

    <Dialog
        :open="selectedRecipe !== null"
        @update:open="
            (open) => {
                if (!open) selectedRecipe = null;
            }
        "
    >
        <DialogContent
            v-if="selectedRecipe"
            class="max-h-[90vh] overflow-y-auto sm:max-w-3xl"
        >
            <DialogHeader>
                <div class="flex flex-wrap gap-1 pr-8">
                    <Badge
                        v-for="tag in selectedRecipe.tags"
                        :key="tag.id"
                        variant="secondary"
                        >{{ tag.name }}</Badge
                    >
                </div>
                <DialogTitle>{{ selectedRecipe.name }}</DialogTitle>
                <DialogDescription>
                    {{ format(selectedRecipe.baseServings) }} porcí
                </DialogDescription>
            </DialogHeader>

            <EntityImagePreview
                :image-url="selectedRecipe.coverUrl"
                :image-alt="`Titulní fotografie receptu ${selectedRecipe.name}`"
            />
            <div class="grid gap-6 md:grid-cols-2">
                <section>
                    <h3 class="font-medium">Suroviny</h3>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm">
                        <li
                            v-for="line in selectedRecipe.ingredients"
                            :key="line.id"
                        >
                            {{ line.ingredientName }} —
                            {{ format(line.quantity) }}
                            {{ kindLabel(line.quantityKind) }}
                        </li>
                    </ol>
                </section>
                <section>
                    <h3 class="font-medium">Postup</h3>
                    <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm">
                        <li v-for="step in selectedRecipe.steps" :key="step.id">
                            {{ step.instruction }}
                        </li>
                    </ol>
                </section>
            </div>
            <section class="rounded-md bg-muted p-3 text-sm">
                <h3 class="font-medium">Výživa na porci</h3>
                <p v-if="selectedRecipe.nutrition.perServing" class="mt-1">
                    {{ format(selectedRecipe.nutrition.perServing.energyKcal) }}
                    kcal · tuky
                    {{ format(selectedRecipe.nutrition.perServing.fatGrams) }} g
                    · bílkoviny
                    {{
                        format(selectedRecipe.nutrition.perServing.proteinGrams)
                    }}
                    g · sacharidy
                    {{
                        format(
                            selectedRecipe.nutrition.perServing
                                .carbohydrateGrams,
                        )
                    }}
                    g
                </p>
                <p v-else class="mt-1">
                    Chybí profil:
                    {{
                        selectedRecipe.nutrition.missingIngredientNames.join(
                            ', ',
                        )
                    }}.
                </p>
            </section>
            <p
                v-if="selectedRecipe.notes"
                class="text-sm text-muted-foreground"
            >
                {{ selectedRecipe.notes }}
            </p>
            <a
                v-if="selectedRecipe.sourceUrl"
                :href="selectedRecipe.sourceUrl"
                target="_blank"
                rel="noreferrer"
                class="text-sm underline underline-offset-4"
                >Otevřít zdroj receptu</a
            >
        </DialogContent>
    </Dialog>
</template>

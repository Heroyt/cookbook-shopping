<script setup lang="ts">
import EntityImageUpload from '@/components/media/EntityImageUpload.vue';
import EditRecipeDialog from '@/components/recipes/EditRecipeDialog.vue';
import RecipeLifecycleButton from '@/components/recipes/RecipeLifecycleButton.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

const format = (value: string): string =>
    new Intl.NumberFormat('cs-CZ', { maximumFractionDigits: 6 }).format(
        Number(value),
    );
const kindLabel = (kind: 'grams' | 'millilitres' | 'piece'): string =>
    ({ grams: 'g', millilitres: 'ml', piece: 'ks' })[kind];
</script>

<template>
    <Empty v-if="recipes.length === 0"
        ><EmptyHeader
            ><EmptyTitle>Žádné recepty neodpovídají výběru</EmptyTitle
            ><EmptyDescription
                >Vytvořte první recept nebo změňte hledání a
                filtr.</EmptyDescription
            ></EmptyHeader
        ></Empty
    >
    <div v-else class="grid gap-4 xl:grid-cols-2">
        <Card v-for="recipe in recipes" :key="recipe.id">
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <CardTitle>{{ recipe.name }}</CardTitle
                        ><CardDescription
                            >{{ format(recipe.baseServings) }} porcí<span
                                v-if="recipe.preparationMinutes !== null"
                            >
                                · příprava
                                {{ recipe.preparationMinutes }} min</span
                            ><span v-if="recipe.cookingMinutes !== null">
                                · vaření {{ recipe.cookingMinutes }} min</span
                            ></CardDescription
                        >
                    </div>
                    <Badge v-if="recipe.archived" variant="secondary"
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
            </CardHeader>
            <CardContent class="space-y-4">
                <EntityImageUpload
                    media-type="recipe-cover"
                    :entity-id="recipe.id"
                    :image-url="recipe.coverUrl"
                    :image-alt="`Titulní fotografie receptu ${recipe.name}`"
                    :editable="!recipe.archived"
                />
                <div>
                    <h3 class="text-sm font-medium">Suroviny</h3>
                    <ol class="mt-1 list-decimal space-y-1 pl-5 text-sm">
                        <li v-for="line in recipe.ingredients" :key="line.id">
                            {{ line.ingredientName }} —
                            {{ format(line.quantity) }}
                            {{ kindLabel(line.quantityKind) }}
                        </li>
                    </ol>
                </div>
                <div v-if="recipe.steps.length">
                    <h3 class="text-sm font-medium">Postup</h3>
                    <ol class="mt-1 list-decimal space-y-1 pl-5 text-sm">
                        <li v-for="step in recipe.steps" :key="step.id">
                            {{ step.instruction }}
                        </li>
                    </ol>
                </div>
                <div v-if="recipe.tags.length" class="flex flex-wrap gap-1">
                    <Badge
                        v-for="tag in recipe.tags"
                        :key="tag.id"
                        variant="secondary"
                        >{{ tag.name }}</Badge
                    >
                </div>
                <div class="rounded-md bg-muted p-3 text-sm">
                    <p class="font-medium">Výživa na porci</p>
                    <p v-if="recipe.nutrition.perServing">
                        {{ format(recipe.nutrition.perServing.energyKcal) }}
                        kcal · tuky
                        {{ format(recipe.nutrition.perServing.fatGrams) }} g ·
                        bílkoviny
                        {{ format(recipe.nutrition.perServing.proteinGrams) }} g
                        · sacharidy
                        {{
                            format(
                                recipe.nutrition.perServing.carbohydrateGrams,
                            )
                        }}
                        g
                        <span v-if="recipe.nutrition.status === 'override'"
                            >(ruční přepis)</span
                        >
                    </p>
                    <p v-else>
                        Nelze úplně vypočítat. Chybí nutriční profil:
                        {{
                            recipe.nutrition.missingIngredientNames.join(', ')
                        }}.
                    </p>
                </div>
                <p v-if="recipe.notes" class="text-sm text-muted-foreground">
                    {{ recipe.notes }}
                </p>
                <a
                    v-if="recipe.sourceUrl"
                    :href="recipe.sourceUrl"
                    class="text-sm underline underline-offset-4"
                    target="_blank"
                    rel="noreferrer"
                    >Otevřít zdroj receptu</a
                >
            </CardContent>
            <CardFooter class="gap-2"
                ><EditRecipeDialog
                    v-if="!recipe.archived"
                    :recipe="recipe"
                    :ingredients="ingredients"
                    :open-initially="editRecipeId === recipe.id"
                    :tags="tags" /><RecipeLifecycleButton :recipe="recipe"
            /></CardFooter>
        </Card>
    </div>
</template>

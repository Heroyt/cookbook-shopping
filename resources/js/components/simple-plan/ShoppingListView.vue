<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { TriangleAlertIcon } from '@lucide/vue';
import ShoppingListLineCard from '@/components/simple-plan/ShoppingListLineCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { index as ingredientsIndex } from '@/routes/ingredients';
import { index as recipesIndex } from '@/routes/recipes';
import type { ShoppingListPresentation, ShoppingListProblem } from '@/types';

withDefaults(
    defineProps<{
        shoppingList: ShoppingListPresentation | null;
        problems: ShoppingListProblem[];
        generationSource?: 'simple-plan' | 'calendar';
        preservedSourceText?: string;
    }>(),
    {
        generationSource: 'simple-plan',
        preservedSourceText:
            'Rychlý plán zůstal zachovaný. Opravte všechny uvedené problémy a zkuste vytvoření znovu.',
    },
);
</script>

<template>
    <section
        v-if="problems.length > 0"
        aria-labelledby="calculation-problems"
        class="flex flex-col gap-4"
    >
        <Alert variant="destructive">
            <TriangleAlertIcon />
            <AlertTitle id="calculation-problems">
                Nákupní seznam nelze úplně vypočítat
            </AlertTitle>
            <AlertDescription>
                {{ preservedSourceText }}
            </AlertDescription>
        </Alert>
        <ul class="flex flex-col gap-3">
            <li
                v-for="problem in problems"
                :key="problem.problemKey"
                class="rounded-lg border border-destructive/40 p-4"
            >
                <p class="font-medium">
                    {{ problem.recipeName }} — {{ problem.ingredientName }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ problem.message }} Zadáno: {{ problem.quantityLabel }}.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link
                            :href="
                                recipesIndex({
                                    query: {
                                        edit: problem.recipeId,
                                        filter: 'all',
                                    },
                                })
                            "
                            >Zobrazit recept</Link
                        >
                    </Button>
                    <Button as-child variant="outline" size="sm">
                        <Link
                            :href="
                                ingredientsIndex({
                                    query: {
                                        edit: problem.ingredientId,
                                        filter: 'all',
                                    },
                                })
                            "
                            >Zobrazit surovinu</Link
                        >
                    </Button>
                </div>
            </li>
        </ul>
    </section>

    <section
        v-else-if="shoppingList"
        aria-label="Vygenerovaný nákupní seznam"
        class="flex flex-col gap-8"
    >
        <section
            v-for="store in shoppingList.storeGroups"
            :key="store.storeId"
            class="flex flex-col gap-5"
        >
            <h2 class="text-xl font-semibold tracking-tight">
                {{ store.storeName }}
            </h2>
            <section
                v-for="section in store.sections"
                :key="section.sectionId"
                class="flex flex-col gap-3"
            >
                <h3 class="font-medium text-muted-foreground">
                    {{ section.sectionName }}
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <ShoppingListLineCard
                        v-for="line in section.lines"
                        :key="line.ingredientId"
                        :line="line"
                        :generation-source="generationSource"
                    />
                </div>
            </section>
            <section
                v-if="store.unsectionedLines.length > 0"
                class="flex flex-col gap-3"
            >
                <h3 class="font-medium text-muted-foreground">
                    Mimo části obchodu
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <ShoppingListLineCard
                        v-for="line in store.unsectionedLines"
                        :key="line.ingredientId"
                        :line="line"
                        :generation-source="generationSource"
                    />
                </div>
            </section>
        </section>

        <section
            v-if="shoppingList.unplacedLines.length > 0"
            class="flex flex-col gap-3"
        >
            <h2 class="text-xl font-semibold tracking-tight">Bez obchodu</h2>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <ShoppingListLineCard
                    v-for="line in shoppingList.unplacedLines"
                    :key="line.ingredientId"
                    :line="line"
                    :generation-source="generationSource"
                />
            </div>
        </section>
    </section>

    <Empty v-else>
        <EmptyHeader>
            <EmptyTitle>Nákupní seznam je prázdný</EmptyTitle>
            <EmptyDescription>
                V rychlém plánu nebyly žádné vypočitatelné položky.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>
</template>

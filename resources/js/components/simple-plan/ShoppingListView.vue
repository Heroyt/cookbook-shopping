<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { TriangleAlertIcon } from '@lucide/vue';
import ShoppingListLineCard from '@/components/simple-plan/ShoppingListLineCard.vue';
import StoreSectionIcon from '@/components/stores/StoreSectionIcon.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
        readOnly?: boolean;
    }>(),
    {
        generationSource: 'simple-plan',
        preservedSourceText:
            'Rychlý plán zůstal zachovaný. Opravte všechny uvedené problémy a zkuste vytvoření znovu.',
        readOnly: false,
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
        class="flex flex-col gap-5"
    >
        <section v-for="store in shoppingList.storeGroups" :key="store.storeId">
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-3">
                        <img
                            v-if="store.storeLogoUrl"
                            :src="store.storeLogoUrl"
                            alt=""
                            class="size-8 rounded-md border object-cover"
                        />
                        {{ store.storeName }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-5">
                    <section
                        v-for="section in store.sections"
                        :key="section.sectionId"
                        class="flex flex-col gap-2 rounded-lg border-l-4 p-3"
                        :style="{
                            borderLeftColor: section.sectionColour ?? undefined,
                        }"
                    >
                        <h3 class="flex items-center gap-2 font-medium">
                            <img
                                v-if="section.sectionIconUrl"
                                :src="section.sectionIconUrl"
                                alt=""
                                class="size-6 rounded object-cover"
                            />
                            <StoreSectionIcon
                                v-else-if="section.sectionIcon"
                                :name="section.sectionIcon"
                                class="size-5"
                            />
                            {{ section.sectionName }}
                        </h3>
                        <div class="flex flex-col gap-2">
                            <ShoppingListLineCard
                                v-for="line in section.lines"
                                :key="line.ingredientId"
                                :line="line"
                                :generation-source="generationSource"
                                :read-only="readOnly"
                            />
                        </div>
                    </section>
                    <section
                        v-if="store.unsectionedLines.length"
                        class="flex flex-col gap-2 rounded-lg border-l-4 border-l-muted p-3"
                    >
                        <h3 class="font-medium text-muted-foreground">
                            Nezařazené
                        </h3>
                        <div class="flex flex-col gap-2">
                            <ShoppingListLineCard
                                v-for="line in store.unsectionedLines"
                                :key="line.ingredientId"
                                :line="line"
                                :generation-source="generationSource"
                                :read-only="readOnly"
                            />
                        </div>
                    </section>
                </CardContent>
            </Card>
        </section>

        <section v-if="shoppingList.unplacedLines.length > 0">
            <Card>
                <CardHeader class="pb-3"
                    ><CardTitle>Bez obchodu</CardTitle></CardHeader
                >
                <CardContent class="flex flex-col gap-2">
                    <ShoppingListLineCard
                        v-for="line in shoppingList.unplacedLines"
                        :key="line.ingredientId"
                        :line="line"
                        :generation-source="generationSource"
                        :read-only="readOnly"
                    />
                </CardContent>
            </Card>
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

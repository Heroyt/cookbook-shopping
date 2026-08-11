<script setup lang="ts">
import { TriangleAlertIcon } from '@lucide/vue';
import ShoppingListLineCard from '@/components/simple-plan/ShoppingListLineCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import type { ShoppingListPresentation, ShoppingListProblem } from '@/types';

defineProps<{
    shoppingList: ShoppingListPresentation | null;
    problems: ShoppingListProblem[];
}>();
</script>

<template>
    <section
        v-if="problems.length > 0"
        aria-labelledby="calculation-problems"
        class="space-y-4"
    >
        <Alert variant="destructive">
            <TriangleAlertIcon />
            <AlertTitle id="calculation-problems">
                Nákupní seznam nelze úplně vypočítat
            </AlertTitle>
            <AlertDescription>
                Rychlý plán zůstal zachovaný. Opravte všechny uvedené problémy a
                zkuste vytvoření znovu.
            </AlertDescription>
        </Alert>
        <ul class="space-y-3">
            <li
                v-for="problem in problems"
                :key="`${problem.recipeId}:${problem.ingredientId}:${problem.unit}`"
                class="rounded-lg border border-destructive/40 p-4"
            >
                <p class="font-medium">
                    {{ problem.recipeName }} — {{ problem.ingredientName }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ problem.message }} Zadáno: {{ problem.quantity }}
                    {{ problem.unit }}.
                </p>
            </li>
        </ul>
    </section>

    <section
        v-else-if="shoppingList"
        aria-label="Vygenerovaný nákupní seznam"
        class="space-y-8"
    >
        <section
            v-for="store in shoppingList.storeGroups"
            :key="store.storeId"
            class="space-y-5"
        >
            <h2 class="text-xl font-semibold tracking-tight">
                {{ store.storeName }}
            </h2>
            <section
                v-for="section in store.sections"
                :key="section.sectionId"
                class="space-y-3"
            >
                <h3 class="font-medium text-muted-foreground">
                    {{ section.sectionName }}
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <ShoppingListLineCard
                        v-for="line in section.lines"
                        :key="line.ingredientId"
                        :line="line"
                    />
                </div>
            </section>
            <section v-if="store.unsectionedLines.length > 0" class="space-y-3">
                <h3 class="font-medium text-muted-foreground">
                    Mimo části obchodu
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <ShoppingListLineCard
                        v-for="line in store.unsectionedLines"
                        :key="line.ingredientId"
                        :line="line"
                    />
                </div>
            </section>
        </section>

        <section v-if="shoppingList.unplacedLines.length > 0" class="space-y-3">
            <h2 class="text-xl font-semibold tracking-tight">Bez obchodu</h2>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <ShoppingListLineCard
                    v-for="line in shoppingList.unplacedLines"
                    :key="line.ingredientId"
                    :line="line"
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

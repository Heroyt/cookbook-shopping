<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import SaveShoppingListButton from '@/components/shopping-list-history/SaveShoppingListButton.vue';
import ShoppingListView from '@/components/simple-plan/ShoppingListView.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/calendar';
import type { ShoppingListPresentation, ShoppingListProblem } from '@/types';

defineProps<{
    selectedDates: string[];
    shoppingList: ShoppingListPresentation | null;
    problems: ShoppingListProblem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Nákupní seznam z kalendáře', href: index() }],
    },
});
</script>

<template>
    <Head title="Nákupní seznam z kalendáře" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Nákupní seznam z kalendáře
                </h1>
                <p class="text-sm text-muted-foreground">
                    Výsledek zahrnuje všechny recepty na vybraných datech bez
                    ohledu na označení jídla.
                </p>
                <div class="flex flex-wrap gap-2" aria-label="Vybraná data">
                    <Badge
                        v-for="date in selectedDates"
                        :key="date"
                        variant="secondary"
                        >{{ date }}</Badge
                    >
                </div>
            </div>
            <div class="flex flex-col items-start gap-3 sm:items-end">
                <SaveShoppingListButton
                    v-if="shoppingList && problems.length === 0"
                    source="calendar"
                />
                <Button as-child variant="outline">
                    <Link :href="index({ query: { week: selectedDates[0] } })">
                        <ArrowLeftIcon data-icon="inline-start" />
                        Zpět do kalendáře
                    </Link>
                </Button>
            </div>
        </div>
        <ShoppingListView
            :shopping-list="shoppingList"
            :problems="problems"
            generation-source="calendar"
            preserved-source-text="Výběr dat kalendáře zůstal zachovaný. Opravte všechny uvedené problémy a zkuste vytvoření znovu."
        />
    </div>
</template>

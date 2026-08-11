<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import SaveShoppingListButton from '@/components/shopping-list-history/SaveShoppingListButton.vue';
import ShoppingListView from '@/components/simple-plan/ShoppingListView.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/simple-plan';
import type { ShoppingListPresentation, ShoppingListProblem } from '@/types';

defineProps<{
    shoppingList: ShoppingListPresentation | null;
    problems: ShoppingListProblem[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Nákupní seznam', href: index() }] },
});
</script>

<template>
    <Head title="Nákupní seznam" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Nákupní seznam
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Počty balení jsou hlavní výsledek; přesná potřeba, nákup a
                    přebytek zůstávají viditelné pro kontrolu.
                </p>
            </div>
            <div class="flex flex-col items-start gap-3 sm:items-end">
                <SaveShoppingListButton
                    v-if="shoppingList && problems.length === 0"
                    source="simple-plan"
                />
                <Button as-child variant="outline">
                    <Link :href="index()">
                        <ArrowLeftIcon data-icon="inline-start" />
                        Zpět na rychlý plán
                    </Link>
                </Button>
            </div>
        </div>

        <ShoppingListView :shopping-list="shoppingList" :problems="problems" />
    </div>
</template>

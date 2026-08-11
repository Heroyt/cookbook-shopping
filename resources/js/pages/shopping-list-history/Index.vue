<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SavedShoppingListHistory from '@/components/shopping-list-history/SavedShoppingListHistory.vue';
import { index } from '@/routes/shopping-list-history';
import type {
    SavedShoppingListPagination,
    SavedShoppingListSummary,
} from '@/types';

defineProps<{
    snapshots: SavedShoppingListSummary[];
    pagination: SavedShoppingListPagination;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Historie nákupů', href: index() }] },
});
</script>

<template>
    <Head title="Historie nákupů" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1
                id="shopping-list-history-heading"
                tabindex="-1"
                class="rounded-sm text-2xl font-semibold tracking-tight focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                Historie nákupů
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Uložené seznamy jsou neměnné snímky. Pozdější úpravy receptů,
                surovin nebo obchodů jejich obsah nezmění.
            </p>
        </div>
        <SavedShoppingListHistory
            :snapshots="snapshots"
            :pagination="pagination"
        />
    </div>
</template>

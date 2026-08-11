<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import ShoppingListView from '@/components/simple-plan/ShoppingListView.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/shopping-list-history';
import type { SavedShoppingListDetail } from '@/types';

defineProps<{ snapshot: SavedShoppingListDetail }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Historie nákupů', href: index() }] },
});
</script>

<template>
    <Head :title="`Nákupní seznam z ${snapshot.generatedAt}`" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex flex-col gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Nákupní seznam z {{ snapshot.generatedAt }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Tento uložený seznam je pouze ke čtení a nepoužívá živé
                        recepty, suroviny ani obchody.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Badge>{{ snapshot.sourceLabel }}</Badge>
                    <Badge variant="secondary">
                        Verze záznamu {{ snapshot.schemaVersion }}
                    </Badge>
                </div>
                <div
                    v-if="
                        snapshot.status === 'available' &&
                        snapshot.source.kind === 'calendar'
                    "
                    class="flex flex-col gap-2"
                >
                    <h2 class="text-sm font-medium">Vybraná data</h2>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="(dateLabel, index) in snapshot.source
                                .dateLabels"
                            :key="snapshot.source.dates[index]"
                            variant="outline"
                            >{{ dateLabel }}</Badge
                        >
                    </div>
                </div>
                <div
                    v-else-if="
                        snapshot.status === 'available' &&
                        snapshot.source.kind === 'simple_plan'
                    "
                    class="flex flex-col gap-2"
                >
                    <h2 class="text-sm font-medium">Vybrané recepty</h2>
                    <ul
                        class="flex flex-col gap-1 text-sm text-muted-foreground"
                    >
                        <li
                            v-for="recipe in snapshot.source.recipes"
                            :key="recipe.recipeId"
                        >
                            {{ recipe.recipeName }} —
                            {{ recipe.servingCountLabel }}
                        </li>
                    </ul>
                </div>
            </div>
            <Button as-child variant="outline">
                <Link :href="index()">
                    <ArrowLeftIcon data-icon="inline-start" />
                    Zpět do historie
                </Link>
            </Button>
        </div>

        <Alert v-if="snapshot.status === 'unavailable'" variant="destructive">
            <AlertTitle>Uložený seznam nelze zobrazit</AlertTitle>
            <AlertDescription>
                {{ snapshot.unavailableMessage }}
            </AlertDescription>
        </Alert>

        <ShoppingListView
            v-else
            :shopping-list="snapshot.shoppingList"
            :problems="[]"
            :read-only="true"
        />
    </div>
</template>

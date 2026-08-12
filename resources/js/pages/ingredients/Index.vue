<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CreateIngredientDialog from '@/components/ingredients/CreateIngredientDialog.vue';
import IngredientList from '@/components/ingredients/IngredientList.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index } from '@/routes/ingredients';
import type { IngredientAlternativeOption, IngredientSummary } from '@/types';

defineProps<{
    ingredients: IngredientSummary[];
    alternativeOptions: IngredientAlternativeOption[];
    filter: 'active' | 'archived' | 'all';
    editIngredientId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Suroviny',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Suroviny" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Suroviny</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Spravujte konkrétní balení surovin aktuální rodiny.
                </p>
            </div>
            <CreateIngredientDialog />
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Suroviny aktuální rodiny</CardTitle>
                <CardDescription>
                    Každý člen rodiny může suroviny zobrazit a vytvářet.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    class="mb-4 flex flex-wrap gap-2"
                    aria-label="Filtr surovin"
                >
                    <Button
                        as-child
                        size="sm"
                        :variant="filter === 'active' ? 'default' : 'outline'"
                    >
                        <Link :href="index({ query: { filter: 'active' } })">
                            Aktivní
                        </Link>
                    </Button>
                    <Button
                        as-child
                        size="sm"
                        :variant="filter === 'archived' ? 'default' : 'outline'"
                    >
                        <Link :href="index({ query: { filter: 'archived' } })">
                            Archivované
                        </Link>
                    </Button>
                    <Button
                        as-child
                        size="sm"
                        :variant="filter === 'all' ? 'default' : 'outline'"
                    >
                        <Link :href="index({ query: { filter: 'all' } })">
                            Všechny
                        </Link>
                    </Button>
                </div>
                <IngredientList
                    :ingredients="ingredients"
                    :alternative-options="alternativeOptions"
                    :edit-ingredient-id="editIngredientId"
                />
            </CardContent>
        </Card>
    </div>
</template>

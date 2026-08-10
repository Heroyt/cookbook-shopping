<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CreateIngredientForm from '@/components/ingredients/CreateIngredientForm.vue';
import IngredientList from '@/components/ingredients/IngredientList.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index } from '@/routes/ingredients';
import type { IngredientPlacementStore, IngredientSummary } from '@/types';

defineProps<{
    ingredients: IngredientSummary[];
    stores: IngredientPlacementStore[];
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
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Suroviny</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Spravujte konkrétní balení surovin aktuální rodiny.
            </p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Vytvořit surovinu</CardTitle>
                    <CardDescription>
                        Zadejte název a obsah jednoho balení.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <CreateIngredientForm :stores="stores" />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Suroviny aktuální rodiny</CardTitle>
                    <CardDescription>
                        Každý člen rodiny může suroviny zobrazit a vytvářet.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <IngredientList
                        :ingredients="ingredients"
                        :stores="stores"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>

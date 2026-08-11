<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SimplePlanBuilder from '@/components/simple-plan/SimplePlanBuilder.vue';
import { index } from '@/routes/simple-plan';
import type { SimplePlanRecipeOption, SimplePlanSelection } from '@/types';

defineProps<{
    recipes: SimplePlanRecipeOption[];
    selections: SimplePlanSelection[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Rychlý plán', href: index() }] },
});
</script>

<template>
    <Head title="Rychlý plán" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Rychlý plán</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Vyberte recepty a počty porcí bez ukládání kalendářního plánu.
            </p>
        </div>
        <SimplePlanBuilder :recipes="recipes" :selections="selections" />
    </div>
</template>

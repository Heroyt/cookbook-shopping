<script setup lang="ts">
import { computed } from 'vue';
import type { DashboardOverview } from '@/types';
import DashboardOnboarding from './DashboardOnboarding.vue';
import DashboardQuickActions from './DashboardQuickActions.vue';
import DashboardStatusCards from './DashboardStatusCards.vue';
import DashboardWeekOverview from './DashboardWeekOverview.vue';

const props = defineProps<{
    overview: DashboardOverview;
}>();

const setupIncomplete = computed(
    () =>
        props.overview.setup.ingredientCount === 0 ||
        props.overview.setup.recipeCount === 0 ||
        props.overview.setup.storeCount === 0,
);
</script>

<template>
    <div class="flex flex-1 flex-col gap-8 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Přehled</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ overview.familyName }} · Co se chystá k vaření a kde navázat.
            </p>
        </div>

        <DashboardOnboarding v-if="setupIncomplete" :setup="overview.setup" />
        <DashboardStatusCards :overview="overview" />
        <DashboardWeekOverview :overview="overview" />
        <DashboardQuickActions />
    </div>
</template>

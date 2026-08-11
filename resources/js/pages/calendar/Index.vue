<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CalendarPlanner from '@/components/calendar/CalendarPlanner.vue';
import { index } from '@/routes/calendar';
import type {
    CalendarDayProjection,
    CalendarMealLabelOption,
    CalendarRecipeOption,
    CalendarWeekProjection,
} from '@/types';

defineProps<{
    week: CalendarWeekProjection;
    days: CalendarDayProjection[];
    recipes: CalendarRecipeOption[];
    mealLabels: CalendarMealLabelOption[];
    selectedDates: string[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Kalendář', href: index() }] },
});
</script>

<template>
    <Head title="Kalendář" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Kalendář</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Naplánujte recepty na konkrétní dny a vytvořte nákupní seznam z
                libovolného výběru dat.
            </p>
        </div>
        <CalendarPlanner
            :week="week"
            :days="days"
            :recipes="recipes"
            :meal-labels="mealLabels"
            :selected-dates="selectedDates"
        />
    </div>
</template>

<script setup lang="ts">
import { TriangleAlertIcon } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import type { CalendarNutritionProjection } from '@/types';

withDefaults(
    defineProps<{
        nutrition: CalendarNutritionProjection;
        label?: string;
        showSourceBadge?: boolean;
    }>(),
    {
        label: undefined,
        showSourceBadge: true,
    },
);

const format = (value: string): string => {
    const normalized = value.includes('.')
        ? value.replace(/0+$/, '').replace(/\.$/, '')
        : value;

    return normalized.replace('.', ',');
};
</script>

<template>
    <div class="flex flex-col gap-2 text-sm">
        <div
            v-if="label || (showSourceBadge && nutrition.source === 'override')"
            class="flex flex-wrap items-center gap-2"
        >
            <p v-if="label" class="font-medium">{{ label }}</p>
            <Badge
                v-if="showSourceBadge && nutrition.source === 'override'"
                variant="secondary"
            >
                Ruční přepis
            </Badge>
        </div>
        <p class="text-muted-foreground">
            {{ format(nutrition.totals.energyKcal) }} kcal ·
            {{ format(nutrition.totals.fatGrams) }} g tuků ·
            {{ format(nutrition.totals.proteinGrams) }} g bílkovin ·
            {{ format(nutrition.totals.carbohydrateGrams) }} g sacharidů
        </p>
        <Alert v-if="nutrition.status === 'incomplete'" variant="destructive">
            <TriangleAlertIcon />
            <AlertTitle>Neúplné nutriční údaje</AlertTitle>
            <AlertDescription>
                Uvedený známý součet není úplný. Chybí:
                {{ nutrition.missingIngredientNames.join(', ') }}.
            </AlertDescription>
        </Alert>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as calendarIndex } from '@/routes/calendar';
import type { DashboardOverview } from '@/types';

const props = defineProps<{
    overview: DashboardOverview;
}>();

const weekLabel = computed(
    () =>
        `${props.overview.days[0]?.dateLabel ?? ''}–${props.overview.days.at(-1)?.dateLabel ?? ''}`,
);
</script>

<template>
    <section
        class="flex flex-col gap-4"
        aria-labelledby="dashboard-week-heading"
    >
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2
                    id="dashboard-week-heading"
                    class="text-xl font-semibold tracking-tight"
                >
                    Tento týden
                </h2>
                <p class="text-sm text-muted-foreground">{{ weekLabel }}</p>
            </div>
            <Button as-child variant="ghost" size="sm">
                <Link
                    :href="
                        calendarIndex({
                            query: { week: overview.week.startsOn },
                        })
                    "
                >
                    Naplánovat týden
                    <ArrowRightIcon data-icon="inline-end" />
                </Link>
            </Button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <Card v-for="day in overview.days" :key="day.date">
                <CardHeader>
                    <div class="flex items-start justify-between gap-2">
                        <CardTitle class="capitalize">
                            {{ day.weekdayLabel }}
                        </CardTitle>
                        <Badge
                            v-if="day.date === overview.today"
                            variant="secondary"
                        >
                            Dnes
                        </Badge>
                    </div>
                    <CardDescription>{{ day.dateLabel }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <ul
                        v-if="day.entries.length > 0"
                        class="flex flex-col gap-1 text-sm"
                    >
                        <li
                            v-for="entry in day.entries.slice(0, 3)"
                            :key="entry.id"
                            class="truncate"
                        >
                            {{ entry.recipeName }}
                        </li>
                        <li
                            v-if="day.entries.length > 3"
                            class="text-muted-foreground"
                        >
                            A {{ day.entries.length - 3 }} další
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        Bez plánu
                    </p>
                </CardContent>
            </Card>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDaysIcon, ClipboardListIcon, HistoryIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as calendarIndex } from '@/routes/calendar';
import {
    index as shoppingListHistoryIndex,
    show as shoppingListShow,
} from '@/routes/shopping-list-history';
import { index as simplePlanIndex } from '@/routes/simple-plan';
import type {
    DashboardCalendarEntry,
    DashboardOverview,
    SimplePlanSelection,
} from '@/types';

const props = defineProps<{
    overview: DashboardOverview;
}>();

const todayDay = computed(() =>
    props.overview.days.find((day) => day.date === props.overview.today),
);
const todayEntries = computed<DashboardCalendarEntry[]>(
    () => todayDay.value?.entries ?? [],
);
const visibleTodayEntries = computed(() => todayEntries.value.slice(0, 3));
const hiddenTodayEntryCount = computed(() =>
    Math.max(0, todayEntries.value.length - visibleTodayEntries.value.length),
);
const visibleSelections = computed<SimplePlanSelection[]>(() =>
    props.overview.simplePlanSelections.slice(0, 3),
);
const hiddenSelectionCount = computed(() =>
    Math.max(
        0,
        props.overview.simplePlanSelections.length -
            visibleSelections.value.length,
    ),
);
const latestGeneratedAt = computed(
    () => props.overview.latestShoppingList?.generatedAt.split(',')[0] ?? null,
);

const servingLabel = (servingCount: string): string =>
    `${servingCount.replace('.', ',')} porce`;
</script>

<template>
    <section
        class="grid gap-4 md:grid-cols-3"
        aria-label="Aktuální přehled plánování"
    >
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CalendarDaysIcon />
                    Dnes vaříme
                </CardTitle>
                <CardDescription>{{ todayDay?.dateLabel }}</CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="todayEntries.length > 0" class="flex flex-col gap-3">
                    <li
                        v-for="entry in visibleTodayEntries"
                        :key="entry.id"
                        class="flex items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ entry.recipeName }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ servingLabel(entry.servingCount) }}
                            </p>
                        </div>
                        <Badge variant="secondary">{{ entry.mealLabel }}</Badge>
                    </li>
                    <li
                        v-if="hiddenTodayEntryCount > 0"
                        class="text-sm text-muted-foreground"
                    >
                        A {{ hiddenTodayEntryCount }} další
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Na dnešek zatím není naplánovaný žádný recept.
                </p>
            </CardContent>
            <CardFooter>
                <Button as-child variant="outline" size="sm">
                    <Link
                        :href="
                            calendarIndex({
                                query: { week: overview.today },
                            })
                        "
                    >
                        Otevřít kalendář
                    </Link>
                </Button>
            </CardFooter>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ClipboardListIcon />
                    Rozpracovaný rychlý plán
                </CardTitle>
                <CardDescription>
                    Vybrané recepty: {{ overview.simplePlanSelections.length }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul
                    v-if="overview.simplePlanSelections.length > 0"
                    class="flex flex-col gap-3"
                >
                    <li
                        v-for="selection in visibleSelections"
                        :key="selection.recipeId"
                        class="flex items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ selection.recipeName }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ servingLabel(selection.servingCount) }}
                            </p>
                        </div>
                        <Badge v-if="!selection.available" variant="outline">
                            Archivovaný
                        </Badge>
                    </li>
                    <li
                        v-if="hiddenSelectionCount > 0"
                        class="text-sm text-muted-foreground"
                    >
                        A {{ hiddenSelectionCount }} další
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Rychlý plán je prázdný. Vyberte recepty bez plánování
                    konkrétních dnů.
                </p>
            </CardContent>
            <CardFooter>
                <Button as-child variant="outline" size="sm">
                    <Link :href="simplePlanIndex()">
                        {{
                            overview.simplePlanSelections.length > 0
                                ? 'Pokračovat v plánu'
                                : 'Vytvořit rychlý plán'
                        }}
                    </Link>
                </Button>
            </CardFooter>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <HistoryIcon />
                    Poslední nákupní seznam
                </CardTitle>
                <CardDescription>
                    {{
                        overview.latestShoppingList?.sourceLabel ??
                        'Historie nákupů'
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="overview.latestShoppingList"
                    class="flex flex-col gap-2"
                >
                    <p class="font-medium">{{ latestGeneratedAt }}</p>
                    <Badge variant="secondary" class="w-fit">
                        {{ overview.latestShoppingList.sourceLabel }}
                    </Badge>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Zatím jste neuložili žádný vygenerovaný nákupní seznam.
                </p>
            </CardContent>
            <CardFooter>
                <Button as-child variant="outline" size="sm">
                    <Link
                        :href="
                            overview.latestShoppingList
                                ? shoppingListShow(
                                      overview.latestShoppingList.id,
                                  )
                                : shoppingListHistoryIndex()
                        "
                    >
                        {{
                            overview.latestShoppingList
                                ? 'Zobrazit seznam'
                                : 'Otevřít historii'
                        }}
                    </Link>
                </Button>
            </CardFooter>
        </Card>
    </section>
</template>

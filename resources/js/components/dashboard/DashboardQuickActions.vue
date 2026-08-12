<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarPlusIcon,
    ClipboardListIcon,
    HistoryIcon,
    NotebookTabsIcon,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as calendarIndex } from '@/routes/calendar';
import { index as recipesIndex } from '@/routes/recipes';
import { index as shoppingListHistoryIndex } from '@/routes/shopping-list-history';
import { index as simplePlanIndex } from '@/routes/simple-plan';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Rychlé akce</CardTitle>
            <CardDescription>Nejčastější další kroky</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-2 sm:grid-cols-2">
            <Button as-child variant="outline" class="justify-start">
                <Link :href="calendarIndex()">
                    <CalendarPlusIcon data-icon="inline-start" />
                    Naplánovat jídlo
                </Link>
            </Button>
            <Button as-child variant="outline" class="justify-start">
                <Link :href="simplePlanIndex()">
                    <ClipboardListIcon data-icon="inline-start" />
                    Vytvořit rychlý plán
                </Link>
            </Button>
            <Button as-child variant="outline" class="justify-start">
                <Link :href="recipesIndex()">
                    <NotebookTabsIcon data-icon="inline-start" />
                    Spravovat recepty
                </Link>
            </Button>
            <Button as-child variant="outline" class="justify-start">
                <Link :href="shoppingListHistoryIndex()">
                    <HistoryIcon data-icon="inline-start" />
                    Historie nákupů
                </Link>
            </Button>
        </CardContent>
    </Card>
</template>

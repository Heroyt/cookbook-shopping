<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BotIcon, FileClockIcon, ListChecksIcon } from '@lucide/vue';
import AgentChangeSetHistoryController from '@/actions/App/AgentIntegration/Http/Controllers/AgentChangeSetHistoryController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import type { AgentChangeSetSummary } from '@/types';

defineProps<{ changeSets: AgentChangeSetSummary[] }>();

const dateLabel = (value: string): string =>
    new Intl.DateTimeFormat('cs-CZ', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const resourceLabel = (value: string): string =>
    ({
        stores: 'Obchody',
        store_sections: 'Části obchodů',
        ingredients: 'Suroviny',
        recipe_tags: 'Štítky receptů',
        recipes: 'Recepty',
        calendar_entries: 'Kalendář',
    })[value] ?? value;
</script>

<template>
    <Empty v-if="changeSets.length === 0" class="border">
        <EmptyHeader>
            <EmptyMedia variant="icon"><FileClockIcon /></EmptyMedia>
            <EmptyTitle>Žádné použité změny</EmptyTitle>
            <EmptyDescription>
                Aktuální rodina zatím nemá historii odpovídající zvoleným
                filtrům.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>
    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card
            v-for="changeSet in changeSets"
            :key="changeSet.id"
            class="min-w-0"
        >
            <CardHeader>
                <div class="flex items-start justify-between gap-3">
                    <CardTitle class="text-base break-words">
                        {{ changeSet.title ?? 'Změna bez názvu' }}
                    </CardTitle>
                    <Badge>Použito</Badge>
                </div>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm">
                <p class="flex items-center gap-2">
                    <BotIcon class="size-4" />{{ changeSet.credentialName }}
                </p>
                <p class="text-muted-foreground">
                    Vydal/a {{ changeSet.issuerName }}
                </p>
                <p class="flex items-center gap-2">
                    <ListChecksIcon class="size-4" />{{
                        changeSet.operationCount
                    }}
                    operací
                </p>
                <p class="text-muted-foreground">
                    {{ dateLabel(changeSet.appliedAt) }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="type in changeSet.resourceTypes"
                        :key="type"
                        variant="secondary"
                    >
                        {{ resourceLabel(type) }}
                    </Badge>
                </div>
            </CardContent>
            <CardFooter>
                <Button as-child variant="outline" class="w-full">
                    <Link
                        :href="
                            AgentChangeSetHistoryController.show(changeSet.id)
                        "
                        >Zobrazit podrobnosti</Link
                    >
                </Button>
            </CardFooter>
        </Card>
    </div>
</template>

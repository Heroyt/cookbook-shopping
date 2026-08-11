<script setup lang="ts">
import { StoreIcon } from '@lucide/vue';
import EditStoreDialog from '@/components/stores/EditStoreDialog.vue';
import StoreSectionIcon from '@/components/stores/StoreSectionIcon.vue';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { StoreSectionSummary, StoreSummary } from '@/types';

defineProps<{
    stores: StoreSummary[];
    storeSections: StoreSectionSummary[];
}>();
</script>

<template>
    <Empty v-if="stores.length === 0" class="min-h-64 border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <StoreIcon />
            </EmptyMedia>
            <EmptyTitle>Zatím nemáte žádné obchody</EmptyTitle>
            <EmptyDescription>
                Vytvořte první obchod pro aktuální rodinu.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <Table v-else>
        <TableHeader>
            <TableRow>
                <TableHead>Obchod</TableHead>
                <TableHead>Části obchodu</TableHead>
                <TableHead class="text-right">Akce</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="store in stores" :key="store.id">
                <TableCell>
                    <div class="flex items-center gap-3 font-medium">
                        <img
                            v-if="store.logoUrl"
                            :src="store.logoUrl"
                            :alt="`Logo obchodu ${store.name}`"
                            class="size-10 rounded-md border object-cover"
                        />
                        <span
                            v-else
                            class="flex size-10 items-center justify-center rounded-md border bg-muted"
                            aria-hidden="true"
                        >
                            <StoreIcon class="size-5 text-muted-foreground" />
                        </span>
                        {{ store.name }}
                    </div>
                </TableCell>
                <TableCell>
                    <div
                        v-if="store.sections.length"
                        class="flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="storeSection in store.sections"
                            :key="storeSection.id"
                            variant="secondary"
                        >
                            <StoreSectionIcon
                                :name="storeSection.icon"
                                class="size-3.5"
                            />
                            <span
                                aria-hidden="true"
                                class="size-2 rounded-full border"
                                :style="{
                                    backgroundColor: storeSection.colour,
                                }"
                            />
                            {{ storeSection.name }}
                        </Badge>
                    </div>
                    <span v-else class="text-sm text-muted-foreground">
                        Bez přiřazených částí
                    </span>
                </TableCell>
                <TableCell class="text-right">
                    <EditStoreDialog
                        :store="store"
                        :store-sections="storeSections"
                    />
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>

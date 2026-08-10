<script setup lang="ts">
import { PaletteIcon } from '@lucide/vue';
import DeleteStoreSectionAlertDialog from '@/components/stores/DeleteStoreSectionAlertDialog.vue';
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
import type { StoreSectionSummary } from '@/types';

defineProps<{ storeSections: StoreSectionSummary[] }>();
</script>

<template>
    <Empty v-if="storeSections.length === 0" class="min-h-64 border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <PaletteIcon />
            </EmptyMedia>
            <EmptyTitle>Zatím nemáte žádné části obchodů</EmptyTitle>
            <EmptyDescription>
                Vytvořte první opakovaně použitelnou část pro aktuální rodinu.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <Table v-else>
        <TableHeader>
            <TableRow>
                <TableHead>Název</TableHead>
                <TableHead>Barva</TableHead>
                <TableHead class="text-right">Akce</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow
                v-for="storeSection in storeSections"
                :key="storeSection.id"
            >
                <TableCell>{{ storeSection.name }}</TableCell>
                <TableCell>
                    <div class="flex items-center gap-2">
                        <span
                            aria-hidden="true"
                            class="size-5 rounded-full border"
                            :style="{
                                backgroundColor: storeSection.colour,
                            }"
                        />
                        <span class="font-mono text-sm">
                            {{ storeSection.colour }}
                        </span>
                    </div>
                </TableCell>
                <TableCell class="text-right">
                    <DeleteStoreSectionAlertDialog
                        :store-section="storeSection"
                    />
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>

<script setup lang="ts">
import { StoreIcon } from '@lucide/vue';
import DeleteStoreAlertDialog from '@/components/stores/DeleteStoreAlertDialog.vue';
import RenameStoreDialog from '@/components/stores/RenameStoreDialog.vue';
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
import type { StoreSummary } from '@/types';

defineProps<{ stores: StoreSummary[] }>();
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
                <TableHead>Název</TableHead>
                <TableHead class="text-right">Akce</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="store in stores" :key="store.id">
                <TableCell>{{ store.name }}</TableCell>
                <TableCell class="text-right">
                    <div class="flex justify-end gap-2">
                        <RenameStoreDialog :store="store" />
                        <DeleteStoreAlertDialog :store="store" />
                    </div>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>

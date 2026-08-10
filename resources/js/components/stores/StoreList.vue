<script setup lang="ts">
import { StoreIcon } from '@lucide/vue';
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
            <EmptyTitle>No Stores yet</EmptyTitle>
            <EmptyDescription>
                Create the first Store for the Current Family.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <Table v-else>
        <TableHeader>
            <TableRow>
                <TableHead>Name</TableHead>
                <TableHead class="text-right">Actions</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="store in stores" :key="store.id">
                <TableCell>{{ store.name }}</TableCell>
                <TableCell class="text-right">
                    <RenameStoreDialog :store="store" />
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>

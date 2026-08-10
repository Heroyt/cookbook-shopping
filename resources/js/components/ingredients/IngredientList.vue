<script setup lang="ts">
import { WheatIcon } from '@lucide/vue';
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
import type { IngredientSummary } from '@/types';

defineProps<{ ingredients: IngredientSummary[] }>();
</script>

<template>
    <Empty v-if="ingredients.length === 0" class="min-h-64 border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <WheatIcon />
            </EmptyMedia>
            <EmptyTitle>Zatím nemáte žádné suroviny</EmptyTitle>
            <EmptyDescription>
                Vytvořte první surovinu pro aktuální rodinu.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <Table v-else>
        <TableHeader>
            <TableRow>
                <TableHead>Název</TableHead>
                <TableHead>Obsah balení</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="ingredient in ingredients" :key="ingredient.id">
                <TableCell>{{ ingredient.name }}</TableCell>
                <TableCell>
                    <ul class="flex flex-wrap gap-x-3 gap-y-1">
                        <li
                            v-for="quantity in ingredient.quantities"
                            :key="quantity"
                        >
                            {{ quantity }}
                        </li>
                    </ul>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { UsersRoundIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { create } from '@/routes/families';
</script>

<template>
    <Empty class="min-h-[28rem]">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <UsersRoundIcon />
            </EmptyMedia>
            <EmptyTitle>Nejprve vytvořte rodinu</EmptyTitle>
            <EmptyDescription>
                Rodina propojí vaše recepty, plánování jídel a nákupní seznamy
                na jednom místě.
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
            <Button as-child>
                <Link :href="create()">Vytvořit rodinu</Link>
            </Button>
        </EmptyContent>
    </Empty>
</template>

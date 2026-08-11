<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CreateStoreDialog from '@/components/stores/CreateStoreDialog.vue';
import CreateStoreSectionDialog from '@/components/stores/CreateStoreSectionDialog.vue';
import StoreList from '@/components/stores/StoreList.vue';
import StoreSectionList from '@/components/stores/StoreSectionList.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index } from '@/routes/stores';
import type { StoreSectionSummary, StoreSummary } from '@/types';

defineProps<{
    stores: StoreSummary[];
    storeSections: StoreSectionSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Obchody',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Obchody" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Obchody</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Spravujte obchody pro nákupy aktuální rodiny.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <CreateStoreSectionDialog />
                <CreateStoreDialog />
            </div>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Obchody aktuální rodiny</CardTitle>
                    <CardDescription>
                        Každý člen rodiny může obchody zobrazit a vytvářet.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <StoreList
                        :stores="stores"
                        :store-sections="storeSections"
                    />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Části obchodů aktuální rodiny</CardTitle>
                    <CardDescription>
                        Každý člen rodiny může části zobrazit a vytvářet.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <StoreSectionList :store-sections="storeSections" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CreateStoreForm from '@/components/stores/CreateStoreForm.vue';
import CreateStoreSectionForm from '@/components/stores/CreateStoreSectionForm.vue';
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
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Obchody</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Spravujte obchody pro nákupy aktuální rodiny.
            </p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Vytvořit obchod</CardTitle>
                    <CardDescription>
                        Přidejte místo, kde rodina nakupuje.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <CreateStoreForm />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
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
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Vytvořit část obchodu</CardTitle>
                    <CardDescription>
                        Přidejte opakovaně použitelnou část s vlastní barvou.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <CreateStoreSectionForm />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
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

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CreateStoreForm from '@/components/stores/CreateStoreForm.vue';
import StoreList from '@/components/stores/StoreList.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index } from '@/routes/stores';
import type { StoreSummary } from '@/types';

defineProps<{ stores: StoreSummary[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Stores',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Stores" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Stores</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage the places where your Current Family shops.
            </p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Create a Store</CardTitle>
                    <CardDescription>
                        Add a shopping destination to this Family.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <CreateStoreForm />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Current Family Stores</CardTitle>
                    <CardDescription>
                        Every Family member can view and create Stores.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <StoreList :stores="stores" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>

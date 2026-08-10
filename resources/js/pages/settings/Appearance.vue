<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Nastavení vzhledu',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Nastavení vzhledu" />

    <h1 class="sr-only">Nastavení vzhledu</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Nastavení vzhledu"
            description="Upravte vzhled aplikace pro svůj účet"
        />
        <AppearanceTabs />
    </div>
</template>

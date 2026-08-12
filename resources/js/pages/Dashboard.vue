<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardFamilyEmpty from '@/components/dashboard/DashboardFamilyEmpty.vue';
import DashboardOverview from '@/components/dashboard/DashboardOverview.vue';
import { dashboard } from '@/routes';
import type { DashboardOverview as DashboardOverviewData } from '@/types';

defineProps<{
    overview: DashboardOverviewData | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Přehled',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Přehled" />
    <DashboardOverview v-if="overview" :overview="overview" />
    <DashboardFamilyEmpty v-else />
</template>

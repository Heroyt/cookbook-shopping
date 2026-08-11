<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AgentChangeSetHistoryController from '@/actions/App/AgentIntegration/Http/Controllers/AgentChangeSetHistoryController';
import AgentChangeSetFilters from '@/components/agent-change-sets/AgentChangeSetFilters.vue';
import AgentChangeSetList from '@/components/agent-change-sets/AgentChangeSetList.vue';
import type {
    AgentChangeSetFilters as Filters,
    AgentChangeSetOption,
    AgentChangeSetSummary,
} from '@/types';

defineProps<{
    changeSets: AgentChangeSetSummary[];
    credentials: AgentChangeSetOption[];
    issuers: AgentChangeSetOption[];
    filters: Filters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Historie změn agentů',
                href: AgentChangeSetHistoryController.index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Historie změn agentů" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1
                id="agent-change-history-heading"
                tabindex="-1"
                class="rounded-sm text-2xl font-semibold tracking-tight focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                Historie změn agentů
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Neměnný přehled změn, které agenti použili v aktuální rodině.
            </p>
        </div>
        <AgentChangeSetFilters
            :filters="filters"
            :credentials="credentials"
            :issuers="issuers"
        />
        <AgentChangeSetList :change-sets="changeSets" />
    </div>
</template>

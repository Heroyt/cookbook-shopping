<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FilterIcon, RotateCcwIcon } from '@lucide/vue';
import { ref } from 'vue';
import AgentChangeSetHistoryController from '@/actions/App/AgentIntegration/Http/Controllers/AgentChangeSetHistoryController';
import AppDatePicker from '@/components/date/AppDatePicker.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AgentChangeSetFilters, AgentChangeSetOption } from '@/types';

const props = defineProps<{
    filters: AgentChangeSetFilters;
    credentials: AgentChangeSetOption[];
    issuers: AgentChangeSetOption[];
}>();

const credentialId = ref(props.filters.credentialId ?? 'all');
const issuerUserId = ref(props.filters.issuerUserId ?? 'all');
const dateFrom = ref(props.filters.dateFrom ?? '');
const dateTo = ref(props.filters.dateTo ?? '');
const resourceType = ref(props.filters.resourceType ?? 'all');
const outcome = ref(props.filters.outcome ?? 'all');

const applyFilters = (): void => {
    router.get(
        AgentChangeSetHistoryController.index().url,
        {
            credential_id:
                credentialId.value === 'all' ? undefined : credentialId.value,
            issuer_user_id:
                issuerUserId.value === 'all' ? undefined : issuerUserId.value,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            resource_type:
                resourceType.value === 'all' ? undefined : resourceType.value,
            outcome: outcome.value === 'all' ? undefined : outcome.value,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <form
        class="grid gap-4 rounded-lg border bg-card p-4 sm:grid-cols-2 xl:grid-cols-3"
        aria-label="Filtry historie změn agentů"
        @submit.prevent="applyFilters"
    >
        <Field>
            <FieldLabel for="agent-history-credential"
                >Přístup agenta</FieldLabel
            >
            <Select v-model="credentialId">
                <SelectTrigger id="agent-history-credential" class="w-full">
                    <SelectValue placeholder="Všechny přístupy" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Všechny přístupy</SelectItem>
                    <SelectItem
                        v-for="credential in credentials"
                        :key="credential.id"
                        :value="String(credential.id)"
                    >
                        {{ credential.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </Field>
        <Field>
            <FieldLabel for="agent-history-issuer">Vydavatel</FieldLabel>
            <Select v-model="issuerUserId">
                <SelectTrigger id="agent-history-issuer" class="w-full">
                    <SelectValue placeholder="Všichni vydavatelé" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Všichni vydavatelé</SelectItem>
                    <SelectItem
                        v-for="issuer in issuers"
                        :key="issuer.id"
                        :value="String(issuer.id)"
                    >
                        {{ issuer.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </Field>
        <Field>
            <FieldLabel for="agent-history-resource">Typ záznamu</FieldLabel>
            <Select v-model="resourceType">
                <SelectTrigger id="agent-history-resource" class="w-full">
                    <SelectValue placeholder="Všechny typy" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Všechny typy</SelectItem>
                    <SelectItem value="stores">Obchody</SelectItem>
                    <SelectItem value="store_sections"
                        >Části obchodů</SelectItem
                    >
                    <SelectItem value="ingredients">Suroviny</SelectItem>
                    <SelectItem value="recipe_tags">Štítky receptů</SelectItem>
                    <SelectItem value="recipes">Recepty</SelectItem>
                    <SelectItem value="calendar_entries">Kalendář</SelectItem>
                </SelectContent>
            </Select>
        </Field>
        <Field>
            <FieldLabel for="agent-history-date-from">Od data</FieldLabel>
            <AppDatePicker
                id="agent-history-date-from"
                v-model="dateFrom"
                clearable
            />
        </Field>
        <Field>
            <FieldLabel for="agent-history-date-to">Do data</FieldLabel>
            <AppDatePicker
                id="agent-history-date-to"
                v-model="dateTo"
                clearable
            />
        </Field>
        <Field>
            <FieldLabel for="agent-history-outcome">Výsledek</FieldLabel>
            <Select v-model="outcome">
                <SelectTrigger id="agent-history-outcome" class="w-full">
                    <SelectValue placeholder="Všechny výsledky" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Všechny výsledky</SelectItem>
                    <SelectItem value="applied">Použito</SelectItem>
                </SelectContent>
            </Select>
        </Field>
        <div class="flex flex-wrap gap-2 sm:col-span-2 xl:col-span-3">
            <Button type="submit"
                ><FilterIcon data-icon="inline-start" />Použít filtry</Button
            >
            <Button as-child type="button" variant="outline">
                <a :href="AgentChangeSetHistoryController.index().url">
                    <RotateCcwIcon data-icon="inline-start" />Vymazat filtry
                </a>
            </Button>
        </div>
    </form>
</template>

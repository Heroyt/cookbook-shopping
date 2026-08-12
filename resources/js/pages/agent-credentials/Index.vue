<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AgentCredentialController from '@/actions/App/AgentIntegration/Http/Controllers/AgentCredentialController';
import AgentCredentialList from '@/components/agent-credentials/AgentCredentialList.vue';
import AgentCredentialSecretDialog from '@/components/agent-credentials/AgentCredentialSecretDialog.vue';
import AgentInstructionsCard from '@/components/agent-credentials/AgentInstructionsCard.vue';
import CreateAgentCredentialDialog from '@/components/agent-credentials/CreateAgentCredentialDialog.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type {
    AgentConnection,
    AgentCredentialSecret,
    AgentCredentialSummary,
} from '@/types';

defineProps<{
    credentials: AgentCredentialSummary[];
    passwordConfirmed: boolean;
    agentConnection: AgentConnection;
}>();

const page = usePage();
const credentialSecret = computed(
    (): AgentCredentialSecret | undefined => page.flash.agentCredentialSecret,
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Přístupy agentů',
                href: AgentCredentialController.index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Přístupy agentů" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Přístupy agentů
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Spravujte přístupy automatizovaných pomocníků k aktuální
                    rodině.
                </p>
            </div>
            <CreateAgentCredentialDialog
                :password-confirmed="passwordConfirmed"
            />
        </div>

        <AgentInstructionsCard :connection="agentConnection" />

        <Card>
            <CardHeader>
                <CardTitle>Přístupy aktuální rodiny</CardTitle>
                <CardDescription>
                    Každý člen vidí metadata a může přístup odvolat. Nový
                    přístup nebo tajemství může vytvořit jen jeho vydavatel po
                    potvrzení hesla.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AgentCredentialList
                    :credentials="credentials"
                    :password-confirmed="passwordConfirmed"
                />
            </CardContent>
        </Card>
    </div>

    <AgentCredentialSecretDialog
        v-if="credentialSecret !== undefined"
        :key="credentialSecret.secret"
        :credential-secret="credentialSecret"
        :connection="agentConnection"
    />
</template>

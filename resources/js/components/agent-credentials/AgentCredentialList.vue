<script setup lang="ts">
import { BotIcon } from '@lucide/vue';
import RevokeAgentCredentialDialog from '@/components/agent-credentials/RevokeAgentCredentialDialog.vue';
import RotateAgentCredentialDialog from '@/components/agent-credentials/RotateAgentCredentialDialog.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import type {
    AgentCredentialAbility,
    AgentCredentialStatus,
    AgentCredentialSummary,
} from '@/types';

defineProps<{
    credentials: AgentCredentialSummary[];
    passwordConfirmed: boolean;
}>();

const statusLabels: Record<AgentCredentialStatus, string> = {
    active: 'Aktivní',
    revoked: 'Odvolaný',
    expired: 'Po platnosti',
    invalidated: 'Neplatný',
};
const abilityLabels: Record<AgentCredentialAbility, string> = {
    'content:read': 'Čtení obsahu',
    'cookbook:write': 'Úpravy kuchařky',
    'planning:write': 'Úpravy plánování',
    'destructive:write': 'Destruktivní změny',
};

const formatDate = (value: string | null): string =>
    value === null
        ? 'Nikdy'
        : new Intl.DateTimeFormat('cs-CZ', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value));
</script>

<template>
    <Empty v-if="credentials.length === 0">
        <EmptyHeader>
            <EmptyMedia variant="icon"><BotIcon /></EmptyMedia>
            <EmptyTitle>Zatím bez přístupů pro agenty</EmptyTitle>
            <EmptyDescription>
                Vytvořte první přístup s oprávněními omezenými na aktuální
                rodinu.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <div v-else class="grid gap-4 xl:grid-cols-2">
        <Card v-for="credential in credentials" :key="credential.id">
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle>{{ credential.name }}</CardTitle>
                        <CardDescription>
                            Vydal uživatel {{ credential.issuerName }}
                        </CardDescription>
                    </div>
                    <Badge
                        :variant="
                            credential.status === 'active'
                                ? 'default'
                                : credential.status === 'revoked'
                                  ? 'destructive'
                                  : 'secondary'
                        "
                    >
                        {{ statusLabels[credential.status] }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Vytvořeno</dt>
                        <dd>{{ formatDate(credential.createdAt) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Platnost do</dt>
                        <dd>{{ formatDate(credential.expiresAt) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Naposledy použito</dt>
                        <dd>{{ formatDate(credential.lastUsedAt) }}</dd>
                    </div>
                    <div v-if="credential.revokedAt !== null">
                        <dt class="text-muted-foreground">Odvoláno</dt>
                        <dd>
                            {{ formatDate(credential.revokedAt) }}
                            <template v-if="credential.revokedByName !== null">
                                uživatelem {{ credential.revokedByName }}
                            </template>
                        </dd>
                    </div>
                </dl>

                <div
                    class="flex flex-wrap gap-2"
                    aria-label="Oprávnění přístupu"
                >
                    <Badge
                        v-for="ability in credential.abilities"
                        :key="ability"
                        variant="outline"
                    >
                        {{ abilityLabels[ability] }}
                    </Badge>
                </div>

                <div
                    v-if="credential.status !== 'revoked'"
                    class="flex flex-wrap gap-2"
                >
                    <RotateAgentCredentialDialog
                        v-if="credential.isIssuer"
                        :credential="credential"
                        :password-confirmed="passwordConfirmed"
                    />
                    <RevokeAgentCredentialDialog :credential="credential" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>

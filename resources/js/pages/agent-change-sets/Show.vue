<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import AgentChangeSetHistoryController from '@/actions/App/AgentIntegration/Http/Controllers/AgentChangeSetHistoryController';
import DeleteAgentChangeSetDialog from '@/components/agent-change-sets/DeleteAgentChangeSetDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AgentChangeSetDetail } from '@/types';

const props = defineProps<{ changeSet: AgentChangeSetDetail }>();

const title = props.changeSet.title ?? 'Změna bez názvu';
const formattedJson = (value: unknown): string =>
    JSON.stringify(value, null, 2);

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
    <Head :title="title" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight break-words">
                    {{ title }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Změnu použil přístup {{ changeSet.credentialName }}, vydal/a
                    {{ changeSet.issuerName }}.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Badge>Použito</Badge>
                    <Badge variant="secondary"
                        >{{ changeSet.operationCount }} operací</Badge
                    >
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="AgentChangeSetHistoryController.index()"
                        ><ArrowLeftIcon data-icon="inline-start" />Zpět do
                        historie</Link
                    >
                </Button>
                <DeleteAgentChangeSetDialog
                    :change-set-id="changeSet.id"
                    :title="title"
                />
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle>Provozní údaje</CardTitle></CardHeader>
                <CardContent class="grid gap-3 text-sm">
                    <p>
                        <span class="font-medium"
                            >Identifikátor požadavku:</span
                        >
                        {{ changeSet.clientRequestId }}
                    </p>
                    <p class="break-all">
                        <span class="font-medium">Otisk:</span>
                        {{ changeSet.digest }}
                    </p>
                    <p v-if="changeSet.note">
                        <span class="font-medium">Poznámka:</span>
                        {{ changeSet.note }}
                    </p>
                    <div v-if="changeSet.sourceUrls.length > 0">
                        <p class="font-medium">Zdrojové odkazy</p>
                        <ul class="mt-1 list-inside list-disc break-all">
                            <li v-for="url in changeSet.sourceUrls" :key="url">
                                <a
                                    class="underline"
                                    :href="url"
                                    rel="noreferrer"
                                    target="_blank"
                                    >{{ url }}</a
                                >
                            </li>
                        </ul>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    ><CardTitle>Mapování identifikátorů</CardTitle></CardHeader
                >
                <CardContent>
                    <pre
                        class="max-h-80 overflow-auto rounded-md bg-muted p-3 text-xs"
                        aria-label="Mapování místních identifikátorů"
                        >{{ formattedJson(changeSet.identifierMappings) }}</pre>
                </CardContent>
            </Card>
        </div>
        <Card>
            <CardHeader><CardTitle>Úplný výsledek</CardTitle></CardHeader>
            <CardContent>
                <pre
                    class="max-h-[32rem] overflow-auto rounded-md bg-muted p-3 text-xs"
                    aria-label="Úplný výsledek změny"
                    >{{ formattedJson(changeSet.result) }}</pre>
            </CardContent>
        </Card>
        <Card>
            <CardHeader><CardTitle>Původní požadavek</CardTitle></CardHeader>
            <CardContent>
                <pre
                    class="max-h-[32rem] overflow-auto rounded-md bg-muted p-3 text-xs"
                    aria-label="Původní požadavek změny"
                    >{{ formattedJson(changeSet.canonicalRequest) }}</pre>
            </CardContent>
        </Card>
    </div>
</template>

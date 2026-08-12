<script setup lang="ts">
import { CheckIcon, CopyIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useClipboardCopy } from '@/composables/useClipboardCopy';
import type { AgentConnection } from '@/types';
import { createAgentBootstrapInstructions } from './agentInstructions';

const { connection } = defineProps<{
    connection: AgentConnection;
}>();
const { copy, copyState } = useClipboardCopy();

const copyInstructions = async (): Promise<void> => {
    await copy(createAgentBootstrapInstructions(connection));
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Pokyny pro připojení agenta</CardTitle>
            <CardDescription>
                Zkopírujte je, vložte do svého AI chatu a odešlete. Agent vás
                pak provede vytvořením přístupu a zeptá se na vše potřebné.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                Tyto bezpečné úvodní pokyny neobsahují žádné tajemství. Můžete
                je poslat do nového i již otevřeného chatu.
            </p>
        </CardContent>
        <CardFooter class="flex-col items-stretch gap-2 sm:items-start">
            <Button
                type="button"
                class="w-full sm:w-auto"
                @click="copyInstructions"
            >
                <CheckIcon
                    v-if="copyState === 'copied'"
                    data-icon="inline-start"
                />
                <CopyIcon v-else data-icon="inline-start" />
                {{
                    copyState === 'copied'
                        ? 'Pokyny zkopírovány'
                        : 'Kopírovat pokyny bez tajemství'
                }}
            </Button>
            <p aria-live="polite" class="text-sm text-muted-foreground">
                <template v-if="copyState === 'copied'">
                    Pokyny byly zkopírovány. Vložte je do svého AI chatu a
                    odešlete.
                </template>
                <template v-else-if="copyState === 'failed'">
                    Kopírování se nezdařilo. Zkuste to znovu nebo povolte
                    přístup ke schránce.
                </template>
            </p>
        </CardFooter>
    </Card>
</template>

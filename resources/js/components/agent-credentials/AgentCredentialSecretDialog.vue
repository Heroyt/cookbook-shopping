<script setup lang="ts">
import { CheckIcon, CopyIcon, TriangleAlertIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useTargetedClipboardCopy } from '@/composables/useClipboardCopy';
import type { AgentConnection, AgentCredentialSecret } from '@/types';
import { createCredentialAgentInstructions } from './agentInstructions';

const { connection, credentialSecret } = defineProps<{
    connection: AgentConnection;
    credentialSecret: AgentCredentialSecret;
}>();
const open = shallowRef(true);
const { copy, copyState, copyTarget } = useTargetedClipboardCopy<
    'secret' | 'instructions'
>();

const copySecret = async (): Promise<void> => {
    await copy('secret', credentialSecret.secret);
};

const copyInstructions = async (): Promise<void> => {
    await copy(
        'instructions',
        createCredentialAgentInstructions(connection, credentialSecret.secret),
    );
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Uložte nové tajemství</DialogTitle>
                <DialogDescription>
                    Tajemství pro „{{ credentialSecret.name }}“ se zobrazí pouze
                    teď.
                </DialogDescription>
            </DialogHeader>

            <Alert>
                <TriangleAlertIcon />
                <AlertTitle>Jednorázové zobrazení</AlertTitle>
                <AlertDescription>
                    Zkopírujte tajemství do bezpečného úložiště. Po zavření jej
                    aplikace už nikdy nezobrazí; ztracené tajemství musíte
                    nahradit rotací.
                </AlertDescription>
            </Alert>

            <div class="flex flex-col gap-2 sm:flex-row">
                <Input
                    :model-value="credentialSecret.secret"
                    readonly
                    aria-label="Nové tajemství přístupu agenta"
                    class="font-mono text-xs"
                />
                <Button type="button" variant="outline" @click="copySecret">
                    <CheckIcon
                        v-if="copyTarget === 'secret' && copyState === 'copied'"
                        data-icon="inline-start"
                    />
                    <CopyIcon v-else data-icon="inline-start" />
                    {{
                        copyTarget === 'secret' && copyState === 'copied'
                            ? 'Tajemství zkopírováno'
                            : 'Kopírovat tajemství'
                    }}
                </Button>
            </div>

            <div
                class="flex flex-col items-start gap-3 rounded-lg border bg-muted/40 p-4"
            >
                <p class="text-sm">
                    Vložte je do svého AI chatu a odešlete. Agent vás provede
                    dalším postupem.
                </p>
                <Button
                    type="button"
                    class="w-full sm:w-auto"
                    @click="copyInstructions"
                >
                    <CheckIcon
                        v-if="
                            copyTarget === 'instructions' &&
                            copyState === 'copied'
                        "
                        data-icon="inline-start"
                    />
                    <CopyIcon v-else data-icon="inline-start" />
                    {{
                        copyTarget === 'instructions' && copyState === 'copied'
                            ? 'Pokyny zkopírovány'
                            : 'Kopírovat pokyny s tajemstvím'
                    }}
                </Button>
            </div>

            <p aria-live="polite" class="text-sm text-muted-foreground">
                <template
                    v-if="
                        copyTarget === 'instructions' && copyState === 'copied'
                    "
                >
                    Pokyny s tajemstvím byly zkopírovány. Odešlete je pouze do
                    důvěryhodného AI chatu.
                </template>
                <template
                    v-else-if="
                        copyTarget === 'instructions' && copyState === 'failed'
                    "
                >
                    Pokyny se nepodařilo zkopírovat. Zkuste to znovu nebo
                    povolte přístup ke schránce.
                </template>
                <template
                    v-else-if="
                        copyTarget === 'secret' && copyState === 'copied'
                    "
                >
                    Tajemství bylo zkopírováno.
                </template>
                <template
                    v-else-if="
                        copyTarget === 'secret' && copyState === 'failed'
                    "
                >
                    Kopírování se nezdařilo. Označte a zkopírujte tajemství
                    ručně.
                </template>
            </p>

            <DialogFooter>
                <Button type="button" @click="open = false">
                    Mám bezpečně uloženo
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

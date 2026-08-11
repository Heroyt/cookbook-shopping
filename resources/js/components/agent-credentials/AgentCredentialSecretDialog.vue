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
import type { AgentCredentialSecret } from '@/types';

const { credentialSecret } = defineProps<{
    credentialSecret: AgentCredentialSecret;
}>();
const open = shallowRef(true);
const copyState = shallowRef<'idle' | 'copied' | 'failed'>('idle');

const copySecret = async (): Promise<void> => {
    try {
        await navigator.clipboard.writeText(credentialSecret.secret);
        copyState.value = 'copied';
    } catch {
        copyState.value = 'failed';
    }
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
                        v-if="copyState === 'copied'"
                        data-icon="inline-start"
                    />
                    <CopyIcon v-else data-icon="inline-start" />
                    {{ copyState === 'copied' ? 'Zkopírováno' : 'Kopírovat' }}
                </Button>
            </div>
            <p aria-live="polite" class="text-sm text-muted-foreground">
                <template v-if="copyState === 'copied'">
                    Tajemství bylo zkopírováno.
                </template>
                <template v-else-if="copyState === 'failed'">
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

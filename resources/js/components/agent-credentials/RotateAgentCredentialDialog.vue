<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { KeyRoundIcon, RefreshCwIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import AgentCredentialController from '@/actions/App/AgentIntegration/Http/Controllers/AgentCredentialController';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { AgentCredentialSummary } from '@/types';

const { credential, passwordConfirmed } = defineProps<{
    credential: AgentCredentialSummary;
    passwordConfirmed: boolean;
}>();
const processing = shallowRef(false);

const rotate = (): void => {
    processing.value = true;
    router.post(
        AgentCredentialController.rotate(credential.id).url,
        undefined,
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>

<template>
    <Button v-if="!passwordConfirmed" as-child variant="outline" size="sm">
        <Link :href="AgentCredentialController.confirmed()">
            <KeyRoundIcon data-icon="inline-start" /> Potvrdit heslo pro rotaci
        </Link>
    </Button>
    <AlertDialog v-else>
        <AlertDialogTrigger as-child>
            <Button type="button" variant="outline" size="sm">
                <RefreshCwIcon data-icon="inline-start" /> Nahradit tajemství
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle
                    >Nahradit tajemství přístupu „{{
                        credential.name
                    }}“?</AlertDialogTitle
                >
                <AlertDialogDescription>
                    Dosavadní tajemství přestane fungovat okamžitě. Všechny jeho
                    nepoužité náhledy změn budou zneplatněny a nové tajemství se
                    zobrazí pouze jednou.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        :disabled="processing"
                        @click="rotate"
                    >
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Nahradit tajemství
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

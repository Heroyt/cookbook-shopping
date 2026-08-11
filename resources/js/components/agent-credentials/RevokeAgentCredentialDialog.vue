<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { BanIcon } from '@lucide/vue';
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

const { credential } = defineProps<{ credential: AgentCredentialSummary }>();
const processing = shallowRef(false);

const revoke = (): void => {
    processing.value = true;
    router.delete(AgentCredentialController.destroy(credential.id).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button type="button" variant="destructive" size="sm">
                <BanIcon data-icon="inline-start" /> Odvolat
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle
                    >Odvolat přístup „{{ credential.name }}“?</AlertDialogTitle
                >
                <AlertDialogDescription>
                    Agent ztratí přístup k aktuální rodině okamžitě a všechny
                    jeho nepoužité náhledy změn budou zneplatněny. Odvolaný
                    přístup nelze obnovit, ale jeho auditní metadata zůstanou
                    uložená.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="processing"
                        @click="revoke"
                    >
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Odvolat přístup
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

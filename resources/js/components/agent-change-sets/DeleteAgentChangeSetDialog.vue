<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import AgentChangeSetHistoryController from '@/actions/App/AgentIntegration/Http/Controllers/AgentChangeSetHistoryController';
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

const props = defineProps<{ changeSetId: string; title: string }>();
const processing = shallowRef(false);

const destroyHistory = (): void => {
    processing.value = true;
    router.delete(
        AgentChangeSetHistoryController.destroy(props.changeSetId).url,
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button type="button" variant="destructive">
                <Trash2Icon data-icon="inline-start" />Smazat historii
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle
                    >Smazat historii „{{ title }}“?</AlertDialogTitle
                >
                <AlertDialogDescription>
                    Smaže se pouze auditní záznam. Recepty, suroviny, obchody
                    ani plánované položky vytvořené touto změnou se nevrátí zpět
                    a zůstanou beze změny.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="processing"
                        @click="destroyHistory"
                    >
                        <Spinner
                            v-if="processing"
                            data-icon="inline-start"
                        />Smazat pouze historii
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

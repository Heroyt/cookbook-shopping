<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import { destroy } from '@/actions/App/ShoppingGeneration/Http/Controllers/SavedShoppingListController';
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
import type { SavedShoppingListSummary } from '@/types';

const { snapshot, focusTargetId } = defineProps<{
    snapshot: SavedShoppingListSummary;
    focusTargetId: string;
}>();
const processing = shallowRef(false);

const restoreFocusAfterDelete = (): void => {
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            document.getElementById(focusTargetId)?.focus();
        });
    });
};

const deleteSnapshot = (): void => {
    processing.value = true;
    router.delete(destroy(snapshot.id).url, {
        preserveScroll: true,
        onSuccess: restoreFocusAfterDelete,
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-label="`Smazat seznam z ${snapshot.generatedAt}`"
            >
                <Trash2Icon data-icon="inline-start" />
                Smazat
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    Smazat seznam z {{ snapshot.generatedAt }}?
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Uložený seznam bude trvale odstraněn. Tato akce nepřepočítá
                    ani nevrátí žádné změny receptů, surovin nebo kalendáře.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="processing"
                        @click="deleteSnapshot"
                    >
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Smazat uložený seznam
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

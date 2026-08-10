<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreController from '@/actions/App/Cookbook/Http/Controllers/StoreController';
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
import type { StoreSummary } from '@/types';

const { store } = defineProps<{ store: StoreSummary }>();

const processing = shallowRef(false);

const deleteStore = (): void => {
    processing.value = true;

    router.delete(StoreController.destroy(store.id).url, {
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
            <Button variant="ghost" size="sm">
                <Trash2Icon data-icon="inline-start" />
                Smazat
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle
                    >Smazat obchod {{ store.name }}?</AlertDialogTitle
                >
                <AlertDialogDescription>
                    Obchod bude z aktuální rodiny trvale smazán. Tuto akci nelze
                    vrátit zpět.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="processing"
                        @click="deleteStore"
                    >
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Smazat obchod
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

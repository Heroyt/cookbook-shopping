<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
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
import type { StoreSectionSummary } from '@/types';

const { storeSection } = defineProps<{ storeSection: StoreSectionSummary }>();

const processing = shallowRef(false);

const deleteStoreSection = (): void => {
    processing.value = true;

    router.delete(StoreSectionController.destroy(storeSection.id).url, {
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
                <AlertDialogTitle>
                    Smazat část obchodu {{ storeSection.name }}?
                </AlertDialogTitle>
                <AlertDialogDescription>
                    <span class="block">
                        Přiřazení k obchodům:
                        {{ storeSection.associationCount }}.
                    </span>
                    <span class="block">
                        Umístění surovin: {{ storeSection.placementCount }}.
                    </span>
                    <span class="block">
                        Část bude trvale odebrána ze všech obchodů a jejich
                        pořadí se upraví. Tuto akci nelze vrátit zpět.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="processing"
                        @click="deleteStoreSection"
                    >
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Smazat část obchodu
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArchiveIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import IngredientController from '@/actions/App/Cookbook/Http/Controllers/IngredientController';
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
import type { IngredientSummary } from '@/types';

const { ingredient } = defineProps<{ ingredient: IngredientSummary }>();
const processing = shallowRef(false);

const archiveIngredient = (): void => {
    processing.value = true;
    router.patch(IngredientController.archive(ingredient.id).url, undefined, {
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
            <Button variant="outline" size="sm">
                <ArchiveIcon data-icon="inline-start" />
                Archivovat
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    Archivovat surovinu {{ ingredient.name }}?
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Surovina zůstane uložená, ale nebude dostupná pro nová
                    použití. Před další úpravou ji musíte obnovit.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Button
                        type="button"
                        :disabled="processing"
                        @click="archiveIngredient"
                    >
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Archivovat surovinu
                    </Button>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

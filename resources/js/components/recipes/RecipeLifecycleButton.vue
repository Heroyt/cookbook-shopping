<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArchiveIcon, ArchiveRestoreIcon } from '@lucide/vue';
import { ref } from 'vue';
import RecipeController from '@/actions/App/Cookbook/Http/Controllers/RecipeController';
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
import type { RecipeSummary } from '@/types';

const props = defineProps<{ recipe: RecipeSummary }>();
const processing = ref(false);
const changeLifecycle = (): void => {
    processing.value = true;
    const action = props.recipe.archived
        ? RecipeController.restore(props.recipe.id)
        : RecipeController.archive(props.recipe.id);
    router.patch(action.url, undefined, {
        preserveScroll: true,
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <Button
        v-if="recipe.archived"
        type="button"
        variant="outline"
        size="sm"
        :disabled="processing"
        @click="changeLifecycle"
        ><ArchiveRestoreIcon data-icon="inline-start" /> Obnovit</Button
    >
    <AlertDialog v-else>
        <AlertDialogTrigger as-child
            ><Button type="button" variant="outline" size="sm"
                ><ArchiveIcon data-icon="inline-start" /> Archivovat</Button
            ></AlertDialogTrigger
        >
        <AlertDialogContent>
            <AlertDialogHeader
                ><AlertDialogTitle
                    >Archivovat recept {{ recipe.name }}?</AlertDialogTitle
                ><AlertDialogDescription
                    >Recept zůstane uložený, ale nebude mezi aktivními recepty a
                    nepůjde upravovat, dokud jej
                    neobnovíte.</AlertDialogDescription
                ></AlertDialogHeader
            >
            <AlertDialogFooter
                ><AlertDialogCancel>Zrušit</AlertDialogCancel
                ><AlertDialogAction as-child
                    ><Button
                        type="button"
                        :disabled="processing"
                        @click="changeLifecycle"
                        >Archivovat recept</Button
                    ></AlertDialogAction
                ></AlertDialogFooter
            >
        </AlertDialogContent>
    </AlertDialog>
</template>

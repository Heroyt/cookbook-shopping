<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArchiveRestoreIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import IngredientController from '@/actions/App/Cookbook/Http/Controllers/IngredientController';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { IngredientSummary } from '@/types';

const { ingredient } = defineProps<{ ingredient: IngredientSummary }>();
const processing = shallowRef(false);

const restoreIngredient = (): void => {
    processing.value = true;
    router.patch(IngredientController.restore(ingredient.id).url, undefined, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Button
        variant="outline"
        size="sm"
        :disabled="processing"
        @click="restoreIngredient"
    >
        <Spinner v-if="processing" data-icon="inline-start" />
        <ArchiveRestoreIcon v-else data-icon="inline-start" />
        Obnovit
    </Button>
</template>

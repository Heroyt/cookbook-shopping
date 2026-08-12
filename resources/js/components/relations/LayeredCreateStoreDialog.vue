<script setup lang="ts">
import CreateStoreForm from '@/components/stores/CreateStoreForm.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { IngredientStoreOption } from '@/types';

defineProps<{ open: boolean }>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    created: [store: IngredientStoreOption];
}>();

const created = (store?: IngredientStoreOption): void => {
    if (!store) {
        return;
    }

    emit('created', store);
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Vytvořit obchod</DialogTitle>
                <DialogDescription>
                    Nový obchod se po uložení rovnou vybere v surovině.
                </DialogDescription>
            </DialogHeader>
            <CreateStoreForm layered @success="created" />
        </DialogContent>
    </Dialog>
</template>

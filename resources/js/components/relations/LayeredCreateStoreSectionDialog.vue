<script setup lang="ts">
import CreateStoreSectionForm from '@/components/stores/CreateStoreSectionForm.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { IngredientStoreSectionOption } from '@/types';

defineProps<{ open: boolean; storeId: number }>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    created: [storeSection: IngredientStoreSectionOption];
}>();

const created = (storeSection?: IngredientStoreSectionOption): void => {
    if (!storeSection) {
        return;
    }

    emit('created', storeSection);
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Vytvořit část obchodu</DialogTitle>
                <DialogDescription>
                    Nová část se připojí k vybranému obchodu a rovnou se vybere
                    v surovině.
                </DialogDescription>
            </DialogHeader>
            <CreateStoreSectionForm
                layered
                :store-id="storeId"
                @success="created"
            />
        </DialogContent>
    </Dialog>
</template>

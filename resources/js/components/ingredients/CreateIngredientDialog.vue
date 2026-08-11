<script setup lang="ts">
import { PlusIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import CreateIngredientForm from '@/components/ingredients/CreateIngredientForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { IngredientPlacementStore } from '@/types';

defineProps<{ stores: IngredientPlacementStore[] }>();

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button><PlusIcon data-icon="inline-start" /> Nová surovina</Button>
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Vytvořit surovinu</DialogTitle>
                <DialogDescription>
                    Zadejte název a obsah jednoho balení.
                </DialogDescription>
            </DialogHeader>
            <CreateIngredientForm :stores="stores" @success="open = false" />
        </DialogContent>
    </Dialog>
</template>

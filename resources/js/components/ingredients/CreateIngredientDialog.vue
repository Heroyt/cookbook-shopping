<script setup lang="ts">
import { PlusIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import CreateIngredientForm from '@/components/ingredients/CreateIngredientForm.vue';
import EntityImageUpload from '@/components/media/EntityImageUpload.vue';
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
const createdIngredient = shallowRef<{ id: number; name: string } | null>(null);
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
            <template v-if="createdIngredient">
                <p class="rounded-md bg-muted p-3 text-sm">
                    Surovina {{ createdIngredient.name }} je uložená. Obrázek se
                    nahrává samostatně a případná chyba uložení suroviny
                    nezruší.
                </p>
                <EntityImageUpload
                    media-type="ingredient-photo"
                    :entity-id="createdIngredient.id"
                    :image-url="null"
                    :image-alt="`Fotografie suroviny ${createdIngredient.name}`"
                />
                <Button type="button" @click="open = false">Hotovo</Button>
            </template>
            <CreateIngredientForm
                v-else
                :stores="stores"
                @success="createdIngredient = $event"
            />
        </DialogContent>
    </Dialog>
</template>

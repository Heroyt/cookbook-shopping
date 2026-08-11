<script setup lang="ts">
import { PlusIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import CreateRecipeForm from '@/components/recipes/CreateRecipeForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RecipeIngredientOption, RecipeTagOption } from '@/types';

defineProps<{
    ingredients: RecipeIngredientOption[];
    tags: RecipeTagOption[];
}>();

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button><PlusIcon data-icon="inline-start" /> Nový recept</Button>
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Vytvořit recept</DialogTitle>
                <DialogDescription>
                    Recept se uloží najednou jako jeden celek.
                </DialogDescription>
            </DialogHeader>
            <CreateRecipeForm
                :ingredients="ingredients"
                :tags="tags"
                @success="open = false"
            />
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PencilIcon } from '@lucide/vue';
import { ref } from 'vue';
import RecipeController from '@/actions/App/Cookbook/Http/Controllers/RecipeController';
import RecipeFormFields from '@/components/recipes/RecipeFormFields.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type {
    RecipeIngredientOption,
    RecipeSummary,
    RecipeTagOption,
} from '@/types';

defineProps<{
    recipe: RecipeSummary;
    ingredients: RecipeIngredientOption[];
    tags: RecipeTagOption[];
}>();
const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child
            ><Button variant="outline" size="sm"
                ><PencilIcon data-icon="inline-start" /> Upravit</Button
            ></DialogTrigger
        >
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
            <DialogHeader
                ><DialogTitle>Upravit recept {{ recipe.name }}</DialogTitle
                ><DialogDescription
                    >Uloží se úplná aktuální podoba receptu.</DialogDescription
                ></DialogHeader
            >
            <Form
                v-bind="RecipeController.update.form(recipe.id)"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <RecipeFormFields
                    :recipe="recipe"
                    :ingredients="ingredients"
                    :tags="tags"
                    :errors="errors"
                    :processing="processing"
                />
            </Form>
        </DialogContent>
    </Dialog>
</template>

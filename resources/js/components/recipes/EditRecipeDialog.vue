<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PencilIcon } from '@lucide/vue';
import { ref } from 'vue';
import RecipeController from '@/actions/App/Cookbook/Http/Controllers/RecipeController';
import EntityImageUpload from '@/components/media/EntityImageUpload.vue';
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
import { Separator } from '@/components/ui/separator';
import type {
    RecipeIngredientOption,
    RecipeSummary,
    RecipeTagOption,
} from '@/types';

const props = withDefaults(
    defineProps<{
        recipe: RecipeSummary;
        ingredients: RecipeIngredientOption[];
        tags: RecipeTagOption[];
        openInitially?: boolean;
        iconOnly?: boolean;
    }>(),
    { openInitially: false, iconOnly: false },
);
const open = ref(props.openInitially);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child
            ><Button
                variant="outline"
                :size="iconOnly ? 'icon-sm' : 'sm'"
                :aria-label="
                    iconOnly ? `Upravit recept ${recipe.name}` : undefined
                "
                ><PencilIcon
                    :data-icon="iconOnly ? undefined : 'inline-start'"
                />
                <span :class="iconOnly ? 'sr-only' : undefined"
                    >Upravit</span
                ></Button
            ></DialogTrigger
        >
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
            <DialogHeader
                ><DialogTitle>Upravit recept {{ recipe.name }}</DialogTitle
                ><DialogDescription
                    >Uloží se úplná aktuální podoba receptu.</DialogDescription
                ></DialogHeader
            >
            <section
                class="flex flex-col gap-3"
                :aria-labelledby="`recipe-${recipe.id}-cover`"
            >
                <h3 :id="`recipe-${recipe.id}-cover`" class="font-medium">
                    Titulní fotografie
                </h3>
                <EntityImageUpload
                    media-type="recipe-cover"
                    :entity-id="recipe.id"
                    :image-url="recipe.coverUrl"
                    :image-alt="`Titulní fotografie receptu ${recipe.name}`"
                />
            </section>
            <Separator />
            <Form
                v-bind="RecipeController.update.form(recipe.id)"
                class="flex flex-col gap-6"
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

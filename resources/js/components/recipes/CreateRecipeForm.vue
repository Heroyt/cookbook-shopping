<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Cookbook/Http/Controllers/RecipeController';
import RecipeFormFields from '@/components/recipes/RecipeFormFields.vue';
import type { RecipeIngredientOption, RecipeTagOption } from '@/types';

defineProps<{
    ingredients: RecipeIngredientOption[];
    tags: RecipeTagOption[];
}>();

const emit = defineEmits<{ success: [] }>();
</script>

<template>
    <Form
        v-bind="RecipeController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <RecipeFormFields
            :ingredients="ingredients"
            :tags="tags"
            :errors="errors"
            :processing="processing"
        />
    </Form>
</template>

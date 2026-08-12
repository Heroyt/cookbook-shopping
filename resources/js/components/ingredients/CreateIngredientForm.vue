<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import IngredientController from '@/actions/App/Cookbook/Http/Controllers/IngredientController';
import IngredientFormFields from '@/components/ingredients/IngredientFormFields.vue';

const emit = defineEmits<{
    success: [ingredient: { id: number; name: string }];
}>();
const page = usePage();
</script>

<template>
    <Form
        v-bind="IngredientController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        @success="
            page.flash.createdIngredient &&
            emit('success', page.flash.createdIngredient)
        "
    >
        <IngredientFormFields :errors="errors" :processing="processing" />
    </Form>
</template>

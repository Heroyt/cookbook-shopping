<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import IngredientController from '@/actions/App/Cookbook/Http/Controllers/IngredientController';
import IngredientFormFields from '@/components/ingredients/IngredientFormFields.vue';
import type { IngredientPlacementStore } from '@/types';

defineProps<{ stores: IngredientPlacementStore[] }>();
</script>

<template>
    <Form
        v-bind="IngredientController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <IngredientFormFields
            :errors="errors"
            :processing="processing"
            :stores="stores"
        />
    </Form>
</template>

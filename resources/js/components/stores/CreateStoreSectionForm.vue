<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
import StoreSectionFormFields from '@/components/stores/StoreSectionFormFields.vue';

const emit = defineEmits<{ success: [] }>();
</script>

<template>
    <Form
        v-bind="StoreSectionController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <StoreSectionFormFields :errors="errors" :processing="processing" />
    </Form>
</template>

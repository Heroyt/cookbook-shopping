<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
import StoreSectionFormFields from '@/components/stores/StoreSectionFormFields.vue';
</script>

<template>
    <Form
        v-bind="StoreSectionController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <StoreSectionFormFields :errors="errors" :processing="processing" />
    </Form>
</template>

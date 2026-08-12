<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
import StoreSectionFormFields from '@/components/stores/StoreSectionFormFields.vue';
import type { IngredientStoreSectionOption } from '@/types';

withDefaults(defineProps<{ layered?: boolean; storeId?: number | null }>(), {
    layered: false,
    storeId: null,
});
const emit = defineEmits<{
    success: [storeSection?: IngredientStoreSectionOption];
}>();
const page = usePage();
</script>

<template>
    <Form
        v-bind="StoreSectionController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        @success="emit('success', page.flash.createdStoreSection)"
    >
        <input v-if="layered" type="hidden" name="layered" value="1" />
        <input
            v-if="storeId !== null"
            type="hidden"
            name="store_id"
            :value="storeId"
        />
        <StoreSectionFormFields :errors="errors" :processing="processing" />
    </Form>
</template>

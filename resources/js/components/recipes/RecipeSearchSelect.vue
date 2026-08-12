<script setup lang="ts">
import RelationSearchSelect from '@/components/relations/RelationSearchSelect.vue';
import { recipes } from '@/routes/relation-search';
import type { CalendarRecipeOption } from '@/types';

withDefaults(
    defineProps<{
        id: string;
        modelValue: string;
        initialOptions?: CalendarRecipeOption[];
        name?: string;
        invalid?: boolean;
    }>(),
    { initialOptions: () => [] },
);
const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <RelationSearchSelect
        :id="id"
        :model-value="modelValue"
        :endpoint="recipes().url"
        :initial-options="initialOptions"
        :name="name"
        :invalid="invalid"
        placeholder="Hledat a vybrat recept"
        search-placeholder="Hledat recept…"
        empty-label="Žádný recept nebyl nalezen."
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>

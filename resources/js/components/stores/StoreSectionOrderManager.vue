<script setup lang="ts">
import { computed } from 'vue';
import AttachStoreSectionForm from '@/components/stores/AttachStoreSectionForm.vue';
import StoreSectionOrderList from '@/components/stores/StoreSectionOrderList.vue';
import type { StoreSectionSummary, StoreSummary } from '@/types';

const props = defineProps<{
    store: StoreSummary;
    storeSections: StoreSectionSummary[];
}>();

const availableSections = computed(() => {
    const associatedSectionIds = new Set(
        props.store.sections.map((storeSection) => storeSection.id),
    );

    return props.storeSections.filter(
        (storeSection) => !associatedSectionIds.has(storeSection.id),
    );
});
</script>

<template>
    <div class="flex min-w-72 flex-col gap-4">
        <AttachStoreSectionForm
            :store-id="store.id"
            :available-sections="availableSections"
        />
        <StoreSectionOrderList :store="store" />
    </div>
</template>

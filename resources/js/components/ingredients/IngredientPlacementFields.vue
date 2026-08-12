<script setup lang="ts">
import { computed, defineAsyncComponent, shallowRef, watch } from 'vue';
import RelationSearchSelect from '@/components/relations/RelationSearchSelect.vue';
import StoreSectionIcon from '@/components/stores/StoreSectionIcon.vue';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { storeSections, stores } from '@/routes/relation-search';
import type {
    IngredientStoreOption,
    IngredientStoreSectionOption,
    IngredientSummary,
} from '@/types';

const props = withDefaults(
    defineProps<{
        ingredient?: IngredientSummary | null;
        idPrefix?: string;
        storeError?: string;
        storeSectionError?: string;
    }>(),
    { ingredient: null, idPrefix: 'ingredient-create' },
);

const LayeredCreateStoreDialog = defineAsyncComponent({
    loader: () => import('@/components/relations/LayeredCreateStoreDialog.vue'),
    delay: 200,
    timeout: 30_000,
});
const LayeredCreateStoreSectionDialog = defineAsyncComponent({
    loader: () =>
        import('@/components/relations/LayeredCreateStoreSectionDialog.vue'),
    delay: 200,
    timeout: 30_000,
});

const storeSelection = shallowRef(
    props.ingredient?.storeId ? String(props.ingredient.storeId) : '',
);
const sectionSelection = shallowRef(
    props.ingredient?.storeSectionId
        ? String(props.ingredient.storeSectionId)
        : '',
);
const createdStores = shallowRef<IngredientStoreOption[]>([]);
const createdSections = shallowRef<IngredientStoreSectionOption[]>([]);
const createStoreOpen = shallowRef(false);
const createSectionOpen = shallowRef(false);
const initialStores = computed<IngredientStoreOption[]>(() => [
    ...(props.ingredient?.store ? [props.ingredient.store] : []),
    ...createdStores.value,
]);
const initialSections = computed<IngredientStoreSectionOption[]>(() => [
    ...(props.ingredient?.storeSection ? [props.ingredient.storeSection] : []),
    ...createdSections.value,
]);
const storeSectionEndpoint = computed(
    () =>
        storeSections({
            query: { store_id: Number(storeSelection.value || 0) },
        }).url,
);

watch(storeSelection, () => {
    sectionSelection.value = '';
    createdSections.value = [];
});

const selectCreatedStore = (store: IngredientStoreOption): void => {
    createdStores.value = [store, ...createdStores.value];
    storeSelection.value = String(store.id);
};

const selectCreatedSection = (
    storeSection: IngredientStoreSectionOption,
): void => {
    createdSections.value = [storeSection, ...createdSections.value];
    sectionSelection.value = String(storeSection.id);
};
</script>

<template>
    <FieldSet>
        <FieldLegend variant="legend">Umístění v obchodě</FieldLegend>
        <FieldDescription>
            Umístění slouží pouze pro budoucí seskupení nákupního seznamu.
            Nevyjadřuje dostupnost ani skladovou zásobu.
        </FieldDescription>

        <Field :data-invalid="Boolean(storeError)">
            <FieldLabel :for="`${idPrefix}-store`">Obchod</FieldLabel>
            <RelationSearchSelect
                :id="`${idPrefix}-store`"
                v-model="storeSelection"
                name="store_id"
                :endpoint="stores().url"
                :initial-options="initialStores"
                :invalid="Boolean(storeError)"
                placeholder="Bez obchodu"
                search-placeholder="Hledat obchod…"
                empty-label="Žádný obchod nebyl nalezen."
                clear-label="Bez obchodu"
                create-label="Vytvořit nový obchod"
                @create="createStoreOpen = true"
            >
                <template #selected="{ option }">
                    <span class="flex min-w-0 items-center gap-2 truncate">
                        <img
                            v-if="option?.logoUrl"
                            :src="option.logoUrl"
                            alt=""
                            class="size-5 rounded object-cover"
                        />
                        {{ option?.name ?? 'Bez obchodu' }}
                    </span>
                </template>
                <template #option="{ option }">
                    <span class="flex min-w-0 items-center gap-2 truncate">
                        <img
                            v-if="option.logoUrl"
                            :src="option.logoUrl"
                            alt=""
                            class="size-5 rounded object-cover"
                        />
                        {{ option.name }}
                    </span>
                </template>
            </RelationSearchSelect>
            <FieldError :errors="[storeError]" />
        </Field>

        <Field :data-invalid="Boolean(storeSectionError)">
            <FieldLabel :for="`${idPrefix}-store-section`">
                Část obchodu
            </FieldLabel>
            <RelationSearchSelect
                :id="`${idPrefix}-store-section`"
                v-model="sectionSelection"
                name="store_section_id"
                :endpoint="storeSectionEndpoint"
                :initial-options="initialSections"
                :invalid="Boolean(storeSectionError)"
                :disabled="storeSelection === ''"
                placeholder="Bez části obchodu"
                search-placeholder="Hledat část obchodu…"
                empty-label="Žádná část obchodu nebyla nalezena."
                clear-label="Bez části obchodu"
                create-label="Vytvořit novou část obchodu"
                @create="createSectionOpen = true"
            >
                <template #selected="{ option }">
                    <span class="flex min-w-0 items-center gap-2 truncate">
                        <span
                            v-if="option"
                            class="size-2.5 shrink-0 rounded-full border"
                            :style="{ backgroundColor: option.colour }"
                        />
                        <img
                            v-if="option?.iconUrl"
                            :src="option.iconUrl"
                            alt=""
                            class="size-5 rounded object-cover"
                        />
                        <StoreSectionIcon
                            v-else-if="option?.icon"
                            :name="option.icon"
                            class="size-4"
                        />
                        {{ option?.name ?? 'Bez části obchodu' }}
                    </span>
                </template>
                <template #option="{ option }">
                    <span class="flex min-w-0 items-center gap-2 truncate">
                        <span
                            class="size-2.5 shrink-0 rounded-full border"
                            :style="{ backgroundColor: option.colour }"
                        />
                        <img
                            v-if="option.iconUrl"
                            :src="option.iconUrl"
                            alt=""
                            class="size-5 rounded object-cover"
                        />
                        <StoreSectionIcon
                            v-else
                            :name="option.icon"
                            class="size-4"
                        />
                        {{ option.name }}
                    </span>
                </template>
            </RelationSearchSelect>
            <FieldError :errors="[storeSectionError]" />
        </Field>
    </FieldSet>

    <LayeredCreateStoreDialog
        v-if="createStoreOpen"
        v-model:open="createStoreOpen"
        @created="selectCreatedStore"
    />
    <LayeredCreateStoreSectionDialog
        v-if="createSectionOpen && storeSelection !== ''"
        v-model:open="createSectionOpen"
        :store-id="Number(storeSelection)"
        @created="selectCreatedSection"
    />
</template>

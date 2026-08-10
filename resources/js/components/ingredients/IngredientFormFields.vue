<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { IngredientPlacementStore, IngredientSummary } from '@/types';

type IngredientField =
    | 'name'
    | 'metric_quantity'
    | 'metric_unit'
    | 'piece_count'
    | 'description'
    | 'store_id'
    | 'store_section_id'
    | 'quantities';

const props = withDefaults(
    defineProps<{
        errors?: Partial<Record<IngredientField, string>>;
        processing?: boolean;
        ingredient?: IngredientSummary | null;
        stores?: IngredientPlacementStore[];
        idPrefix?: string;
        submitLabel?: string;
        showSubmit?: boolean;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        ingredient: null,
        stores: () => [],
        idPrefix: 'ingredient-create',
        submitLabel: 'Vytvořit surovinu',
        showSubmit: true,
    },
);

const storeSelection = ref(
    props.ingredient?.storeId === null ||
        props.ingredient?.storeId === undefined
        ? 'none'
        : String(props.ingredient.storeId),
);
const sectionSelection = ref(
    props.ingredient?.storeSectionId === null ||
        props.ingredient?.storeSectionId === undefined
        ? 'none'
        : String(props.ingredient.storeSectionId),
);
const selectedStore = computed(() =>
    props.stores.find((store) => String(store.id) === storeSelection.value),
);

watch(storeSelection, () => {
    if (
        !selectedStore.value?.sections.some(
            (section) => String(section.id) === sectionSelection.value,
        )
    ) {
        sectionSelection.value = 'none';
    }
});
</script>

<template>
    <FieldGroup>
        <Field :data-invalid="Boolean(errors.name)">
            <FieldLabel :for="`${idPrefix}-name`">Název suroviny</FieldLabel>
            <Input
                :id="`${idPrefix}-name`"
                name="name"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="Celozrnný chléb"
                :default-value="ingredient?.name"
                :aria-invalid="Boolean(errors.name)"
            />
            <FieldDescription>
                Název musí být v aktuální rodině jedinečný.
            </FieldDescription>
            <FieldError :errors="[errors.name]" />
        </Field>

        <Field :data-invalid="Boolean(errors.description)">
            <FieldLabel :for="`${idPrefix}-description`">Popis</FieldLabel>
            <Textarea
                :id="`${idPrefix}-description`"
                name="description"
                :rows="3"
                placeholder="Volitelný popis konkrétního balení"
                :default-value="ingredient?.description ?? ''"
                :aria-invalid="Boolean(errors.description)"
            />
            <FieldError :errors="[errors.description]" />
        </Field>

        <FieldSet>
            <FieldLegend variant="legend">Obsah balení</FieldLegend>
            <FieldDescription>
                Vyplňte alespoň jednu hodnotu. Metrické množství zadejte s
                jednotkou; počet kusů může být jedinou hodnotou nebo jej
                doplnit.
            </FieldDescription>
            <FieldError :errors="[errors.quantities]" />

            <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_8rem]">
                <Field :data-invalid="Boolean(errors.metric_quantity)">
                    <FieldLabel :for="`${idPrefix}-metric-quantity`">
                        Metrické množství
                    </FieldLabel>
                    <Input
                        :id="`${idPrefix}-metric-quantity`"
                        name="metric_quantity"
                        type="number"
                        inputmode="decimal"
                        min="0.000001"
                        step="0.000001"
                        placeholder="500"
                        :default-value="ingredient?.metricQuantity ?? ''"
                        :aria-invalid="Boolean(errors.metric_quantity)"
                    />
                    <FieldError :errors="[errors.metric_quantity]" />
                </Field>

                <Field :data-invalid="Boolean(errors.metric_unit)">
                    <FieldLabel :for="`${idPrefix}-metric-unit`">
                        Jednotka
                    </FieldLabel>
                    <Select
                        name="metric_unit"
                        :default-value="ingredient?.metricUnit ?? 'g'"
                    >
                        <SelectTrigger
                            :id="`${idPrefix}-metric-unit`"
                            :aria-invalid="Boolean(errors.metric_unit)"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="mg">mg</SelectItem>
                                <SelectItem value="g">g</SelectItem>
                                <SelectItem value="kg">kg</SelectItem>
                                <SelectItem value="ml">ml</SelectItem>
                                <SelectItem value="cl">cl</SelectItem>
                                <SelectItem value="l">l</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError :errors="[errors.metric_unit]" />
                </Field>
            </div>

            <Field :data-invalid="Boolean(errors.piece_count)">
                <FieldLabel :for="`${idPrefix}-pieces`">
                    Počet kusů v balení
                </FieldLabel>
                <Input
                    :id="`${idPrefix}-pieces`"
                    name="piece_count"
                    type="number"
                    inputmode="decimal"
                    min="0.000001"
                    step="0.000001"
                    placeholder="10"
                    :default-value="ingredient?.pieceCount ?? ''"
                    :aria-invalid="Boolean(errors.piece_count)"
                />
                <FieldError :errors="[errors.piece_count]" />
            </Field>
        </FieldSet>

        <FieldSet>
            <FieldLegend variant="legend">Umístění v obchodě</FieldLegend>
            <FieldDescription>
                Umístění slouží pouze pro budoucí seskupení nákupního seznamu.
                Nevyjadřuje dostupnost ani skladovou zásobu.
            </FieldDescription>

            <input
                type="hidden"
                name="store_id"
                :value="storeSelection === 'none' ? '' : storeSelection"
            />
            <input
                type="hidden"
                name="store_section_id"
                :value="sectionSelection === 'none' ? '' : sectionSelection"
            />

            <Field :data-invalid="Boolean(errors.store_id)">
                <FieldLabel :for="`${idPrefix}-store`">Obchod</FieldLabel>
                <Select v-model="storeSelection">
                    <SelectTrigger
                        :id="`${idPrefix}-store`"
                        :aria-invalid="Boolean(errors.store_id)"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="none">Bez obchodu</SelectItem>
                            <SelectItem
                                v-for="store in stores"
                                :key="store.id"
                                :value="String(store.id)"
                            >
                                {{ store.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <FieldError :errors="[errors.store_id]" />
            </Field>

            <Field :data-invalid="Boolean(errors.store_section_id)">
                <FieldLabel :for="`${idPrefix}-store-section`">
                    Část obchodu
                </FieldLabel>
                <Select
                    v-model="sectionSelection"
                    :disabled="storeSelection === 'none'"
                >
                    <SelectTrigger
                        :id="`${idPrefix}-store-section`"
                        :aria-invalid="Boolean(errors.store_section_id)"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="none"
                                >Bez části obchodu</SelectItem
                            >
                            <SelectItem
                                v-for="section in selectedStore?.sections ?? []"
                                :key="section.id"
                                :value="String(section.id)"
                            >
                                {{ section.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <FieldError :errors="[errors.store_section_id]" />
            </Field>
        </FieldSet>

        <Field v-if="showSubmit" orientation="horizontal">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" data-icon="inline-start" />
                {{ submitLabel }}
            </Button>
        </Field>
    </FieldGroup>
</template>

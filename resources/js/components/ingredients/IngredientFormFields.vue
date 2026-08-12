<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import StoreSectionIcon from '@/components/stores/StoreSectionIcon.vue';
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
import { formatDecimalInput } from '@/lib/formatDecimalInput';
import type { IngredientPlacementStore, IngredientSummary } from '@/types';

type IngredientField =
    | 'name'
    | 'metric_quantity'
    | 'metric_unit'
    | 'piece_count'
    | 'description'
    | 'store_id'
    | 'store_section_id'
    | 'nutrition'
    | 'nutrition_basis_kind'
    | 'nutrition_basis_quantity'
    | 'nutrition_energy_kcal'
    | 'nutrition_fat_grams'
    | 'nutrition_protein_grams'
    | 'nutrition_carbohydrate_grams'
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

const storeSelection = shallowRef(
    props.ingredient?.storeId === null ||
        props.ingredient?.storeId === undefined
        ? 'none'
        : String(props.ingredient.storeId),
);
const sectionSelection = shallowRef(
    props.ingredient?.storeSectionId === null ||
        props.ingredient?.storeSectionId === undefined
        ? 'none'
        : String(props.ingredient.storeSectionId),
);
const nutritionBasis = shallowRef(
    props.ingredient?.nutrition?.basisKind ?? 'none',
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
                        :default-value="
                            formatDecimalInput(ingredient?.metricQuantity)
                        "
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
                    Počet kusů v balení (volitelné)
                </FieldLabel>
                <Input
                    :id="`${idPrefix}-pieces`"
                    name="piece_count"
                    type="number"
                    inputmode="decimal"
                    min="0.000001"
                    step="0.000001"
                    placeholder="10"
                    :default-value="formatDecimalInput(ingredient?.pieceCount)"
                    :aria-invalid="Boolean(errors.piece_count)"
                />
                <FieldDescription>
                    Nechte prázdné, pokud balení určujete pouze hmotností nebo
                    objemem.
                </FieldDescription>
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
                                <span class="flex items-center gap-2">
                                    <img
                                        v-if="store.logoUrl"
                                        :src="store.logoUrl"
                                        alt=""
                                        class="size-5 rounded object-cover"
                                    />
                                    {{ store.name }}
                                </span>
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
                                <span class="flex items-center gap-2">
                                    <span
                                        class="size-2.5 rounded-full border"
                                        :style="{
                                            backgroundColor: section.colour,
                                        }"
                                    />
                                    <img
                                        v-if="section.iconUrl"
                                        :src="section.iconUrl"
                                        alt=""
                                        class="size-5 rounded object-cover"
                                    />
                                    <StoreSectionIcon
                                        v-else-if="section.icon"
                                        :name="section.icon"
                                        class="size-4"
                                    />
                                    {{ section.name }}
                                </span>
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <FieldError :errors="[errors.store_section_id]" />
            </Field>
        </FieldSet>

        <FieldSet>
            <FieldLegend variant="legend">Nutriční profil</FieldLegend>
            <FieldDescription>
                Volitelně vyplňte energii a všechny makroživiny pro uvedené
                základní množství. Částečný profil nelze uložit.
            </FieldDescription>
            <FieldError :errors="[errors.nutrition]" />
            <input
                type="hidden"
                name="nutrition_basis_kind"
                :value="nutritionBasis === 'none' ? '' : nutritionBasis"
            />
            <Field :data-invalid="Boolean(errors.nutrition_basis_kind)">
                <FieldLabel :for="`${idPrefix}-nutrition-basis-kind`"
                    >Základ profilu</FieldLabel
                >
                <Select v-model="nutritionBasis">
                    <SelectTrigger
                        :id="`${idPrefix}-nutrition-basis-kind`"
                        :aria-invalid="Boolean(errors.nutrition_basis_kind)"
                        ><SelectValue
                    /></SelectTrigger>
                    <SelectContent
                        ><SelectGroup>
                            <SelectItem value="none"
                                >Bez nutričního profilu</SelectItem
                            >
                            <SelectItem value="package">Celé balení</SelectItem>
                            <SelectItem value="grams">Gramy</SelectItem>
                            <SelectItem value="millilitres"
                                >Mililitry</SelectItem
                            >
                            <SelectItem value="piece">Kusy</SelectItem>
                        </SelectGroup></SelectContent
                    >
                </Select>
                <FieldError :errors="[errors.nutrition_basis_kind]" />
            </Field>
            <div class="grid gap-4 sm:grid-cols-2">
                <Field :data-invalid="Boolean(errors.nutrition_basis_quantity)">
                    <FieldLabel :for="`${idPrefix}-nutrition-basis-quantity`"
                        >Základní množství</FieldLabel
                    >
                    <Input
                        :id="`${idPrefix}-nutrition-basis-quantity`"
                        name="nutrition_basis_quantity"
                        type="number"
                        inputmode="decimal"
                        min="0.000001"
                        step="0.000001"
                        :disabled="nutritionBasis === 'none'"
                        :default-value="
                            formatDecimalInput(
                                ingredient?.nutrition?.basisQuantity,
                            )
                        "
                        :aria-invalid="Boolean(errors.nutrition_basis_quantity)"
                    />
                    <FieldError :errors="[errors.nutrition_basis_quantity]" />
                </Field>
                <Field
                    v-for="field in [
                        [
                            'energy_kcal',
                            'Energie (kcal)',
                            ingredient?.nutrition?.energyKcal,
                        ],
                        [
                            'fat_grams',
                            'Tuky (g)',
                            ingredient?.nutrition?.fatGrams,
                        ],
                        [
                            'protein_grams',
                            'Bílkoviny (g)',
                            ingredient?.nutrition?.proteinGrams,
                        ],
                        [
                            'carbohydrate_grams',
                            'Sacharidy (g)',
                            ingredient?.nutrition?.carbohydrateGrams,
                        ],
                    ] as const"
                    :key="field[0]"
                    :data-invalid="Boolean(errors[`nutrition_${field[0]}`])"
                >
                    <FieldLabel :for="`${idPrefix}-nutrition-${field[0]}`">{{
                        field[1]
                    }}</FieldLabel>
                    <Input
                        :id="`${idPrefix}-nutrition-${field[0]}`"
                        :name="`nutrition_${field[0]}`"
                        type="number"
                        inputmode="decimal"
                        min="0"
                        step="0.000001"
                        :disabled="nutritionBasis === 'none'"
                        :default-value="formatDecimalInput(field[2])"
                        :aria-invalid="Boolean(errors[`nutrition_${field[0]}`])"
                    />
                    <FieldError :errors="[errors[`nutrition_${field[0]}`]]" />
                </Field>
            </div>
        </FieldSet>

        <Field v-if="showSubmit" orientation="horizontal">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" data-icon="inline-start" />
                {{ submitLabel }}
            </Button>
        </Field>
    </FieldGroup>
</template>

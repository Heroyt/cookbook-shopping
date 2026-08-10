<script setup lang="ts">
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
import { Spinner } from '@/components/ui/spinner';

type IngredientField =
    | 'name'
    | 'weight_grams'
    | 'volume_millilitres'
    | 'piece_count'
    | 'quantities';

withDefaults(
    defineProps<{
        errors?: Partial<Record<IngredientField, string>>;
        processing?: boolean;
    }>(),
    {
        errors: () => ({}),
        processing: false,
    },
);
</script>

<template>
    <FieldGroup>
        <Field :data-invalid="Boolean(errors.name)">
            <FieldLabel for="ingredient-name">Název suroviny</FieldLabel>
            <Input
                id="ingredient-name"
                name="name"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="Celozrnný chléb"
                :aria-invalid="Boolean(errors.name)"
            />
            <FieldDescription>
                Název musí být v aktuální rodině jedinečný.
            </FieldDescription>
            <FieldError :errors="[errors.name]" />
        </Field>

        <FieldSet>
            <FieldLegend variant="legend">Obsah balení</FieldLegend>
            <FieldDescription>
                Vyplňte alespoň jednu hodnotu. Hmotnost a objem nelze zadat
                současně; počet kusů může být jedinou hodnotou nebo doplnit
                jednu z nich.
            </FieldDescription>
            <FieldError :errors="[errors.quantities]" />

            <Field :data-invalid="Boolean(errors.weight_grams)">
                <FieldLabel for="ingredient-weight">
                    Hmotnost balení (g)
                </FieldLabel>
                <Input
                    id="ingredient-weight"
                    name="weight_grams"
                    type="number"
                    inputmode="decimal"
                    min="0.000001"
                    step="0.000001"
                    placeholder="500"
                    :aria-invalid="Boolean(errors.weight_grams)"
                />
                <FieldError :errors="[errors.weight_grams]" />
            </Field>

            <Field :data-invalid="Boolean(errors.volume_millilitres)">
                <FieldLabel for="ingredient-volume">
                    Objem balení (ml)
                </FieldLabel>
                <Input
                    id="ingredient-volume"
                    name="volume_millilitres"
                    type="number"
                    inputmode="decimal"
                    min="0.000001"
                    step="0.000001"
                    placeholder="1000"
                    :aria-invalid="Boolean(errors.volume_millilitres)"
                />
                <FieldError :errors="[errors.volume_millilitres]" />
            </Field>

            <Field :data-invalid="Boolean(errors.piece_count)">
                <FieldLabel for="ingredient-pieces">
                    Počet kusů v balení
                </FieldLabel>
                <Input
                    id="ingredient-pieces"
                    name="piece_count"
                    type="number"
                    inputmode="decimal"
                    min="0.000001"
                    step="0.000001"
                    placeholder="10"
                    :aria-invalid="Boolean(errors.piece_count)"
                />
                <FieldError :errors="[errors.piece_count]" />
            </Field>
        </FieldSet>

        <Field orientation="horizontal">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" data-icon="inline-start" />
                Vytvořit surovinu
            </Button>
        </Field>
    </FieldGroup>
</template>

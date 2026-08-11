<script setup lang="ts">
import { PlusIcon, Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldSet,
    FieldLegend,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
    RecipeIngredientOption,
    RecipeSummary,
    RecipeTagOption,
} from '@/types';

const props = withDefaults(
    defineProps<{
        ingredients: RecipeIngredientOption[];
        tags: RecipeTagOption[];
        recipe?: RecipeSummary | null;
        errors?: Record<string, string>;
        processing?: boolean;
    }>(),
    { recipe: null, errors: () => ({}), processing: false },
);

type Line = {
    ingredientId: string;
    quantity: string;
    quantityKind: 'grams' | 'millilitres' | 'piece';
};

const lines = ref<Line[]>(
    props.recipe?.ingredients.map((line) => ({
        ingredientId: String(line.ingredientId),
        quantity: line.quantity,
        quantityKind: line.quantityKind,
    })) ?? [{ ingredientId: '', quantity: '', quantityKind: 'grams' }],
);
const steps = ref<string[]>(
    props.recipe?.steps.map((step) => step.instruction) ?? [''],
);
const selectedTags = ref<number[]>(
    props.recipe?.tags.map((tag) => tag.id) ?? [],
);

const addLine = (): void => {
    lines.value.push({
        ingredientId: '',
        quantity: '',
        quantityKind: 'grams',
    });
};

const removeLine = (index: number): void => {
    if (lines.value.length > 1) {
        lines.value.splice(index, 1);
    }
};

const kindsFor = (ingredientId: string): Line['quantityKind'][] =>
    props.ingredients.find(
        (ingredient) => ingredient.id === Number(ingredientId),
    )?.kinds ?? ['grams', 'millilitres', 'piece'];

const quantityKindLabel = (kind: Line['quantityKind']): string =>
    ({ grams: 'gramy', millilitres: 'mililitry', piece: 'kusy' })[kind];
</script>

<template>
    <input v-if="recipe" type="hidden" name="version" :value="recipe.version" />

    <FieldGroup>
        <Field :data-invalid="Boolean(errors.name)">
            <FieldLabel for="recipe-name">Název receptu</FieldLabel>
            <Input
                id="recipe-name"
                name="name"
                :default-value="recipe?.name ?? ''"
                required
                :aria-invalid="Boolean(errors.name)"
                autocomplete="off"
            />
            <FieldError :errors="[errors.name]" />
        </Field>

        <Field :data-invalid="Boolean(errors.base_servings)">
            <FieldLabel for="recipe-servings">Počet porcí</FieldLabel>
            <Input
                id="recipe-servings"
                name="base_servings"
                type="number"
                min="0.000001"
                step="0.000001"
                :default-value="recipe?.baseServings ?? '4'"
                required
                :aria-invalid="Boolean(errors.base_servings)"
            />
            <FieldDescription
                >Základní počet porcí pro množství níže.</FieldDescription
            >
            <FieldError :errors="[errors.base_servings]" />
        </Field>

        <FieldSet>
            <FieldLegend>Suroviny receptu</FieldLegend>
            <FieldDescription
                >Surovinu můžete v receptu použít opakovaně. Pořadí řádků se
                uloží.</FieldDescription
            >
            <FieldError :errors="[errors.ingredients]" />
            <div
                v-for="(line, index) in lines"
                :key="index"
                class="grid gap-3 rounded-md border p-3 md:grid-cols-[1fr_8rem_10rem_auto]"
            >
                <Field
                    :data-invalid="
                        Boolean(errors[`ingredients.${index}.ingredient_id`])
                    "
                >
                    <FieldLabel :for="`recipe-ingredient-${index}`"
                        >Surovina {{ index + 1 }}</FieldLabel
                    >
                    <Select
                        v-model="line.ingredientId"
                        :name="`ingredients[${index}][ingredient_id]`"
                        required
                    >
                        <SelectTrigger
                            :id="`recipe-ingredient-${index}`"
                            :aria-invalid="
                                Boolean(
                                    errors[
                                        `ingredients.${index}.ingredient_id`
                                    ],
                                )
                            "
                        >
                            <SelectValue placeholder="Vyberte surovinu" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="ingredient in ingredients"
                                :key="ingredient.id"
                                :value="String(ingredient.id)"
                                >{{ ingredient.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <FieldError
                        :errors="[errors[`ingredients.${index}.ingredient_id`]]"
                    />
                </Field>
                <Field
                    :data-invalid="
                        Boolean(errors[`ingredients.${index}.quantity`])
                    "
                >
                    <FieldLabel :for="`recipe-quantity-${index}`"
                        >Množství</FieldLabel
                    >
                    <Input
                        :id="`recipe-quantity-${index}`"
                        v-model="line.quantity"
                        :name="`ingredients[${index}][quantity]`"
                        type="number"
                        min="0.000001"
                        step="0.000001"
                        required
                        :aria-invalid="
                            Boolean(errors[`ingredients.${index}.quantity`])
                        "
                    />
                    <FieldError
                        :errors="[errors[`ingredients.${index}.quantity`]]"
                    />
                </Field>
                <Field
                    :data-invalid="
                        Boolean(errors[`ingredients.${index}.quantity_kind`])
                    "
                >
                    <FieldLabel :for="`recipe-kind-${index}`"
                        >Jednotka</FieldLabel
                    >
                    <Select
                        v-model="line.quantityKind"
                        :name="`ingredients[${index}][quantity_kind]`"
                        required
                    >
                        <SelectTrigger
                            :id="`recipe-kind-${index}`"
                            :aria-invalid="
                                Boolean(
                                    errors[
                                        `ingredients.${index}.quantity_kind`
                                    ],
                                )
                            "
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="kind in kindsFor(line.ingredientId)"
                                :key="kind"
                                :value="kind"
                                >{{ quantityKindLabel(kind) }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <FieldError
                        :errors="[errors[`ingredients.${index}.quantity_kind`]]"
                    />
                </Field>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="self-end"
                    :disabled="lines.length === 1"
                    :aria-label="`Odebrat surovinu ${index + 1}`"
                    @click="removeLine(index)"
                    ><Trash2Icon
                /></Button>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addLine"
                ><PlusIcon data-icon="inline-start" /> Přidat surovinu</Button
            >
        </FieldSet>

        <FieldSet>
            <FieldLegend>Postup</FieldLegend>
            <FieldDescription
                >Kroky jsou volitelné a uloží se v uvedeném
                pořadí.</FieldDescription
            >
            <div
                v-for="(_step, index) in steps"
                :key="index"
                class="flex items-end gap-2"
            >
                <Field
                    class="flex-1"
                    :data-invalid="Boolean(errors[`steps.${index}`])"
                >
                    <FieldLabel :for="`recipe-step-${index}`"
                        >Krok {{ index + 1 }}</FieldLabel
                    >
                    <Textarea
                        :id="`recipe-step-${index}`"
                        v-model="steps[index]"
                        :name="`steps[${index}]`"
                        :aria-invalid="Boolean(errors[`steps.${index}`])"
                    />
                    <FieldError :errors="[errors[`steps.${index}`]]" />
                </Field>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="`Odebrat krok ${index + 1}`"
                    @click="steps.splice(index, 1)"
                    ><Trash2Icon
                /></Button>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="steps.push('')"
                ><PlusIcon data-icon="inline-start" /> Přidat krok</Button
            >
        </FieldSet>

        <FieldSet v-if="tags.length > 0">
            <FieldLegend>Štítky</FieldLegend>
            <div class="flex flex-wrap gap-3">
                <label
                    v-for="tag in tags"
                    :key="tag.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <input
                        v-model="selectedTags"
                        type="checkbox"
                        name="tag_ids[]"
                        :value="tag.id"
                        class="size-4 rounded border-input"
                    />
                    {{ tag.name }}
                </label>
            </div>
            <FieldError :errors="[errors.tag_ids]" />
        </FieldSet>

        <div class="grid gap-4 md:grid-cols-2">
            <Field
                ><FieldLabel for="recipe-source">Zdrojový odkaz</FieldLabel
                ><Input
                    id="recipe-source"
                    name="source_url"
                    type="url"
                    :default-value="recipe?.sourceUrl ?? ''"
                    :aria-invalid="Boolean(errors.source_url)" /><FieldError
                    :errors="[errors.source_url]"
            /></Field>
            <Field
                ><FieldLabel for="recipe-preparation"
                    >Příprava (minuty)</FieldLabel
                ><Input
                    id="recipe-preparation"
                    name="preparation_minutes"
                    type="number"
                    min="0"
                    step="1"
                    :default-value="recipe?.preparationMinutes ?? ''"
                    :aria-invalid="
                        Boolean(errors.preparation_minutes)
                    " /><FieldError :errors="[errors.preparation_minutes]"
            /></Field>
            <Field
                ><FieldLabel for="recipe-cooking">Vaření (minuty)</FieldLabel
                ><Input
                    id="recipe-cooking"
                    name="cooking_minutes"
                    type="number"
                    min="0"
                    step="1"
                    :default-value="recipe?.cookingMinutes ?? ''"
                    :aria-invalid="
                        Boolean(errors.cooking_minutes)
                    " /><FieldError :errors="[errors.cooking_minutes]"
            /></Field>
            <Field class="md:col-span-2"
                ><FieldLabel for="recipe-notes">Poznámky</FieldLabel
                ><Textarea
                    id="recipe-notes"
                    name="notes"
                    :default-value="recipe?.notes ?? ''"
            /></Field>
        </div>

        <FieldSet>
            <FieldLegend>Nutriční přepis na porci</FieldLegend>
            <FieldDescription
                >Vyplňte všechny čtyři hodnoty, nebo ponechte vše prázdné pro
                automatický výpočet.</FieldDescription
            >
            <FieldError :errors="[errors.nutrition]" />
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Field
                    v-for="field in [
                        [
                            'nutrition_energy_kcal',
                            'Energie (kcal)',
                            recipe?.nutritionOverride?.energyKcal,
                        ],
                        [
                            'nutrition_fat_grams',
                            'Tuky (g)',
                            recipe?.nutritionOverride?.fatGrams,
                        ],
                        [
                            'nutrition_protein_grams',
                            'Bílkoviny (g)',
                            recipe?.nutritionOverride?.proteinGrams,
                        ],
                        [
                            'nutrition_carbohydrate_grams',
                            'Sacharidy (g)',
                            recipe?.nutritionOverride?.carbohydrateGrams,
                        ],
                    ]"
                    :key="field[0]"
                >
                    <FieldLabel :for="String(field[0])">{{
                        field[1]
                    }}</FieldLabel>
                    <Input
                        :id="String(field[0])"
                        :name="String(field[0])"
                        type="number"
                        min="0"
                        step="0.000001"
                        :default-value="field[2] ?? ''"
                        :aria-invalid="Boolean(errors[String(field[0])])"
                    />
                    <FieldError :errors="[errors[String(field[0])]]" />
                </Field>
            </div>
        </FieldSet>

        <Button type="submit" :disabled="processing">{{
            recipe ? 'Uložit celý recept' : 'Vytvořit recept'
        }}</Button>
    </FieldGroup>
</template>

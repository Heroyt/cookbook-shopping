<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PlusIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import { store } from '@/actions/App/MealPlanning/Http/Controllers/CalendarController';
import RecipeSearchSelect from '@/components/recipes/RecipeSearchSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    date: string;
    mealLabel: string | null;
    groupLabel: string;
}>();
const open = shallowRef(false);
const recipeId = shallowRef('');
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-label="`Přidat recept: ${groupLabel}, ${date}`"
                ><PlusIcon
            /></Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Přidat recept</DialogTitle>
                <DialogDescription
                    >{{ groupLabel }} · {{ date }}</DialogDescription
                >
            </DialogHeader>
            <Form
                v-bind="store.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
                @success="open = false"
            >
                <input type="hidden" name="date" :value="date" />
                <input
                    type="hidden"
                    name="meal_label"
                    :value="mealLabel ?? ''"
                />
                <FieldGroup>
                    <Field :data-invalid="Boolean(errors.recipe_id)">
                        <FieldLabel for="calendar-slot-recipe"
                            >Recept</FieldLabel
                        >
                        <RecipeSearchSelect
                            id="calendar-slot-recipe"
                            v-model="recipeId"
                            name="recipe_id"
                            :invalid="Boolean(errors.recipe_id)"
                        />
                        <FieldError :errors="[errors.recipe_id]" />
                    </Field>
                    <Field :data-invalid="Boolean(errors.serving_count)">
                        <FieldLabel>Počet porcí</FieldLabel>
                        <Input
                            name="serving_count"
                            type="number"
                            min="0.000001"
                            step="0.000001"
                            default-value="1"
                            required
                        />
                        <FieldError :errors="[errors.serving_count]" />
                    </Field>
                    <Field :data-invalid="Boolean(errors.repeat_days)">
                        <FieldLabel>Počet po sobě jdoucích dnů</FieldLabel>
                        <Input
                            name="repeat_days"
                            type="number"
                            min="1"
                            max="31"
                            step="1"
                            default-value="1"
                            required
                        />
                        <FieldDescription
                            >1 znamená pouze vybraný den, nejvýše 31
                            dnů.</FieldDescription
                        >
                        <FieldError :errors="[errors.repeat_days]" />
                    </Field>
                </FieldGroup>
                <Button type="submit" :disabled="processing || recipeId === ''"
                    ><Spinner
                        v-if="processing"
                        data-icon="inline-start"
                    />Přidat do kalendáře</Button
                >
            </Form>
        </DialogContent>
    </Dialog>
</template>

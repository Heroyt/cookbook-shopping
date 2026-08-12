<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PencilIcon, Trash2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import {
    destroy,
    update,
} from '@/actions/App/MealPlanning/Http/Controllers/CalendarController';
import CalendarNutrition from '@/components/calendar/CalendarNutrition.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
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
import { formatDecimalInput } from '@/lib/formatDecimalInput';
import type {
    CalendarEntryProjection,
    CalendarMealLabelOption,
    CalendarRecipeOption,
} from '@/types';

const props = defineProps<{
    entry: CalendarEntryProjection;
    recipes: CalendarRecipeOption[];
    mealLabels: CalendarMealLabelOption[];
}>();

const recipeId = shallowRef(String(props.entry.recipeId));
const mealLabel = shallowRef(props.entry.mealLabel ?? 'unlabeled');
const open = shallowRef(false);
</script>

<template>
    <Card>
        <CardHeader class="gap-2">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <CardTitle class="text-base">{{ entry.recipeName }}</CardTitle>
                <Badge v-if="entry.recipeArchived" variant="destructive">
                    Archivováno
                </Badge>
            </div>
        </CardHeader>
        <CardContent>
            <p class="mb-3 text-sm font-medium tabular-nums">
                {{ entry.servingCount.replace('.', ',') }} porce
            </p>
            <CalendarNutrition
                :nutrition="entry.nutrition"
                label="Výživa pro naplánované porce"
            />
        </CardContent>
        <CardFooter class="flex flex-wrap gap-2">
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button type="button" variant="outline" size="sm">
                        <PencilIcon data-icon="inline-start" />
                        Upravit
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Upravit záznam</DialogTitle>
                        <DialogDescription>
                            Změňte recept, datum, označení nebo počet porcí.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        v-bind="update.form(entry.id)"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-4"
                        @success="open = false"
                    >
                        <Alert v-if="entry.recipeArchived">
                            <AlertTitle>Archivovaný recept</AlertTitle>
                            <AlertDescription>
                                Lze změnit pouze počet porcí. Nejprve obnovte
                                recept, pokud potřebujete změnit ostatní údaje.
                            </AlertDescription>
                        </Alert>
                        <FieldError :errors="[errors.entry]" />
                        <FieldGroup>
                            <template v-if="entry.recipeArchived">
                                <input
                                    type="hidden"
                                    name="recipe_id"
                                    :value="entry.recipeId"
                                />
                                <input
                                    type="hidden"
                                    name="date"
                                    :value="entry.date"
                                />
                                <input
                                    type="hidden"
                                    name="meal_label"
                                    :value="entry.mealLabel ?? ''"
                                />
                            </template>
                            <Field
                                v-else
                                :data-invalid="Boolean(errors.recipe_id)"
                            >
                                <FieldLabel
                                    :for="`calendar-entry-${entry.id}-recipe`"
                                    >Recept</FieldLabel
                                >
                                <Select v-model="recipeId" name="recipe_id">
                                    <SelectTrigger
                                        :id="`calendar-entry-${entry.id}-recipe`"
                                        :aria-invalid="
                                            Boolean(errors.recipe_id)
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="recipe in recipes"
                                                :key="recipe.id"
                                                :value="String(recipe.id)"
                                            >
                                                {{ recipe.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError :errors="[errors.recipe_id]" />
                            </Field>
                            <Field
                                v-if="!entry.recipeArchived"
                                :data-invalid="Boolean(errors.date)"
                            >
                                <FieldLabel
                                    :for="`calendar-entry-${entry.id}-date`"
                                    >Datum</FieldLabel
                                >
                                <Input
                                    :id="`calendar-entry-${entry.id}-date`"
                                    name="date"
                                    type="date"
                                    :default-value="entry.date"
                                    required
                                    :aria-invalid="Boolean(errors.date)"
                                />
                                <FieldError :errors="[errors.date]" />
                            </Field>
                            <Field
                                v-if="!entry.recipeArchived"
                                :data-invalid="Boolean(errors.meal_label)"
                            >
                                <FieldLabel
                                    :for="`calendar-entry-${entry.id}-label`"
                                    >Označení jídla</FieldLabel
                                >
                                <input
                                    type="hidden"
                                    name="meal_label"
                                    :value="
                                        mealLabel === 'unlabeled'
                                            ? ''
                                            : mealLabel
                                    "
                                />
                                <Select v-model="mealLabel">
                                    <SelectTrigger
                                        :id="`calendar-entry-${entry.id}-label`"
                                        :aria-invalid="
                                            Boolean(errors.meal_label)
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="unlabeled"
                                                >Bez označení</SelectItem
                                            >
                                            <SelectItem
                                                v-for="label in mealLabels"
                                                :key="label.value"
                                                :value="label.value"
                                            >
                                                {{ label.label }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError :errors="[errors.meal_label]" />
                            </Field>
                            <Field
                                :data-invalid="Boolean(errors.serving_count)"
                            >
                                <FieldLabel
                                    :for="`calendar-entry-${entry.id}-servings`"
                                    >Počet porcí</FieldLabel
                                >
                                <Input
                                    :id="`calendar-entry-${entry.id}-servings`"
                                    name="serving_count"
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    :default-value="
                                        formatDecimalInput(entry.servingCount)
                                    "
                                    required
                                    inputmode="decimal"
                                    :aria-invalid="
                                        Boolean(errors.serving_count)
                                    "
                                />
                                <FieldError :errors="[errors.serving_count]" />
                            </Field>
                        </FieldGroup>
                        <DialogFooter>
                            <Button type="submit" :disabled="processing">
                                <Spinner
                                    v-if="processing"
                                    data-icon="inline-start"
                                    aria-hidden="true"
                                />
                                Uložit změny
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
            <Form v-bind="destroy.form(entry.id)" v-slot="{ processing }">
                <Button
                    type="submit"
                    variant="ghost"
                    size="sm"
                    :disabled="processing"
                >
                    <Spinner
                        v-if="processing"
                        data-icon="inline-start"
                        aria-hidden="true"
                    />
                    <Trash2Icon v-else data-icon="inline-start" />
                    Smazat
                </Button>
            </Form>
        </CardFooter>
    </Card>
</template>

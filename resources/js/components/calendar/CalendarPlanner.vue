<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CalendarPlusIcon,
    ShoppingCartIcon,
    XIcon,
} from '@lucide/vue';
import { computed, ref, shallowRef, watch } from 'vue';
import {
    generate,
    store,
} from '@/actions/App/MealPlanning/Http/Controllers/CalendarController';
import CalendarEntryCard from '@/components/calendar/CalendarEntryCard.vue';
import CalendarNutrition from '@/components/calendar/CalendarNutrition.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/calendar';
import type {
    CalendarDayProjection,
    CalendarMealLabelOption,
    CalendarRecipeOption,
    CalendarWeekProjection,
} from '@/types';

const props = defineProps<{
    week: CalendarWeekProjection;
    days: CalendarDayProjection[];
    recipes: CalendarRecipeOption[];
    mealLabels: CalendarMealLabelOption[];
    selectedDates: string[];
}>();

const recipeId = shallowRef('');
const mealLabel = shallowRef('unlabeled');
const selectedDates = ref([...props.selectedDates]);
const manualDate = shallowRef('');
const rangeStart = shallowRef(props.week.startsOn);
const rangeEnd = shallowRef(props.week.endsOn);
const entryCount = computed(() =>
    props.days.reduce(
        (total, day) =>
            total +
            day.groups.reduce(
                (dayTotal, group) => dayTotal + group.entries.length,
                0,
            ),
        0,
    ),
);

watch(
    () => props.selectedDates,
    (dates) => {
        selectedDates.value = [...dates];
    },
);

const toggleDate = (date: string, checked: boolean | 'indeterminate'): void => {
    selectedDates.value =
        checked === true
            ? [...new Set([...selectedDates.value, date])].sort()
            : selectedDates.value.filter(
                  (selectedDate) => selectedDate !== date,
              );
};

const selectRange = (): void => {
    const first =
        rangeStart.value <= rangeEnd.value ? rangeStart.value : rangeEnd.value;
    const last =
        rangeStart.value <= rangeEnd.value ? rangeEnd.value : rangeStart.value;
    selectedDates.value = props.days
        .map((day) => day.date)
        .filter((date) => date >= first && date <= last);
};

const addManualDate = (): void => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(manualDate.value)) {
        return;
    }

    selectedDates.value = [
        ...new Set([...selectedDates.value, manualDate.value]),
    ].sort();
    manualDate.value = '';
};

const dayHasEntries = (day: CalendarDayProjection): boolean =>
    day.groups.some((group) => group.entries.length > 0);
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Button as-child variant="outline" size="sm">
                <Link :href="index({ query: { week: week.previousStartsOn } })">
                    <ArrowLeftIcon data-icon="inline-start" />
                    Předchozí týden
                </Link>
            </Button>
            <p class="font-medium tabular-nums">
                {{ week.startsOn }} – {{ week.endsOn }}
            </p>
            <Button as-child variant="outline" size="sm">
                <Link :href="index({ query: { week: week.nextStartsOn } })">
                    Další týden
                    <ArrowRightIcon data-icon="inline-end" />
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Přidat recept do kalendáře</CardTitle>
                <CardDescription>
                    Stejný recept na stejném datu a se stejným označením se
                    přesně přičte k existujícímu záznamu.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="store.form()"
                    v-slot="{ errors, processing }"
                    reset-on-success
                    class="flex flex-col gap-4"
                    @success="
                        recipeId = '';
                        mealLabel = 'unlabeled';
                    "
                >
                    <FieldGroup>
                        <Field :data-invalid="Boolean(errors.recipe_id)">
                            <FieldLabel for="calendar-new-recipe"
                                >Recept</FieldLabel
                            >
                            <Select
                                v-model="recipeId"
                                name="recipe_id"
                                required
                            >
                                <SelectTrigger
                                    id="calendar-new-recipe"
                                    :aria-invalid="Boolean(errors.recipe_id)"
                                >
                                    <SelectValue placeholder="Vyberte recept" />
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
                        <Field :data-invalid="Boolean(errors.date)">
                            <FieldLabel for="calendar-new-date"
                                >Datum</FieldLabel
                            >
                            <Input
                                id="calendar-new-date"
                                name="date"
                                type="date"
                                :default-value="week.startsOn"
                                required
                                :aria-invalid="Boolean(errors.date)"
                            />
                            <FieldError :errors="[errors.date]" />
                        </Field>
                        <Field :data-invalid="Boolean(errors.meal_label)">
                            <FieldLabel for="calendar-new-label"
                                >Označení jídla</FieldLabel
                            >
                            <input
                                type="hidden"
                                name="meal_label"
                                :value="
                                    mealLabel === 'unlabeled' ? '' : mealLabel
                                "
                            />
                            <Select v-model="mealLabel">
                                <SelectTrigger
                                    id="calendar-new-label"
                                    :aria-invalid="Boolean(errors.meal_label)"
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
                        <Field :data-invalid="Boolean(errors.serving_count)">
                            <FieldLabel for="calendar-new-serving-count"
                                >Počet porcí</FieldLabel
                            >
                            <Input
                                id="calendar-new-serving-count"
                                name="serving_count"
                                type="number"
                                min="0.000001"
                                step="0.000001"
                                default-value="1"
                                required
                                inputmode="decimal"
                                :aria-invalid="Boolean(errors.serving_count)"
                            />
                            <FieldDescription
                                >Lze zadat i desetinný počet, například
                                1,5.</FieldDescription
                            >
                            <FieldError :errors="[errors.serving_count]" />
                        </Field>
                    </FieldGroup>
                    <Button
                        type="submit"
                        :disabled="processing || recipes.length === 0"
                    >
                        <Spinner
                            v-if="processing"
                            data-icon="inline-start"
                            aria-hidden="true"
                        />
                        <CalendarPlusIcon v-else data-icon="inline-start" />
                        Přidat do kalendáře
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="day in days" :key="day.date">
                <CardHeader>
                    <CardTitle class="capitalize"
                        >{{ day.weekdayLabel }} {{ day.dateLabel }}</CardTitle
                    >
                    <CardDescription>{{ day.date }}</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <template
                        v-for="(group, groupIndex) in day.groups"
                        :key="group.key"
                    >
                        <Separator v-if="groupIndex > 0" />
                        <section
                            class="flex flex-col gap-2"
                            :aria-label="`${group.label}, ${day.date}`"
                        >
                            <h3 class="text-sm font-medium">
                                {{ group.label }}
                            </h3>
                            <p
                                v-if="group.entries.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                Bez receptu
                            </p>
                            <div v-else class="flex flex-col gap-3">
                                <CalendarEntryCard
                                    v-for="entry in group.entries"
                                    :key="entry.id"
                                    :entry="entry"
                                    :recipes="recipes"
                                    :meal-labels="mealLabels"
                                />
                            </div>
                        </section>
                    </template>
                </CardContent>
                <CardFooter>
                    <CalendarNutrition
                        :nutrition="day.nutrition"
                        label="Součet dne"
                    />
                </CardFooter>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Vytvořit nákupní seznam</CardTitle>
                <CardDescription>
                    Vyberte libovolná data. Rozsah je pouze rychlá pomůcka a
                    konečný výběr lze dále měnit po jednotlivých dnech.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="generate.form()"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-5"
                >
                    <input
                        v-for="date in selectedDates"
                        :key="date"
                        type="hidden"
                        name="dates[]"
                        :value="date"
                    />
                    <FieldSet>
                        <FieldLegend variant="label"
                            >Data pro nákupní seznam</FieldLegend
                        >
                        <FieldDescription
                            >Vybrány budou všechny označené i neoznačené recepty
                            na zvolených datech.</FieldDescription
                        >
                        <FieldGroup
                            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                        >
                            <Field
                                v-for="day in days"
                                :key="day.date"
                                orientation="horizontal"
                                :data-disabled="!dayHasEntries(day)"
                            >
                                <Checkbox
                                    :id="`calendar-selection-${day.date}`"
                                    :model-value="
                                        selectedDates.includes(day.date)
                                    "
                                    :disabled="!dayHasEntries(day)"
                                    @update:model-value="
                                        toggleDate(day.date, $event)
                                    "
                                />
                                <FieldLabel
                                    :for="`calendar-selection-${day.date}`"
                                    class="font-normal"
                                >
                                    {{ day.weekdayLabel }} {{ day.dateLabel }}
                                </FieldLabel>
                            </Field>
                        </FieldGroup>
                    </FieldSet>
                    <FieldGroup
                        class="sm:grid sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    >
                        <Field>
                            <FieldLabel for="calendar-range-start"
                                >Začátek rozsahu</FieldLabel
                            >
                            <Input
                                id="calendar-range-start"
                                v-model="rangeStart"
                                type="date"
                                :min="week.startsOn"
                                :max="week.endsOn"
                            />
                        </Field>
                        <Field>
                            <FieldLabel for="calendar-range-end"
                                >Konec rozsahu</FieldLabel
                            >
                            <Input
                                id="calendar-range-end"
                                v-model="rangeEnd"
                                type="date"
                                :min="week.startsOn"
                                :max="week.endsOn"
                            />
                        </Field>
                        <Button
                            type="button"
                            variant="outline"
                            @click="selectRange"
                            >Vybrat rozsah</Button
                        >
                    </FieldGroup>
                    <FieldGroup
                        class="sm:grid sm:grid-cols-[1fr_auto] sm:items-end"
                    >
                        <Field>
                            <FieldLabel for="calendar-manual-date"
                                >Libovolné datum</FieldLabel
                            >
                            <Input
                                id="calendar-manual-date"
                                v-model="manualDate"
                                type="date"
                            />
                        </Field>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="manualDate === ''"
                            @click="addManualDate"
                            >Přidat libovolné datum</Button
                        >
                    </FieldGroup>
                    <div
                        v-if="selectedDates.length > 0"
                        class="flex flex-wrap gap-2"
                        aria-label="Vybraná data"
                    >
                        <Button
                            v-for="date in selectedDates"
                            :key="date"
                            type="button"
                            variant="secondary"
                            size="sm"
                            :aria-label="`Odebrat datum ${date}`"
                            @click="toggleDate(date, false)"
                        >
                            {{ date }}
                            <XIcon data-icon="inline-end" />
                        </Button>
                    </div>
                    <FieldError :errors="[errors.dates]" />
                    <FieldError :errors="[errors['dates.0']]" />
                    <Button
                        type="submit"
                        size="lg"
                        :disabled="processing || selectedDates.length === 0"
                    >
                        <Spinner
                            v-if="processing"
                            data-icon="inline-start"
                            aria-hidden="true"
                        />
                        <ShoppingCartIcon v-else data-icon="inline-start" />
                        Vytvořit z vybraných dat
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <Empty v-if="entryCount === 0">
            <EmptyHeader>
                <EmptyTitle>Tento týden je zatím prázdný</EmptyTitle>
                <EmptyDescription
                    >Přidejte první recept pomocí formuláře
                    výše.</EmptyDescription
                >
            </EmptyHeader>
        </Empty>
    </div>
</template>

<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    ShoppingCartIcon,
    XIcon,
} from '@lucide/vue';
import { computed, ref, shallowRef, watch } from 'vue';
import { generate } from '@/actions/App/MealPlanning/Http/Controllers/CalendarController';
import AddCalendarEntryDialog from '@/components/calendar/AddCalendarEntryDialog.vue';
import CalendarEntryCard from '@/components/calendar/CalendarEntryCard.vue';
import CalendarNutrition from '@/components/calendar/CalendarNutrition.vue';
import AppDatePicker from '@/components/date/AppDatePicker.vue';
import AppDateRangePicker from '@/components/date/AppDateRangePicker.vue';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
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

const selectedDates = ref([...props.selectedDates]);
const manualDate = shallowRef('');
const showIndividualDates = shallowRef(false);
const rangeStart = shallowRef(props.week.startsOn);
const rangeEnd = shallowRef(props.week.endsOn);
const generationOpen = shallowRef(false);
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

watch([rangeStart, rangeEnd], selectRange);

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
            <div class="flex flex-wrap justify-end gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="index({ query: { week: week.nextStartsOn } })">
                        Další týden
                        <ArrowRightIcon data-icon="inline-end" />
                    </Link>
                </Button>
                <Button size="sm" @click="generationOpen = true">
                    <ShoppingCartIcon data-icon="inline-start" />
                    Vytvořit nákupní seznam
                </Button>
            </div>
        </div>

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
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <h3 class="text-sm font-medium">
                                    {{ group.label }}
                                </h3>
                                <AddCalendarEntryDialog
                                    :date="day.date"
                                    :meal-label="group.mealLabel"
                                    :group-label="group.label"
                                    :recipes="recipes"
                                />
                            </div>
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

        <Dialog v-model:open="generationOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogTitle class="sr-only"
                    >Vytvořit nákupní seznam</DialogTitle
                >
                <DialogDescription class="sr-only"
                    >Vyberte rozsah nebo jednotlivá data pro nákupní
                    seznam.</DialogDescription
                >
                <Card class="border-0 shadow-none">
                    <CardHeader>
                        <CardTitle>Vytvořit nákupní seznam</CardTitle>
                        <CardDescription>
                            Vyberte libovolná data. Rozsah je pouze rychlá
                            pomůcka a konečný výběr lze dále měnit po
                            jednotlivých dnech.
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
                            <FieldSet v-if="showIndividualDates">
                                <FieldLegend variant="label"
                                    >Data pro nákupní seznam</FieldLegend
                                >
                                <FieldDescription
                                    >Vybrány budou všechny označené i neoznačené
                                    recepty na zvolených
                                    datech.</FieldDescription
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
                                            {{ day.weekdayLabel }}
                                            {{ day.dateLabel }}
                                        </FieldLabel>
                                    </Field>
                                </FieldGroup>
                            </FieldSet>
                            <FieldGroup>
                                <Field>
                                    <FieldLabel for="calendar-date-range"
                                        >Rozsah dat</FieldLabel
                                    >
                                    <AppDateRangePicker
                                        id="calendar-date-range"
                                        v-model:start="rangeStart"
                                        v-model:end="rangeEnd"
                                        :min="week.startsOn"
                                        :max="week.endsOn"
                                    />
                                </Field>
                            </FieldGroup>
                            <FieldGroup
                                v-if="showIndividualDates"
                                class="sm:grid sm:grid-cols-[1fr_auto] sm:items-end"
                            >
                                <Field>
                                    <FieldLabel for="calendar-manual-date"
                                        >Libovolné datum</FieldLabel
                                    >
                                    <AppDatePicker
                                        id="calendar-manual-date"
                                        v-model="manualDate"
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
                            <Button
                                type="button"
                                variant="ghost"
                                class="self-start"
                                @click="
                                    showIndividualDates = !showIndividualDates
                                "
                            >
                                {{
                                    showIndividualDates
                                        ? 'Skrýt jednotlivá data'
                                        : 'Vybrat jednotlivá data'
                                }}
                            </Button>
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
                                :disabled="
                                    processing || selectedDates.length === 0
                                "
                            >
                                <Spinner
                                    v-if="processing"
                                    data-icon="inline-start"
                                    aria-hidden="true"
                                />
                                <ShoppingCartIcon
                                    v-else
                                    data-icon="inline-start"
                                />
                                Vytvořit z vybraných dat
                            </Button>
                        </Form>
                    </CardContent>
                </Card>
            </DialogContent>
        </Dialog>

        <Empty v-if="entryCount === 0">
            <EmptyHeader>
                <EmptyTitle>Tento týden je zatím prázdný</EmptyTitle>
                <EmptyDescription
                    >Přidejte první recept tlačítkem plus u vybraného dne a
                    jídla.</EmptyDescription
                >
            </EmptyHeader>
        </Empty>
    </div>
</template>

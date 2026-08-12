<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { parseDate } from '@internationalized/date';
import { CalendarRangeIcon } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

const props = defineProps<{
    id: string;
    start: string;
    end: string;
    min?: string;
    max?: string;
}>();

const emit = defineEmits<{
    'update:start': [value: string];
    'update:end': [value: string];
}>();

const open = ref(false);
const selectingEnd = ref(false);
const showsTwoMonths = useMediaQuery('(min-width: 640px)');
const parseOptionalDate = (value: string | undefined): DateValue | undefined =>
    value === undefined || value === '' ? undefined : parseDate(value);
const startDate = computed(() => parseOptionalDate(props.start));
const endDate = computed(() => parseOptionalDate(props.end));
const minimumDate = computed(() => parseOptionalDate(props.min));
const maximumDate = computed(() => parseOptionalDate(props.max));
const selectedDates = computed<DateValue[]>(() => {
    if (startDate.value === undefined || endDate.value === undefined) {
        return [];
    }

    const first =
        startDate.value.compare(endDate.value) <= 0
            ? startDate.value
            : endDate.value;
    const last =
        startDate.value.compare(endDate.value) <= 0
            ? endDate.value
            : startDate.value;
    const dates: DateValue[] = [];

    for (
        let date = first;
        date.compare(last) <= 0;
        date = date.add({ days: 1 })
    ) {
        dates.push(date);
    }

    return dates;
});
const formattedRange = computed(() => {
    if (startDate.value === undefined || endDate.value === undefined) {
        return 'Vyberte rozsah';
    }

    const formatter = new Intl.DateTimeFormat('cs-CZ', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
    const format = (date: DateValue): string =>
        formatter.format(new Date(date.year, date.month - 1, date.day));

    return `${format(startDate.value)} – ${format(endDate.value)}`;
});

const selectRangeDate = (
    value: DateValue | DateValue[] | null | undefined,
): void => {
    const nextDates = Array.isArray(value) ? value : value ? [value] : [];
    const currentValues = new Set(
        selectedDates.value.map((date) => date.toString()),
    );
    const nextValues = new Set(nextDates.map((date) => date.toString()));
    const changedDate =
        nextDates.find((date) => !currentValues.has(date.toString())) ??
        selectedDates.value.find((date) => !nextValues.has(date.toString()));

    if (changedDate === undefined) {
        return;
    }

    if (!selectingEnd.value || startDate.value === undefined) {
        emit('update:start', changedDate.toString());
        emit('update:end', changedDate.toString());
        selectingEnd.value = true;

        return;
    }

    const first =
        startDate.value.compare(changedDate) <= 0
            ? startDate.value
            : changedDate;
    const last =
        startDate.value.compare(changedDate) <= 0
            ? changedDate
            : startDate.value;

    emit('update:start', first.toString());
    emit('update:end', last.toString());
    selectingEnd.value = false;
    open.value = false;
};

watch(open, (isOpen) => {
    if (isOpen) {
        selectingEnd.value = false;
    }
});
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                class="w-full justify-start text-left font-normal"
            >
                <CalendarRangeIcon data-icon="inline-start" />
                {{ formattedRange }}
            </Button>
        </PopoverTrigger>
        <PopoverContent align="start" class="w-auto p-0">
            <p class="border-b px-3 py-2 text-sm text-muted-foreground">
                {{
                    selectingEnd
                        ? 'Vyberte poslední den rozsahu.'
                        : 'Vyberte první den rozsahu.'
                }}
            </p>
            <Calendar
                multiple
                :model-value="selectedDates"
                :min-value="minimumDate"
                :max-value="maximumDate"
                :number-of-months="showsTwoMonths ? 2 : 1"
                :week-starts-on="1"
                locale="cs-CZ"
                layout="month-and-year"
                initial-focus
                @update:model-value="selectRangeDate"
            />
        </PopoverContent>
    </Popover>
</template>

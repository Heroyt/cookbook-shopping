<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        id: string;
        modelValue: string;
        name?: string;
        placeholder?: string;
        min?: string;
        max?: string;
        required?: boolean;
        disabled?: boolean;
        ariaInvalid?: boolean;
        showToday?: boolean;
        showClear?: boolean;
        class?: string;
    }>(),
    {
        name: undefined,
        placeholder: 'Vyberte datum',
        min: undefined,
        max: undefined,
        required: false,
        disabled: false,
        ariaInvalid: false,
        showToday: true,
        showClear: true,
        class: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const open = ref(false);

const parseOptionalDate = (value: string | undefined): DateValue | undefined => {
    if (value === undefined || value === '') {
        return undefined;
    }

    return parseDate(value);
};

const selectedDate = computed(() => parseOptionalDate(props.modelValue));
const minimumDate = computed(() => parseOptionalDate(props.min));
const maximumDate = computed(() => parseOptionalDate(props.max));

const formattedDate = computed(() => {
    const date = selectedDate.value;

    if (date === undefined) {
        return props.placeholder;
    }

    return new Intl.DateTimeFormat('cs-CZ', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date.year, date.month - 1, date.day));
});

const selectDate = (value: DateValue | undefined): void => {
    emit('update:modelValue', value?.toString() ?? '');
    open.value = false;
};

const selectToday = (): void => {
    selectDate(today(getLocalTimeZone()));
};

const clear = (): void => {
    selectDate(undefined);
};

const todayIsDisabled = computed(() => {
    const current = today(getLocalTimeZone());

    return (
        (minimumDate.value !== undefined &&
            current.compare(minimumDate.value) < 0) ||
        (maximumDate.value !== undefined &&
            current.compare(maximumDate.value) > 0)
    );
});
</script>

<template>
    <input
        v-if="name"
        type="hidden"
        :name="name"
        :value="modelValue"
    />

    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                :disabled="disabled"
                :aria-invalid="ariaInvalid"
                :aria-required="required"
                :class="
                    cn(
                        'w-full justify-start text-left font-normal',
                        modelValue === '' && 'text-muted-foreground',
                        props.class,
                    )
                "
            >
                <CalendarIcon data-icon="inline-start" />
                {{ formattedDate }}
            </Button>
        </PopoverTrigger>
        <PopoverContent align="start" class="w-auto p-0">
            <Calendar
                :model-value="selectedDate"
                :min-value="minimumDate"
                :max-value="maximumDate"
                :week-starts-on="1"
                locale="cs-CZ"
                layout="month-and-year"
                initial-focus
                @update:model-value="selectDate"
            />
            <div class="flex items-center justify-between gap-2 border-t p-2">
                <Button
                    v-if="showToday"
                    type="button"
                    size="sm"
                    variant="ghost"
                    :disabled="todayIsDisabled"
                    @click="selectToday"
                >
                    Dnes
                </Button>
                <Button
                    v-if="showClear && modelValue !== ''"
                    type="button"
                    size="sm"
                    variant="ghost"
                    class="ml-auto"
                    @click="clear"
                >
                    Vymazat
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>

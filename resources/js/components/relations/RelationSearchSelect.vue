<script setup lang="ts" generic="TOption extends RelationSearchOption">
import { CheckIcon, ChevronsUpDownIcon, PlusIcon } from '@lucide/vue';
import { computed, shallowRef } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { useRelationSearch } from '@/composables/useRelationSearch';
import type { RelationSearchOption } from '@/composables/useRelationSearch';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        id: string;
        modelValue: string;
        endpoint: string;
        initialOptions?: TOption[];
        name?: string;
        invalid?: boolean;
        disabled?: boolean;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyLabel?: string;
        createLabel?: string;
    }>(),
    {
        initialOptions: () => [],
        placeholder: 'Hledat a vybrat',
        searchPlaceholder: 'Hledat…',
        emptyLabel: 'Žádná položka nebyla nalezena.',
    },
);
const emit = defineEmits<{
    'update:modelValue': [value: string];
    selected: [option: TOption | null];
    create: [];
}>();
const open = shallowRef(false);
const search = useRelationSearch<TOption>({
    endpoint: () => props.endpoint,
    initialOptions: () => props.initialOptions,
});
const selectedOption = computed(() =>
    search.results.value.find(
        (option) => String(option.id) === props.modelValue,
    ),
);
const createValue = '__create_relation__';

const selectOption = (value: string): void => {
    if (value === createValue) {
        open.value = false;
        emit('create');

        return;
    }

    const option = search.results.value.find(
        (candidate) => String(candidate.id) === value,
    );
    emit('update:modelValue', value);
    emit('selected', option ? ({ ...option } as TOption) : null);
    open.value = false;
};

const updateOpen = (value: boolean): void => {
    open.value = value;

    if (value) {
        void search.ensureLoaded();
    }
};

defineExpose({ refresh: search.refresh });
</script>

<template>
    <input v-if="name" type="hidden" :name="name" :value="modelValue" />
    <Popover :open="open" @update:open="updateOpen">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                :aria-invalid="invalid"
                :disabled="disabled"
                class="w-full justify-between font-normal"
            >
                <span
                    :class="
                        cn(
                            'flex min-w-0 items-center gap-2 truncate',
                            !selectedOption && 'text-muted-foreground',
                        )
                    "
                >
                    <slot name="selected" :option="selectedOption">
                        {{ selectedOption?.name ?? placeholder }}
                    </slot>
                </span>
                <ChevronsUpDownIcon class="opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[var(--reka-popover-trigger-width)] p-0">
            <Command
                :model-value="modelValue"
                @update:model-value="selectOption(String($event))"
            >
                <CommandInput
                    v-model:search-term="search.query.value"
                    :placeholder="searchPlaceholder"
                />
                <CommandList>
                    <CommandEmpty v-if="!search.loading.value">
                        {{ emptyLabel }}
                    </CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="option in search.results.value"
                            :key="option.id"
                            :value="String(option.id)"
                        >
                            <CheckIcon
                                :class="
                                    cn(
                                        'opacity-0',
                                        modelValue === String(option.id) &&
                                            'opacity-100',
                                    )
                                "
                            />
                            <slot name="option" :option="option">
                                {{ option.name }}
                            </slot>
                        </CommandItem>
                    </CommandGroup>
                    <div
                        v-if="search.loading.value"
                        class="flex items-center justify-center gap-2 p-3 text-sm text-muted-foreground"
                        role="status"
                    >
                        <Spinner /> Načítání…
                    </div>
                    <Button
                        v-if="search.nextCursor.value"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="w-full"
                        :disabled="search.loading.value"
                        @click="search.loadMore"
                    >
                        Načíst další
                    </Button>
                    <CommandGroup v-if="createLabel" heading="Akce">
                        <CommandItem :value="createValue">
                            <PlusIcon />
                            {{ createLabel }}
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
                <Alert v-if="search.failed.value" variant="destructive">
                    <AlertDescription>
                        Položky se nepodařilo načíst. Zkuste to znovu.
                    </AlertDescription>
                </Alert>
            </Command>
        </PopoverContent>
    </Popover>
</template>

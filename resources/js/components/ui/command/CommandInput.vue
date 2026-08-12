<script setup lang="ts">
import type { ListboxFilterProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { SearchIcon } from '@lucide/vue';
import { reactiveOmit } from '@vueuse/core';
import { ListboxFilter, useForwardProps } from 'reka-ui';
import { watch } from 'vue';
import { cn } from '@/lib/utils';
import { useCommand } from '.';

defineOptions({ inheritAttrs: false });
const props = defineProps<
    ListboxFilterProps & {
        class?: HTMLAttributes['class'];
        searchTerm?: string;
    }
>();
const emit = defineEmits<{
    'update:searchTerm': [value: string];
}>();
const delegatedProps = reactiveOmit(props, 'class', 'searchTerm');
const forwardedProps = useForwardProps(delegatedProps);
const { filterState } = useCommand();

watch(
    () => props.searchTerm,
    (searchTerm) => {
        if (searchTerm !== undefined && searchTerm !== filterState.search) {
            filterState.search = searchTerm;
        }
    },
    { immediate: true },
);
watch(
    () => filterState.search,
    (searchTerm) => emit('update:searchTerm', searchTerm),
);
</script>

<template>
    <div class="flex h-9 items-center gap-2 border-b px-3">
        <SearchIcon class="size-4 shrink-0 opacity-50" />
        <ListboxFilter
            v-bind="{ ...forwardedProps, ...$attrs }"
            v-model="filterState.search"
            auto-focus
            data-slot="command-input"
            :class="
                cn(
                    'flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-hidden placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50',
                    props.class,
                )
            "
        />
    </div>
</template>

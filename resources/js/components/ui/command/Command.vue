<script setup lang="ts">
import type { ListboxRootEmits, ListboxRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ListboxRoot, useFilter, useForwardPropsEmits } from 'reka-ui';
import { reactive, ref, watch } from 'vue';
import { cn } from '@/lib/utils';
import { provideCommandContext } from '.';

const props = withDefaults(
    defineProps<ListboxRootProps & { class?: HTMLAttributes['class'] }>(),
    {
        modelValue: '',
        highlightOnHover: true,
    },
);
const emits = defineEmits<ListboxRootEmits>();
const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
const allItems = ref<Map<string, string>>(new Map());
const { contains } = useFilter({ sensitivity: 'base' });
const filterState = reactive({
    search: '',
    filtered: {
        count: 0,
        items: new Map<string, number>(),
    },
});

watch(
    () => filterState.search,
    (search) => {
        if (search === '') {
            filterState.filtered.count = allItems.value.size;

            return;
        }

        let itemCount = 0;

        for (const [id, value] of allItems.value) {
            const matches = contains(value, search);
            filterState.filtered.items.set(id, matches ? 1 : 0);
            itemCount += matches ? 1 : 0;
        }

        filterState.filtered.count = itemCount;
    },
);

provideCommandContext({ allItems, filterState });
</script>

<template>
    <ListboxRoot
        data-slot="command"
        v-bind="forwarded"
        :class="
            cn(
                'flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground',
                props.class,
            )
        "
    >
        <slot />
    </ListboxRoot>
</template>

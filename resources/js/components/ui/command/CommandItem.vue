<script setup lang="ts">
import type { ListboxItemEmits, ListboxItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit, useCurrentElement } from '@vueuse/core';
import { ListboxItem, useForwardPropsEmits, useId } from 'reka-ui';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { cn } from '@/lib/utils';
import { useCommand } from '.';

const props = defineProps<
    ListboxItemProps & { class?: HTMLAttributes['class'] }
>();
const emits = defineEmits<ListboxItemEmits>();
const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
const id = useId();
const { filterState, allItems } = useCommand();
const isVisible = computed(
    () =>
        filterState.search === '' ||
        (filterState.filtered.items.get(id) ?? 1) > 0,
);
const itemRef = ref();
const currentElement = useCurrentElement(itemRef);

onMounted(() => {
    if (currentElement.value instanceof HTMLElement) {
        allItems.value.set(
            id,
            currentElement.value.textContent ?? String(props.value ?? ''),
        );
    }
});
onUnmounted(() => allItems.value.delete(id));
</script>

<template>
    <ListboxItem
        v-if="isVisible"
        :id="id"
        ref="itemRef"
        v-bind="forwarded"
        data-slot="command-item"
        :class="
            cn(
                'relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4',
                props.class,
            )
        "
        @select="filterState.search = ''"
    >
        <slot />
    </ListboxItem>
</template>

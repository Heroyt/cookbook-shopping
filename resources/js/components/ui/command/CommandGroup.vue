<script setup lang="ts">
import type { ListboxGroupProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ListboxGroup, ListboxGroupLabel } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    ListboxGroupProps & {
        class?: HTMLAttributes['class'];
        heading?: string;
    }
>();
const delegatedProps = reactiveOmit(props, 'class', 'heading');
</script>

<template>
    <ListboxGroup
        v-bind="delegatedProps"
        data-slot="command-group"
        :class="cn('overflow-hidden p-1 text-foreground', props.class)"
    >
        <ListboxGroupLabel
            v-if="heading"
            class="px-2 py-1.5 text-xs font-medium text-muted-foreground"
        >
            {{ heading }}
        </ListboxGroupLabel>
        <slot />
    </ListboxGroup>
</template>

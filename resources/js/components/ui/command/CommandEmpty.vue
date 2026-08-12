<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { useCommand } from '.';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
const { filterState } = useCommand();
const isVisible = computed(
    () => filterState.search !== '' && filterState.filtered.count === 0,
);
</script>

<template>
    <div
        v-if="isVisible"
        data-slot="command-empty"
        :class="cn('py-6 text-center text-sm', props.class)"
    >
        <slot />
    </div>
</template>

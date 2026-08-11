<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import type { StoreSectionIconName } from '@/types';
import { storeSectionIconComponents } from './storeSectionIcons';

const props = defineProps<{
    name: StoreSectionIconName;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <component
        :is="storeSectionIconComponents[name]"
        aria-hidden="true"
        :class="cn('shrink-0', props.class)"
    />
</template>

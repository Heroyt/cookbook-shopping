<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        :class="className"
        v-bind="$attrs"
        fill="none"
        aria-hidden="true"
    >
        <path
            d="M14 23h36l-3 27H17l-3-27Z"
            stroke="currentColor"
            stroke-width="4"
            stroke-linejoin="round"
        />
        <path
            d="M22 23c0-6 4.5-10 10-10s10 4 10 10M11 23h42M25 36l5 5 10-11"
            stroke="currentColor"
            stroke-width="4"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>

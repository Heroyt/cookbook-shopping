<script setup lang="ts">
import { ImageIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        imageUrl: string | null;
        imageAlt: string;
        variant?: 'default' | 'thumbnail' | 'card';
    }>(),
    { variant: 'default' },
);
</script>

<template>
    <div data-media-image-preview>
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :alt="imageAlt"
            class="w-full rounded-md border object-cover"
            :class="{
                'h-24': variant === 'default',
                'size-10 rounded-full': variant === 'thumbnail',
                'aspect-[3/4] h-auto': variant === 'card',
            }"
        />
        <div
            v-else
            class="flex items-center justify-center gap-2 rounded-md border border-dashed text-xs text-muted-foreground"
            :class="{
                'h-24': variant === 'default',
                'size-10 rounded-full': variant === 'thumbnail',
                'aspect-[3/4]': variant === 'card',
            }"
        >
            <ImageIcon aria-hidden="true" class="size-4" />
            <span v-if="variant !== 'thumbnail'"
                >Obrázek zatím není nahraný</span
            >
        </div>
    </div>
</template>

<script setup lang="ts">
import { ImageIcon } from '@lucide/vue';

defineProps<{
    imageUrl: string | null;
    imageAlt: string;
}>();
</script>

<template>
    <div data-media-image-preview>
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :alt="imageAlt"
            class="h-24 w-full rounded-md border object-cover"
        />
        <div
            v-else
            class="flex h-24 items-center justify-center gap-2 rounded-md border border-dashed text-xs text-muted-foreground"
        >
            <ImageIcon aria-hidden="true" class="size-4" />
            <span>Obrázek zatím není nahraný</span>
        </div>
    </div>
</template>

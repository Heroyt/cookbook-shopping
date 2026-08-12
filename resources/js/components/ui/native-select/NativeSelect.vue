<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue'
import type { SelectHTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: SelectHTMLAttributes['class']; modelValue?: string | number }>()
</script>

<template>
  <div data-slot="native-select-wrapper" class="relative w-fit">
    <select
      data-slot="native-select"
      :value="modelValue"
      :class="cn('border-input h-9 w-full appearance-none rounded-md border bg-transparent px-3 py-1 pr-8 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50', props.class)"
    ><slot /></select>
    <ChevronDownIcon data-slot="native-select-icon" class="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
  </div>
</template>

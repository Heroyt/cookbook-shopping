<script setup lang="ts">
defineProps<{ value?: string | number; selected?: boolean; disabled?: boolean }>()
</script>
<template><option :value="value" :selected="selected" :disabled="disabled"><slot /></option></template>

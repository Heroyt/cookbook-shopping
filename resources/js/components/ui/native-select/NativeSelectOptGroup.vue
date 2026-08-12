<script setup lang="ts">
defineProps<{ label?: string; disabled?: boolean }>()
</script>
<template><optgroup :label="label" :disabled="disabled"><slot /></optgroup></template>

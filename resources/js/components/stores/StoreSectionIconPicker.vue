<script setup lang="ts">
import StoreSectionIcon from '@/components/stores/StoreSectionIcon.vue';
import { storeSectionIconOptions } from '@/components/stores/storeSectionIcons';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
    FieldLegend,
    FieldSet,
    FieldTitle,
} from '@/components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import type { StoreSectionIconName } from '@/types';

withDefaults(
    defineProps<{
        defaultValue?: StoreSectionIconName;
        error?: string;
    }>(),
    { defaultValue: 'package', error: undefined },
);
</script>

<template>
    <FieldSet :data-invalid="Boolean(error)" class="gap-3">
        <div>
            <FieldLegend variant="label">Ikona části obchodu</FieldLegend>
            <FieldDescription>
                Vyberte jednoduchou SVG ikonu z připraveného balíčku.
            </FieldDescription>
        </div>
        <RadioGroup
            name="icon"
            :default-value="defaultValue"
            class="grid grid-cols-2 gap-2 sm:grid-cols-3"
        >
            <FieldLabel
                v-for="option in storeSectionIconOptions"
                :key="option.value"
                :for="`store-section-icon-${option.value}`"
                class="cursor-pointer"
            >
                <Field
                    orientation="horizontal"
                    class="rounded-lg border p-3 transition-colors [&:has([data-state=checked])]:border-primary [&:has([data-state=checked])]:bg-accent"
                >
                    <StoreSectionIcon :name="option.value" class="size-5" />
                    <FieldTitle class="flex-1">{{ option.label }}</FieldTitle>
                    <RadioGroupItem
                        :id="`store-section-icon-${option.value}`"
                        :value="option.value"
                        :aria-invalid="Boolean(error)"
                    />
                </Field>
            </FieldLabel>
        </RadioGroup>
        <FieldError :errors="[error]" />
    </FieldSet>
</template>

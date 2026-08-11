<script setup lang="ts">
import StoreSectionIconPicker from '@/components/stores/StoreSectionIconPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { StoreSectionIconName } from '@/types';

const props = withDefaults(
    defineProps<{
        defaultColour?: string;
        defaultIcon?: StoreSectionIconName;
        defaultName?: string;
        errors?: Partial<Record<'name' | 'colour' | 'icon', string>>;
        idPrefix?: string;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        defaultColour: '#2F855A',
        defaultIcon: 'package',
        defaultName: '',
        errors: () => ({}),
        idPrefix: 'store-section',
        processing: false,
        submitLabel: 'Vytvořit část obchodu',
    },
);

const fieldId = (field: string): string => `${props.idPrefix}-${field}`;
</script>

<template>
    <FieldGroup>
        <Field :data-invalid="Boolean(errors.name)">
            <FieldLabel :for="fieldId('name')">
                Název části obchodu
            </FieldLabel>
            <Input
                :id="fieldId('name')"
                name="name"
                :default-value="defaultName"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="Čerstvá zelenina"
                :aria-invalid="Boolean(errors.name)"
            />
            <FieldDescription>
                Název musí být v aktuální rodině jedinečný.
            </FieldDescription>
            <FieldError :errors="[errors.name]" />
        </Field>

        <Field :data-invalid="Boolean(errors.colour)">
            <FieldLabel :for="fieldId('colour')">
                Barva části obchodu
            </FieldLabel>
            <Input
                :id="fieldId('colour')"
                class="max-w-24 cursor-pointer"
                name="colour"
                type="color"
                :default-value="defaultColour"
                required
                :aria-invalid="Boolean(errors.colour)"
            />
            <FieldDescription>
                Vyberte barvu. Kód musí mít tvar #RRGGBB.
            </FieldDescription>
            <FieldError :errors="[errors.colour]" />
        </Field>

        <StoreSectionIconPicker
            :default-value="defaultIcon"
            :error="errors.icon"
            :id-prefix="`${idPrefix}-icon`"
        />

        <Field orientation="horizontal">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" data-icon="inline-start" />
                {{ submitLabel }}
            </Button>
        </Field>
    </FieldGroup>
</template>

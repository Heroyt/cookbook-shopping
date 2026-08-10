<script setup lang="ts">
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

withDefaults(
    defineProps<{
        errors?: Partial<Record<'name' | 'colour', string>>;
        processing?: boolean;
    }>(),
    {
        errors: () => ({}),
        processing: false,
    },
);
</script>

<template>
    <FieldGroup>
        <Field :data-invalid="Boolean(errors.name)">
            <FieldLabel for="store-section-name">
                Název části obchodu
            </FieldLabel>
            <Input
                id="store-section-name"
                name="name"
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
            <FieldLabel for="store-section-colour">
                Barva části obchodu
            </FieldLabel>
            <Input
                id="store-section-colour"
                class="max-w-24 cursor-pointer"
                name="colour"
                type="color"
                value="#2F855A"
                required
                :aria-invalid="Boolean(errors.colour)"
            />
            <FieldDescription>
                Vyberte barvu. Kód musí mít tvar #RRGGBB.
            </FieldDescription>
            <FieldError :errors="[errors.colour]" />
        </Field>

        <Field orientation="horizontal">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" data-icon="inline-start" />
                Vytvořit část obchodu
            </Button>
        </Field>
    </FieldGroup>
</template>

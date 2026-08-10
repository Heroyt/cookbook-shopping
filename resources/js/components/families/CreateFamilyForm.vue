<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import FamilyController from '@/actions/App/FamilyAccess/Http/Controllers/FamilyController';
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
</script>

<template>
    <Form
        v-bind="FamilyController.store.form()"
        v-slot="{ errors, processing }"
    >
        <FieldGroup>
            <Field :data-invalid="Boolean(errors.name)">
                <FieldLabel for="family-name">Název rodiny</FieldLabel>
                <Input
                    id="family-name"
                    name="name"
                    required
                    maxlength="255"
                    autocomplete="off"
                    autofocus
                    placeholder="Víkendová kuchyně"
                    :aria-invalid="Boolean(errors.name)"
                />
                <FieldDescription>
                    Pod tímto názvem členové společný prostor poznají.
                </FieldDescription>
                <FieldError :errors="[errors.name]" />
            </Field>

            <Field orientation="horizontal">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" data-icon="inline-start" />
                    Vytvořit rodinu
                </Button>
            </Field>
        </FieldGroup>
    </Form>
</template>

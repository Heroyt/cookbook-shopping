<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import StoreController from '@/actions/App/Cookbook/Http/Controllers/StoreController';
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
import type { IngredientStoreOption } from '@/types';

withDefaults(defineProps<{ layered?: boolean }>(), { layered: false });
const emit = defineEmits<{ success: [store?: IngredientStoreOption] }>();
const page = usePage();
</script>

<template>
    <Form
        v-bind="StoreController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        @success="emit('success', page.flash.createdStore)"
    >
        <input v-if="layered" type="hidden" name="layered" value="1" />
        <FieldGroup>
            <Field :data-invalid="Boolean(errors.name)">
                <FieldLabel for="store-name">Název obchodu</FieldLabel>
                <Input
                    id="store-name"
                    name="name"
                    required
                    maxlength="255"
                    autocomplete="off"
                    placeholder="Farmářský trh"
                    :aria-invalid="Boolean(errors.name)"
                />
                <FieldDescription>
                    Názvy obchodů musí být v aktuální rodině jedinečné.
                </FieldDescription>
                <FieldError :errors="[errors.name]" />
            </Field>

            <Field orientation="horizontal">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" data-icon="inline-start" />
                    Vytvořit obchod
                </Button>
            </Field>
        </FieldGroup>
    </Form>
</template>

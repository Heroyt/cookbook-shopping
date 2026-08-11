<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Potvrzení hesla',
        description:
            'Pro pokračování v citlivé operaci znovu zadejte své heslo',
    },
});
</script>

<template>
    <Head title="Potvrzení hesla" />

    <Form
        v-bind="store.form()"
        :reset-on-error="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <FieldGroup>
            <Field :data-invalid="Boolean(errors.password)">
                <FieldLabel for="password">Heslo</FieldLabel>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    placeholder="Heslo"
                    :aria-invalid="Boolean(errors.password)"
                />
                <FieldError :errors="[errors.password]" />
            </Field>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="confirm-password-button"
            >
                <Spinner v-if="processing" />
                Potvrdit heslo
            </Button>
        </FieldGroup>
    </Form>
</template>

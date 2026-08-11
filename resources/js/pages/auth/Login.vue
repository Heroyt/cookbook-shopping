<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Přihlášení k účtu',
        description: 'Zadejte svůj e-mail a heslo',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Přihlášení" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <FieldGroup>
            <Field :data-invalid="Boolean(errors.email)">
                <FieldLabel for="email">E-mailová adresa</FieldLabel>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                    :aria-invalid="Boolean(errors.email)"
                />
                <FieldError :errors="[errors.email]" />
            </Field>

            <Field :data-invalid="Boolean(errors.password)">
                <div class="flex items-center justify-between">
                    <FieldLabel for="password">Heslo</FieldLabel>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Zapomněli jste heslo?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Heslo"
                    :aria-invalid="Boolean(errors.password)"
                />
                <FieldError :errors="[errors.password]" />
            </Field>

            <Field orientation="horizontal">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <FieldLabel for="remember">Zapamatovat si mě</FieldLabel>
            </Field>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Přihlásit se
            </Button>
        </FieldGroup>
    </Form>
</template>

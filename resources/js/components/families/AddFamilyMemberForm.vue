<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import FamilyMemberController from '@/actions/App/FamilyAccess/Http/Controllers/FamilyMemberController';
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
        v-bind="FamilyMemberController.store.form()"
        reset-on-success
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
    >
        <FieldGroup>
            <Field :data-invalid="Boolean(errors.email)">
                <FieldLabel for="member-email">User email</FieldLabel>
                <Input
                    id="member-email"
                    name="email"
                    type="email"
                    required
                    maxlength="255"
                    autocomplete="email"
                    placeholder="member@example.com"
                    :aria-invalid="Boolean(errors.email)"
                />
                <FieldDescription>
                    The User must already have an account created by an
                    operator.
                </FieldDescription>
                <FieldError :errors="[errors.email]" />
            </Field>

            <Field orientation="horizontal">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" data-icon="inline-start" />
                    Add member
                </Button>
            </Field>
        </FieldGroup>
    </Form>
</template>

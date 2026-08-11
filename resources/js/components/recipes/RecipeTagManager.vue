<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import RecipeTagController from '@/actions/App/Cookbook/Http/Controllers/RecipeTagController';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { RecipeTagOption } from '@/types';

defineProps<{ tags: RecipeTagOption[] }>();
</script>

<template>
    <Form
        v-bind="RecipeTagController.store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        class="flex items-end gap-2"
    >
        <Field class="flex-1" :data-invalid="Boolean(errors.name)"
            ><FieldLabel for="recipe-tag-name">Nový štítek</FieldLabel
            ><Input
                id="recipe-tag-name"
                name="name"
                required
                :aria-invalid="Boolean(errors.name)" /><FieldError
                :errors="[errors.name]"
        /></Field>
        <Button type="submit" variant="outline" :disabled="processing"
            >Přidat</Button
        >
    </Form>
    <ul v-if="tags.length" class="mt-3 space-y-2" aria-label="Štítky receptů">
        <li
            v-for="tag in tags"
            :key="tag.id"
            class="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
        >
            <span>{{ tag.name }}</span>
            <Form
                v-bind="RecipeTagController.destroy.form(tag.id)"
                v-slot="{ processing }"
                ><Button
                    type="submit"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="processing"
                    :aria-label="`Smazat štítek ${tag.name}`"
                    ><Trash2Icon /></Button
            ></Form>
        </li>
    </ul>
</template>

<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PaletteIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { IngredientStoreSectionOption } from '@/types';

defineProps<{
    storeSection: IngredientStoreSectionOption;
}>();

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button type="button" variant="outline" size="sm">
                <PaletteIcon data-icon="inline-start" />
                Změnit barvu části
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Změnit barvu: {{ storeSection.name }}</DialogTitle>
                <DialogDescription>
                    Změna se projeví u všech surovin přiřazených k této části.
                </DialogDescription>
            </DialogHeader>
            <Form
                v-bind="
                    StoreSectionController.updateColour.form(storeSection.id)
                "
                :options="{ preserveScroll: true, preserveState: true }"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <Field :data-invalid="Boolean(errors.colour)">
                    <FieldLabel
                        :for="`ingredient-section-colour-${storeSection.id}`"
                    >
                        Barva části obchodu
                    </FieldLabel>
                    <Input
                        :id="`ingredient-section-colour-${storeSection.id}`"
                        class="max-w-24 cursor-pointer"
                        name="colour"
                        type="color"
                        :default-value="storeSection.colour"
                        required
                        :aria-invalid="Boolean(errors.colour)"
                    />
                    <FieldError :errors="[errors.colour]" />
                </Field>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" data-icon="inline-start" />
                    Uložit barvu
                </Button>
            </Form>
        </DialogContent>
    </Dialog>
</template>

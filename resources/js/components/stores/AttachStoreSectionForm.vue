<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PlusIcon } from '@lucide/vue';
import StoreSectionAssociationController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionAssociationController';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { StoreSectionSummary } from '@/types';

defineProps<{
    storeId: number;
    availableSections: StoreSectionSummary[];
}>();
</script>

<template>
    <Form
        v-bind="StoreSectionAssociationController.store.form(storeId)"
        :options="{ preserveScroll: true }"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <FieldGroup class="gap-3">
            <Field :data-invalid="Boolean(errors.store_section_id)">
                <FieldLabel :for="`store-${storeId}-section`">
                    Přidat část obchodu
                </FieldLabel>
                <Select
                    name="store_section_id"
                    required
                    :disabled="availableSections.length === 0 || processing"
                >
                    <SelectTrigger
                        :id="`store-${storeId}-section`"
                        class="w-full"
                        :aria-invalid="Boolean(errors.store_section_id)"
                    >
                        <SelectValue placeholder="Vyberte část obchodu" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem
                                v-for="storeSection in availableSections"
                                :key="storeSection.id"
                                :value="String(storeSection.id)"
                            >
                                {{ storeSection.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <FieldDescription>
                    Nová část se přidá na konec pořadí tohoto obchodu.
                </FieldDescription>
                <FieldError :errors="[errors.store_section_id]" />
            </Field>

            <Button
                type="submit"
                size="sm"
                :disabled="availableSections.length === 0 || processing"
            >
                <Spinner v-if="processing" data-icon="inline-start" />
                <PlusIcon v-else data-icon="inline-start" />
                Přidat k obchodu
            </Button>
        </FieldGroup>
    </Form>
</template>

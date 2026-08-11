<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { SaveIcon } from '@lucide/vue';
import { computed } from 'vue';
import {
    storeCalendar,
    storeSimplePlan,
} from '@/actions/App/MealPlanning/Http/Controllers/SavedShoppingListSourceController';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{ source: 'simple-plan' | 'calendar' }>();
const form = computed(() =>
    props.source === 'calendar' ? storeCalendar.form() : storeSimplePlan.form(),
);
</script>

<template>
    <div class="flex flex-col items-start gap-2 sm:items-end">
        <Form v-bind="form" v-slot="{ errors, processing }" preserve-scroll>
            <Button type="submit" :disabled="processing">
                <Spinner
                    v-if="processing"
                    data-icon="inline-start"
                    aria-hidden="true"
                />
                <SaveIcon v-else data-icon="inline-start" />
                Uložit do historie
            </Button>
            <FieldError :errors="[errors.snapshot]" />
        </Form>
        <p class="max-w-sm text-sm text-muted-foreground sm:text-right">
            Každé uložení vytvoří nový záznam. Tento výsledek zůstává do té doby
            pouze dočasný.
        </p>
    </div>
</template>

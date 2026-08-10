<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Link2OffIcon, LinkIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import IngredientAlternativeController from '@/actions/App/Cookbook/Http/Controllers/IngredientAlternativeController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { IngredientSummary } from '@/types';

const { ingredient } = defineProps<{ ingredient: IngredientSummary }>();
const removingId = shallowRef<number | null>(null);

const removeAlternative = (alternativeId: number): void => {
    removingId.value = alternativeId;
    router.delete(
        IngredientAlternativeController.destroy([ingredient.id, alternativeId])
            .url,
        {
            preserveScroll: true,
            onFinish: () => {
                removingId.value = null;
            },
        },
    );
};
</script>

<template>
    <div class="space-y-3">
        <div v-if="ingredient.alternatives.length" class="flex flex-wrap gap-2">
            <Badge
                v-for="alternative in ingredient.alternatives"
                :key="alternative.id"
                variant="secondary"
                class="gap-1"
            >
                {{ alternative.name }}
                <span v-if="alternative.archived">(archivovaná)</span>
                <Button
                    v-if="!ingredient.archived"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`Odebrat alternativu ${alternative.name}`"
                    :disabled="removingId === alternative.id"
                    @click="removeAlternative(alternative.id)"
                >
                    <Spinner
                        v-if="removingId === alternative.id"
                    /><Link2OffIcon v-else />
                </Button>
            </Badge>
        </div>
        <span v-else class="text-sm text-muted-foreground">Bez alternativ</span>
        <Form
            v-if="!ingredient.archived && ingredient.alternativeOptions.length"
            v-bind="IngredientAlternativeController.store.form(ingredient.id)"
            :options="{ preserveScroll: true }"
            reset-on-success
            class="flex items-end gap-2"
            v-slot="{ errors, processing }"
        >
            <Field :data-invalid="Boolean(errors.alternative_id)">
                <FieldLabel :for="`ingredient-${ingredient.id}-alternative`"
                    >Přidat přímou alternativu</FieldLabel
                >
                <Select name="alternative_id" required>
                    <SelectTrigger
                        :id="`ingredient-${ingredient.id}-alternative`"
                        :aria-invalid="Boolean(errors.alternative_id)"
                    >
                        <SelectValue placeholder="Vyberte surovinu" />
                    </SelectTrigger>
                    <SelectContent
                        ><SelectGroup>
                            <SelectItem
                                v-for="option in ingredient.alternativeOptions"
                                :key="option.id"
                                :value="String(option.id)"
                                >{{ option.name }}</SelectItem
                            >
                        </SelectGroup></SelectContent
                    >
                </Select>
                <FieldError :errors="[errors.alternative_id]" />
            </Field>
            <Button type="submit" size="sm" :disabled="processing"
                ><Spinner v-if="processing" /><LinkIcon
                    v-else
                />Propojit</Button
            >
        </Form>
    </div>
</template>

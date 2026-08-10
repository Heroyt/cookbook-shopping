<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PencilIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import IngredientController from '@/actions/App/Cookbook/Http/Controllers/IngredientController';
import IngredientFormFields from '@/components/ingredients/IngredientFormFields.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { IngredientSummary } from '@/types';

defineProps<{ ingredient: IngredientSummary }>();

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline" size="sm">
                <PencilIcon data-icon="inline-start" />
                Upravit
            </Button>
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <Form
                v-bind="IngredientController.update.form(ingredient.id)"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle
                        >Upravit surovinu {{ ingredient.name }}</DialogTitle
                    >
                    <DialogDescription>
                        Změňte údaje konkrétního balení v aktuální rodině.
                    </DialogDescription>
                </DialogHeader>

                <IngredientFormFields
                    :ingredient="ingredient"
                    :errors="errors"
                    :processing="processing"
                    :id-prefix="`ingredient-${ingredient.id}-edit`"
                    :show-submit="false"
                />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Zrušit</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Uložit surovinu
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>

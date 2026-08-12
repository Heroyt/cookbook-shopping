<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PencilIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import IngredientController from '@/actions/App/Cookbook/Http/Controllers/IngredientController';
import IngredientAlternatives from '@/components/ingredients/IngredientAlternatives.vue';
import IngredientFormFields from '@/components/ingredients/IngredientFormFields.vue';
import EntityImageUpload from '@/components/media/EntityImageUpload.vue';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import type {
    IngredientAlternativeOption,
    IngredientPlacementStore,
    IngredientSummary,
} from '@/types';

const props = withDefaults(
    defineProps<{
        ingredient: IngredientSummary;
        stores: IngredientPlacementStore[];
        alternativeOptions?: IngredientAlternativeOption[];
        openInitially?: boolean;
    }>(),
    { openInitially: false, alternativeOptions: () => [] },
);

const open = shallowRef(props.openInitially);
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
            <DialogHeader>
                <DialogTitle>
                    Upravit surovinu {{ ingredient.name }}
                </DialogTitle>
                <DialogDescription>
                    Změňte údaje konkrétního balení v aktuální rodině.
                </DialogDescription>
            </DialogHeader>

            <section
                class="flex flex-col gap-3"
                :aria-labelledby="`ingredient-${ingredient.id}-photo`"
            >
                <h3
                    :id="`ingredient-${ingredient.id}-photo`"
                    class="font-medium"
                >
                    Fotografie suroviny
                </h3>
                <EntityImageUpload
                    media-type="ingredient-photo"
                    :entity-id="ingredient.id"
                    :image-url="ingredient.photoUrl"
                    :image-alt="`Fotografie suroviny ${ingredient.name}`"
                />
            </section>

            <Separator />

            <section class="flex flex-col gap-3">
                <h3 class="font-medium">Alternativy</h3>
                <IngredientAlternatives
                    :ingredient="ingredient"
                    :alternative-options="alternativeOptions"
                />
            </section>

            <Separator />

            <Form
                v-bind="IngredientController.update.form(ingredient.id)"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <IngredientFormFields
                    :ingredient="ingredient"
                    :stores="stores"
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

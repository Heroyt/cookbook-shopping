<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Settings2Icon } from '@lucide/vue';
import StoreController from '@/actions/App/Cookbook/Http/Controllers/StoreController';
import EntityImageUpload from '@/components/media/EntityImageUpload.vue';
import DeleteStoreAlertDialog from '@/components/stores/DeleteStoreAlertDialog.vue';
import StoreSectionOrderManager from '@/components/stores/StoreSectionOrderManager.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import type { StoreSectionSummary, StoreSummary } from '@/types';

defineProps<{
    store: StoreSummary;
    storeSections: StoreSectionSummary[];
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="outline" size="sm">
                <Settings2Icon data-icon="inline-start" /> Spravovat
            </Button>
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Spravovat obchod {{ store.name }}</DialogTitle>
                <DialogDescription>
                    Upravte název, logo a pořadí částí obchodu.
                </DialogDescription>
            </DialogHeader>

            <section class="flex flex-col gap-3" aria-labelledby="store-logo">
                <h3 id="store-logo" class="font-medium">Logo obchodu</h3>
                <EntityImageUpload
                    media-type="store-logo"
                    :entity-id="store.id"
                    :image-url="store.logoUrl"
                    :image-alt="`Logo obchodu ${store.name}`"
                />
            </section>

            <Separator />

            <Form
                v-bind="StoreController.update.form(store.id)"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-4"
                v-slot="{ errors, processing }"
            >
                <FieldGroup>
                    <Field :data-invalid="Boolean(errors.name)">
                        <FieldLabel :for="`store-${store.id}-name`">
                            Název obchodu
                        </FieldLabel>
                        <Input
                            :id="`store-${store.id}-name`"
                            name="name"
                            required
                            maxlength="255"
                            autocomplete="off"
                            :default-value="store.name"
                            :aria-invalid="Boolean(errors.name)"
                        />
                        <FieldDescription>
                            Názvy obchodů musí být v aktuální rodině jedinečné.
                        </FieldDescription>
                        <FieldError :errors="[errors.name]" />
                    </Field>
                </FieldGroup>
                <div>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Uložit název
                    </Button>
                </div>
            </Form>

            <Separator />

            <section
                class="flex flex-col gap-3"
                aria-labelledby="store-sections"
            >
                <div>
                    <h3 id="store-sections" class="font-medium">
                        Části obchodu
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Přiřaďte části a upravte jejich pořadí pro nákupní
                        seznam.
                    </p>
                </div>
                <StoreSectionOrderManager
                    :store="store"
                    :store-sections="storeSections"
                />
            </section>

            <Separator />

            <section class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-medium">Smazat obchod</h3>
                    <p class="text-sm text-muted-foreground">
                        Obchod bude z aktuální rodiny trvale odstraněn.
                    </p>
                </div>
                <DeleteStoreAlertDialog :store="store" />
            </section>
        </DialogContent>
    </Dialog>
</template>

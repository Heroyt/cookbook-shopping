<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Settings2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
import DeleteStoreSectionAlertDialog from '@/components/stores/DeleteStoreSectionAlertDialog.vue';
import StoreSectionFormFields from '@/components/stores/StoreSectionFormFields.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import type { StoreSectionSummary } from '@/types';

defineProps<{ storeSection: StoreSectionSummary }>();

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline" size="sm">
                <Settings2Icon data-icon="inline-start" /> Spravovat
            </Button>
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    Spravovat část {{ storeSection.name }}
                </DialogTitle>
                <DialogDescription>
                    Změňte název, barvu nebo ikonu této části obchodu.
                </DialogDescription>
            </DialogHeader>
            <Form
                v-bind="StoreSectionController.update.form(storeSection.id)"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <StoreSectionFormFields
                    :default-name="storeSection.name"
                    :default-colour="storeSection.colour"
                    :default-icon="storeSection.icon"
                    :errors="errors"
                    :processing="processing"
                    :id-prefix="`edit-store-section-${storeSection.id}`"
                    submit-label="Uložit změny"
                />
            </Form>
            <Separator />
            <section class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-medium">Smazat část obchodu</h3>
                    <p class="text-sm text-muted-foreground">
                        Před smazáním uvidíte počet dotčených přiřazení.
                    </p>
                </div>
                <DeleteStoreSectionAlertDialog :store-section="storeSection" />
            </section>
        </DialogContent>
    </Dialog>
</template>

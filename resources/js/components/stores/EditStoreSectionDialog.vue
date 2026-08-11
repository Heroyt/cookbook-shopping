<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Settings2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreSectionController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionController';
import DeleteStoreSectionAlertDialog from '@/components/stores/DeleteStoreSectionAlertDialog.vue';
import StoreSectionIconPicker from '@/components/stores/StoreSectionIconPicker.vue';
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
import { Spinner } from '@/components/ui/spinner';
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
                    Zvolte ikonu, která část obchodu rychle odliší.
                </DialogDescription>
            </DialogHeader>
            <Form
                v-bind="StoreSectionController.updateIcon.form(storeSection.id)"
                class="flex flex-col gap-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <StoreSectionIconPicker
                    :default-value="storeSection.icon"
                    :error="errors.icon"
                />
                <div>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Uložit ikonu
                    </Button>
                </div>
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

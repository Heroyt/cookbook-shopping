<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PencilIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreController from '@/actions/App/Cookbook/Http/Controllers/StoreController';
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
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { StoreSummary } from '@/types';

defineProps<{ store: StoreSummary }>();

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline" size="sm">
                <PencilIcon data-icon="inline-start" />
                Přejmenovat
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="StoreController.update.form(store.id)"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle
                        >Přejmenovat obchod {{ store.name }}</DialogTitle
                    >
                    <DialogDescription>
                        Názvy obchodů musí být v aktuální rodině jedinečné.
                    </DialogDescription>
                </DialogHeader>

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
                            Při uložení se opakované mezery automaticky upraví.
                        </FieldDescription>
                        <FieldError :errors="[errors.name]" />
                    </Field>
                </FieldGroup>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Zrušit
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" data-icon="inline-start" />
                        Přejmenovat obchod
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>

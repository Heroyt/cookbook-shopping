<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import FamilyController from '@/actions/App/FamilyAccess/Http/Controllers/FamilyController';
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
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';

defineProps<{ familyName: string }>();

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="destructive">Smazat rodinu</Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="FamilyController.destroy.form()"
                reset-on-success
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>Smazat rodinu {{ familyName }}?</DialogTitle>
                    <DialogDescription>
                        Rodina a všechna její data budou trvale smazány. Tuto
                        akci nelze vrátit zpět.
                    </DialogDescription>
                </DialogHeader>

                <Field :data-invalid="Boolean(errors.family_name)">
                    <FieldLabel for="confirmed-family-name">
                        Potvrďte zadáním názvu {{ familyName }}
                    </FieldLabel>
                    <Input
                        id="confirmed-family-name"
                        name="family_name"
                        required
                        maxlength="255"
                        autocomplete="off"
                        :aria-invalid="Boolean(errors.family_name)"
                    />
                    <FieldDescription>
                        Název musí přesně odpovídat včetně velikosti písmen.
                    </FieldDescription>
                    <FieldError :errors="[errors.family_name]" />
                </Field>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Zrušit
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        Smazat rodinu
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>

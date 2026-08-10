<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { CircleAlertIcon } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Smazání účtu"
            description="Smažte svůj účet a členství v rodinách"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Upozornění</p>
                <p class="text-sm">
                    Pokračujte opatrně. Tuto akci nelze vrátit zpět.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button"
                        >Smazat účet</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >Opravdu chcete smazat svůj účet?</DialogTitle
                            >
                            <DialogDescription>
                                Váš účet a členství v rodinách budou trvale
                                smazány. Sdíleným rodinám zůstanou ostatní
                                členové i data. Účet nelze smazat, pokud jste
                                posledním členem některé rodiny. Ve správě
                                rodiny nejprve přidejte dalšího člena nebo
                                rodinu smažte. Potvrďte akci zadáním hesla.
                            </DialogDescription>
                        </DialogHeader>

                        <Alert v-if="errors.account" variant="destructive">
                            <CircleAlertIcon />
                            <AlertTitle
                                >Nejprve vyřešte členství v rodině</AlertTitle
                            >
                            <AlertDescription>
                                {{ errors.account }}
                            </AlertDescription>
                        </Alert>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Heslo</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Heslo"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Zrušit
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Smazat účet
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>

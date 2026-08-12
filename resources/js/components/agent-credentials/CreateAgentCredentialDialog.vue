<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { KeyRoundIcon, PlusIcon } from '@lucide/vue';
import { computed, ref, shallowRef, watch } from 'vue';
import AgentCredentialController from '@/actions/App/AgentIntegration/Http/Controllers/AgentCredentialController';
import AppDatePicker from '@/components/date/AppDatePicker.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { AgentCredentialAbility } from '@/types';

const { passwordConfirmed } = defineProps<{ passwordConfirmed: boolean }>();
const open = shallowRef(false);
const validityPreset = ref('90');
const form = useForm<{
    name: string;
    abilities: AgentCredentialAbility[];
    validity_days: number | null;
    expires_at: string;
}>({
    name: '',
    abilities: [],
    validity_days: 90,
    expires_at: '',
});

const validityOptions = [
    { value: '1', label: '1 den' },
    { value: '7', label: '7 dní' },
    { value: '30', label: '30 dní' },
    { value: '90', label: '90 dní' },
    { value: '180', label: '180 dní' },
    { value: '365', label: '1 rok' },
    { value: 'custom', label: 'Vlastní datum' },
] as const;

const dateString = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const minimumCustomDate = computed(() => dateString(new Date()));
const maximumCustomDate = computed(() => {
    const maximum = new Date();
    maximum.setDate(maximum.getDate() + 364);

    return dateString(maximum);
});

watch(validityPreset, (preset) => {
    form.clearErrors('validity_days', 'expires_at');
    form.expires_at = '';
    form.validity_days = preset === 'custom' ? null : Number(preset);
});

const abilityOptions: Array<{
    value: Exclude<AgentCredentialAbility, 'content:read'>;
    label: string;
    description: string;
}> = [
    {
        value: 'cookbook:write',
        label: 'Úpravy kuchařky',
        description: 'Vytváření a úpravy surovin, obchodů a receptů.',
    },
    {
        value: 'planning:write',
        label: 'Úpravy plánování',
        description: 'Vytváření a úpravy záznamů v kalendáři.',
    },
    {
        value: 'destructive:write',
        label: 'Destruktivní změny',
        description: 'Archivace, obnovení a mazání podporovaných položek.',
    },
];

const toggleAbility = (
    ability: AgentCredentialAbility,
    checked: boolean,
): void => {
    form.abilities = checked
        ? [...form.abilities, ability]
        : form.abilities.filter((item) => item !== ability);
};

const submit = (): void => {
    form.post(AgentCredentialController.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            validityPreset.value = '90';
        },
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button><PlusIcon data-icon="inline-start" /> Nový přístup</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Vytvořit přístup pro agenta</DialogTitle>
                <DialogDescription>
                    Přístup bude trvale omezený na aktuální rodinu. Čtení obsahu
                    je součástí každého přístupu.
                </DialogDescription>
            </DialogHeader>

            <div v-if="!passwordConfirmed" class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Před vytvořením přístupu potvrďte své heslo. Po ověření se
                    vrátíte na tuto stránku.
                </p>
                <Button as-child>
                    <Link :href="AgentCredentialController.confirmed()">
                        <KeyRoundIcon data-icon="inline-start" /> Potvrdit heslo
                    </Link>
                </Button>
            </div>

            <form v-else @submit.prevent="submit">
                <FieldGroup>
                    <Field :data-invalid="Boolean(form.errors.name)">
                        <FieldLabel for="agent-credential-name"
                            >Název přístupu</FieldLabel
                        >
                        <Input
                            id="agent-credential-name"
                            v-model="form.name"
                            required
                            maxlength="255"
                            autocomplete="off"
                            placeholder="Kuchyňský pomocník"
                            :aria-invalid="Boolean(form.errors.name)"
                        />
                        <FieldDescription>
                            Název pomůže rodině poznat, který agent přístup
                            používá.
                        </FieldDescription>
                        <FieldError :errors="[form.errors.name]" />
                    </Field>

                    <fieldset class="space-y-3">
                        <legend class="text-sm font-medium">Oprávnění</legend>
                        <label
                            v-for="option in abilityOptions"
                            :key="option.value"
                            class="flex items-start gap-3 rounded-md border p-3"
                        >
                            <Checkbox
                                :model-value="
                                    form.abilities.includes(option.value)
                                "
                                :aria-label="option.label"
                                @update:model-value="
                                    (checked) =>
                                        toggleAbility(
                                            option.value,
                                            checked === true,
                                        )
                                "
                            />
                            <span class="grid gap-1 text-sm">
                                <span class="font-medium">{{
                                    option.label
                                }}</span>
                                <span class="text-muted-foreground">{{
                                    option.description
                                }}</span>
                            </span>
                        </label>
                        <FieldError :errors="[form.errors.abilities]" />
                    </fieldset>

                    <Field
                        :data-invalid="
                            Boolean(
                                form.errors.validity_days ||
                                form.errors.expires_at,
                            )
                        "
                    >
                        <FieldLabel for="agent-credential-validity"
                            >Délka platnosti</FieldLabel
                        >
                        <Select v-model="validityPreset">
                            <SelectTrigger
                                id="agent-credential-validity"
                                class="w-full"
                                :aria-invalid="
                                    Boolean(form.errors.validity_days)
                                "
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in validityOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="validityPreset === 'custom'">
                            <FieldLabel for="agent-credential-expiry"
                                >Platný do data včetně</FieldLabel
                            >
                            <AppDatePicker
                                id="agent-credential-expiry"
                                v-model="form.expires_at"
                                name="expires_at"
                                :min="minimumCustomDate"
                                :max="maximumCustomDate"
                                required
                                :aria-invalid="Boolean(form.errors.expires_at)"
                                :show-today="true"
                                :show-clear="false"
                            />
                        </template>
                        <FieldDescription>
                            Přednastavená délka se počítá přesně od vytvoření.
                            Vlastní datum platí až do konce vybraného dne.
                        </FieldDescription>
                        <FieldError
                            :errors="[
                                form.errors.validity_days,
                                form.errors.expires_at,
                            ]"
                        />
                    </Field>
                </FieldGroup>

                <DialogFooter class="mt-6">
                    <Button type="submit" :disabled="form.processing">
                        <Spinner
                            v-if="form.processing"
                            data-icon="inline-start"
                        />
                        Vytvořit přístup
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

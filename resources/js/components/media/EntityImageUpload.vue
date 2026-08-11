<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImageIcon, UploadIcon } from '@lucide/vue';
import { ref, useId } from 'vue';
import { store as storeEntityMedia } from '@/actions/App/Cookbook/Http/Controllers/EntityMediaController';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type EntityMediaType =
    'store-logo' | 'store-section-icon' | 'ingredient-photo' | 'recipe-cover';

const props = withDefaults(
    defineProps<{
        mediaType: EntityMediaType;
        entityId: number;
        imageUrl: string | null;
        imageAlt: string;
        editable?: boolean;
    }>(),
    { editable: true },
);

const inputId = useId();
const inputVersion = ref(0);
const form = useForm<{ image: File | null }>({ image: null });

const chooseFile = (event: Event): void => {
    const input = event.target;

    if (input instanceof HTMLInputElement) {
        form.image = input.files?.item(0) ?? null;
        form.clearErrors('image');
    }
};

const submit = (): void => {
    if (form.image === null) {
        return;
    }

    form.post(
        storeEntityMedia({
            mediaType: props.mediaType,
            entity: props.entityId,
        }).url,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('image');
                inputVersion.value += 1;
            },
        },
    );
};
</script>

<template>
    <div class="flex flex-col gap-2" data-media-image>
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :alt="imageAlt"
            class="h-24 w-full rounded-md border object-cover"
        />
        <div
            v-else
            class="flex h-24 items-center justify-center gap-2 rounded-md border border-dashed text-xs text-muted-foreground"
        >
            <ImageIcon aria-hidden="true" class="size-4" />
            <span>Obrázek zatím není nahraný</span>
        </div>

        <form
            v-if="editable"
            class="flex flex-col gap-2"
            @submit.prevent="submit"
        >
            <FieldGroup>
                <Field :data-invalid="Boolean(form.errors.image)">
                    <FieldLabel :for="inputId">Vybrat obrázek</FieldLabel>
                    <Input
                        :id="inputId"
                        :key="inputVersion"
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png"
                        :aria-invalid="Boolean(form.errors.image)"
                        :disabled="form.processing"
                        @change="chooseFile"
                    />
                    <FieldDescription>
                        JPEG nebo PNG, nejvýše 5 MB. Nové nahrání nahradí
                        současný obrázek.
                    </FieldDescription>
                    <FieldError :errors="[form.errors.image]" />
                </Field>
            </FieldGroup>

            <progress
                v-if="form.progress"
                class="h-2 w-full"
                :value="form.progress.percentage"
                max="100"
                aria-label="Průběh nahrávání obrázku"
            >
                {{ form.progress.percentage }} %
            </progress>

            <Button
                type="submit"
                size="sm"
                variant="outline"
                :disabled="form.processing || form.image === null"
            >
                <Spinner v-if="form.processing" data-icon="inline-start" />
                <UploadIcon v-else data-icon="inline-start" />
                {{ imageUrl ? 'Nahradit obrázek' : 'Nahrát obrázek' }}
            </Button>
        </form>
    </div>
</template>

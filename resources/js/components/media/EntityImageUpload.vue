<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImagePlusIcon, UploadIcon } from '@lucide/vue';
import { onBeforeUnmount, ref, useId } from 'vue';
import { store as storeEntityMedia } from '@/actions/App/Cookbook/Http/Controllers/EntityMediaController';
import EntityImagePreview from '@/components/media/EntityImagePreview.vue';
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

const props = defineProps<{
    mediaType: EntityMediaType;
    entityId: number;
    imageUrl: string | null;
    imageAlt: string;
}>();

const inputId = useId();
const inputVersion = ref(0);
const pendingPreviewUrl = ref<string | null>(null);
const isDragging = ref(false);
const form = useForm<{ image: File | null }>({ image: null });

const revokePendingPreview = (): void => {
    if (pendingPreviewUrl.value !== null) {
        URL.revokeObjectURL(pendingPreviewUrl.value);
        pendingPreviewUrl.value = null;
    }
};

const setFile = (file: File | null): void => {
    revokePendingPreview();
    form.image = file;
    form.clearErrors('image');

    if (file !== null) {
        pendingPreviewUrl.value = URL.createObjectURL(file);
    }
};

const chooseFile = (event: Event): void => {
    const input = event.target;

    if (input instanceof HTMLInputElement) {
        setFile(input.files?.item(0) ?? null);
    }
};

const dropFile = (event: DragEvent): void => {
    isDragging.value = false;
    setFile(event.dataTransfer?.files.item(0) ?? null);
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
                revokePendingPreview();
                form.reset('image');
                inputVersion.value += 1;
            },
        },
    );
};

onBeforeUnmount(revokePendingPreview);
</script>

<template>
    <div class="flex flex-col gap-2" data-media-image>
        <form class="flex flex-col gap-2" @submit.prevent="submit">
            <FieldGroup>
                <Field
                    :data-invalid="Boolean(form.errors.image)"
                    :data-disabled="form.processing"
                >
                    <FieldLabel :for="inputId">Obrázek</FieldLabel>
                    <label
                        :for="inputId"
                        class="group relative flex min-h-40 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border border-dashed bg-muted/30 text-center transition-colors hover:bg-muted/60 focus-within:ring-2 focus-within:ring-ring"
                        :class="isDragging ? 'border-primary bg-primary/5' : 'border-border'"
                        @dragenter.prevent="isDragging = true"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="dropFile"
                    >
                        <EntityImagePreview
                            v-if="pendingPreviewUrl ?? imageUrl"
                            class="h-full max-h-64 w-full object-cover"
                            :image-url="pendingPreviewUrl ?? imageUrl"
                            :image-alt="pendingPreviewUrl ? 'Náhled vybraného obrázku' : imageAlt"
                        />
                        <span
                            v-else
                            class="flex flex-col items-center gap-2 px-6 py-8 text-sm text-muted-foreground"
                        >
                            <ImagePlusIcon class="size-8" aria-hidden="true" />
                            Klikněte nebo sem přetáhněte obrázek
                        </span>
                        <span
                            v-if="pendingPreviewUrl ?? imageUrl"
                            class="absolute inset-x-0 bottom-0 bg-background/90 px-3 py-2 text-xs font-medium"
                        >
                            Kliknutím nebo přetažením vyberete jiný obrázek
                        </span>
                    </label>
                    <Input
                        :id="inputId"
                        :key="inputVersion"
                        class="sr-only"
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                        :aria-invalid="Boolean(form.errors.image)"
                        :disabled="form.processing"
                        @change="chooseFile"
                    />
                    <FieldDescription>
                        JPEG, PNG nebo statický WebP, nejvýše 5 MB. Současný
                        obrázek se nahradí až po úspěšném nahrání.
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

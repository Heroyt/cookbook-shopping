<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ArrowDownIcon, ArrowUpIcon, Trash2Icon } from '@lucide/vue';
import { shallowRef } from 'vue';
import StoreSectionAssociationController from '@/actions/App/Cookbook/Http/Controllers/StoreSectionAssociationController';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { FieldError } from '@/components/ui/field';
import type { StoreSummary } from '@/types';

const { store } = defineProps<{ store: StoreSummary }>();

const removingSectionId = shallowRef<number | null>(null);
const orderForm = useForm({
    store_section_ids: store.sections.map((storeSection) => storeSection.id),
    version: store.sectionOrderVersion,
});

const moveSection = (position: number, offset: -1 | 1): void => {
    const targetPosition = position + offset;

    if (targetPosition < 0 || targetPosition >= store.sections.length) {
        return;
    }

    const sectionIds = store.sections.map((storeSection) => storeSection.id);
    [sectionIds[position], sectionIds[targetPosition]] = [
        sectionIds[targetPosition],
        sectionIds[position],
    ];
    orderForm.store_section_ids = sectionIds;
    orderForm.version = store.sectionOrderVersion;
    orderForm.put(StoreSectionAssociationController.update(store.id).url, {
        preserveScroll: true,
    });
};

const removeSection = (storeSectionId: number): void => {
    router.delete(
        StoreSectionAssociationController.destroy({
            store: store.id,
            storeSection: storeSectionId,
        }).url,
        {
            preserveScroll: true,
            onStart: () => {
                removingSectionId.value = storeSectionId;
            },
            onFinish: () => {
                removingSectionId.value = null;
            },
        },
    );
};
</script>

<template>
    <Empty v-if="store.sections.length === 0" class="min-h-32 border p-4">
        <EmptyHeader>
            <EmptyTitle>Obchod zatím nemá přiřazené části</EmptyTitle>
            <EmptyDescription>
                Vyberte opakovaně použitelnou část obchodu.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>

    <div v-else class="flex flex-col gap-3">
        <ol class="flex flex-col gap-2">
            <li
                v-for="(storeSection, position) in store.sections"
                :key="storeSection.id"
                class="flex items-center gap-2 rounded-md border p-2"
            >
                <span
                    aria-hidden="true"
                    class="size-4 shrink-0 rounded-full border"
                    :style="{ backgroundColor: storeSection.colour }"
                />
                <span class="min-w-0 flex-1 truncate text-sm">
                    {{ storeSection.name }}
                </span>
                <div class="flex shrink-0 gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="position === 0 || orderForm.processing"
                        :aria-label="`Posunout část ${storeSection.name} nahoru`"
                        @click="moveSection(position, -1)"
                    >
                        <ArrowUpIcon />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="
                            position === store.sections.length - 1 ||
                            orderForm.processing
                        "
                        :aria-label="`Posunout část ${storeSection.name} dolů`"
                        @click="moveSection(position, 1)"
                    >
                        <ArrowDownIcon />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="removingSectionId !== null"
                        :aria-label="`Odebrat část ${storeSection.name} z obchodu`"
                        @click="removeSection(storeSection.id)"
                    >
                        <Trash2Icon />
                    </Button>
                </div>
            </li>
        </ol>

        <FieldError
            :errors="[
                orderForm.errors.store_section_ids,
                orderForm.errors.version,
            ]"
        />
    </div>
</template>

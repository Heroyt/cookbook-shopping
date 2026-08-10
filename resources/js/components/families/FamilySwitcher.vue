<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from '@lucide/vue';
import { computed } from 'vue';
import CurrentFamilyController from '@/actions/App/FamilyAccess/Http/Controllers/CurrentFamilyController';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create } from '@/routes/families';

const page = usePage();
const families = computed(() => page.props.families);
const currentFamily = computed(() => page.props.currentFamily);

const selectFamily = (value: unknown): void => {
    if (typeof value !== 'string' && typeof value !== 'number') {
        return;
    }

    const familyId = Number(value);

    if (!Number.isSafeInteger(familyId) || familyId < 1) {
        return;
    }

    router.put(CurrentFamilyController.update(familyId).url, undefined, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-2 px-2 group-data-[collapsible=icon]:hidden">
        <p class="px-2 text-xs font-medium text-muted-foreground">
            Current Family
        </p>

        <Select
            v-if="families.length > 0"
            :model-value="currentFamily ? String(currentFamily.id) : undefined"
            @update:model-value="selectFamily"
        >
            <SelectTrigger class="w-full">
                <SelectValue placeholder="Select a Family" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="family in families"
                    :key="family.id"
                    :value="String(family.id)"
                >
                    {{ family.name }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Button v-else variant="outline" class="w-full" as-child>
            <Link :href="create()">
                <PlusIcon />
                Create a Family
            </Link>
        </Button>
    </div>
</template>

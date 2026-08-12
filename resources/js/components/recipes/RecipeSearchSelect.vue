<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { CalendarRecipeOption } from '@/types';

const props = defineProps<{
    id: string;
    modelValue: string;
    recipes: CalendarRecipeOption[];
    name?: string;
    invalid?: boolean;
}>();
const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
const open = ref(false);
const selectedRecipe = computed(() =>
    props.recipes.find((recipe) => String(recipe.id) === props.modelValue),
);
const selectRecipe = (value: string): void => {
    emit('update:modelValue', value);
    open.value = false;
};
</script>

<template>
    <input v-if="name" type="hidden" :name="name" :value="modelValue" />
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                :aria-invalid="invalid"
                class="w-full justify-between font-normal"
            >
                <span :class="!selectedRecipe && 'text-muted-foreground'">
                    {{ selectedRecipe?.name ?? 'Hledat a vybrat recept' }}
                </span>
                <ChevronsUpDownIcon class="opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[var(--reka-popover-trigger-width)] p-0">
            <Command
                :model-value="modelValue"
                @update:model-value="selectRecipe(String($event))"
            >
                <CommandInput placeholder="Hledat recept…" />
                <CommandList>
                    <CommandEmpty>Žádný recept nebyl nalezen.</CommandEmpty>
                    <CommandItem
                        v-for="recipe in recipes"
                        :key="recipe.id"
                        :value="String(recipe.id)"
                    >
                        <CheckIcon
                            :class="
                                cn(
                                    'opacity-0',
                                    modelValue === String(recipe.id) &&
                                        'opacity-100',
                                )
                            "
                        />
                        {{ recipe.name }}
                    </CommandItem>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>

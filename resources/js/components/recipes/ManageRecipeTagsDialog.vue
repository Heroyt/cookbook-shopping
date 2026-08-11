<script setup lang="ts">
import { TagsIcon } from '@lucide/vue';
import RecipeTagManager from '@/components/recipes/RecipeTagManager.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RecipeTagOption } from '@/types';

defineProps<{ tags: RecipeTagOption[] }>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="outline">
                <TagsIcon data-icon="inline-start" /> Spravovat štítky
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Štítky receptů</DialogTitle>
                <DialogDescription>
                    Štítky jsou společné pro aktuální rodinu.
                </DialogDescription>
            </DialogHeader>
            <RecipeTagManager :tags="tags" />
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { LayoutGridIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import CreateStoreSectionForm from '@/components/stores/CreateStoreSectionForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const open = shallowRef(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">
                <LayoutGridIcon data-icon="inline-start" /> Nová část obchodu
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Vytvořit část obchodu</DialogTitle>
                <DialogDescription>
                    Přidejte opakovaně použitelnou část s vlastní barvou.
                </DialogDescription>
            </DialogHeader>
            <CreateStoreSectionForm @success="open = false" />
        </DialogContent>
    </Dialog>
</template>

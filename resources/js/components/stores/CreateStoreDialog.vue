<script setup lang="ts">
import { PlusIcon } from '@lucide/vue';
import { shallowRef } from 'vue';
import CreateStoreForm from '@/components/stores/CreateStoreForm.vue';
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
            <Button><PlusIcon data-icon="inline-start" /> Nový obchod</Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Vytvořit obchod</DialogTitle>
                <DialogDescription>
                    Přidejte místo, kde rodina nakupuje.
                </DialogDescription>
            </DialogHeader>
            <CreateStoreForm @success="open = false" />
        </DialogContent>
    </Dialog>
</template>

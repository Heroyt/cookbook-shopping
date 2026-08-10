<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CreateFamilyForm from '@/components/families/CreateFamilyForm.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create } from '@/routes/families';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Vytvořit rodinu',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Vytvořit rodinu" />

    <h1 class="sr-only">Vytvořit rodinu</h1>

    <div class="flex flex-1 justify-center p-4 md:p-6">
        <Card class="h-fit w-full max-w-xl">
            <CardHeader>
                <CardTitle>Vytvořit rodinu</CardTitle>
                <CardDescription>
                    Založte společný prostor pro rodinnou kuchařku. Stanete se
                    jeho prvním členem.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <CreateFamilyForm />
            </CardContent>
        </Card>
    </div>
</template>

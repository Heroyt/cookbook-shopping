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
                title: 'Create a Family',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Create a Family" />

    <h1 class="sr-only">Create a Family</h1>

    <div class="flex flex-1 justify-center p-4 md:p-6">
        <Card class="h-fit w-full max-w-xl">
            <CardHeader>
                <CardTitle>Create a Family</CardTitle>
                <CardDescription>
                    Start a shared cookbook workspace. You will become its first
                    ordinary member.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <CreateFamilyForm />
            </CardContent>
        </Card>
    </div>
</template>

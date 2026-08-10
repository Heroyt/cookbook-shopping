<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CircleAlertIcon, PlusIcon, UsersRoundIcon } from '@lucide/vue';
import { computed } from 'vue';
import AddFamilyMemberForm from '@/components/families/AddFamilyMemberForm.vue';
import DeleteFamilyDialog from '@/components/families/DeleteFamilyDialog.vue';
import FamilyMemberList from '@/components/families/FamilyMemberList.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { create, index } from '@/routes/families';
import type { FamilyDetail } from '@/types';

defineProps<{ family: FamilyDetail | null }>();

const page = usePage();
const membershipError = computed(() => page.props.errors?.membership);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Families',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Families" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Families</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage the members and lifecycle of your Current Family.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <PlusIcon />
                    Create Family
                </Link>
            </Button>
        </div>

        <Empty v-if="family === null" class="min-h-80 border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <UsersRoundIcon />
                </EmptyMedia>
                <EmptyTitle>No Family yet</EmptyTitle>
                <EmptyDescription>
                    Create a Family to start a shared cookbook workspace.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button as-child>
                    <Link :href="create()">Create your first Family</Link>
                </Button>
            </EmptyContent>
        </Empty>

        <template v-else>
            <Alert v-if="membershipError" variant="destructive">
                <CircleAlertIcon />
                <AlertTitle>Membership could not be removed</AlertTitle>
                <AlertDescription>{{ membershipError }}</AlertDescription>
            </Alert>

            <div class="grid items-start gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>{{ family.name }} members</CardTitle>
                        <CardDescription>
                            Every Family member has the same permissions.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <FamilyMemberList
                            :members="family.members"
                            :authenticated-user-id="page.props.auth.user.id"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Add a member</CardTitle>
                        <CardDescription>
                            Add an existing User to {{ family.name }}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AddFamilyMemberForm />
                    </CardContent>
                </Card>
            </div>

            <Card class="border-destructive/40">
                <CardHeader>
                    <CardTitle>Delete Family</CardTitle>
                    <CardDescription>
                        Permanently delete {{ family.name }} and all data it
                        owns.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <DeleteFamilyDialog :family-name="family.name" />
                </CardContent>
            </Card>
        </template>
    </div>
</template>

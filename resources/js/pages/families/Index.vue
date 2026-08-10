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
                title: 'Rodiny',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Rodiny" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Rodiny</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Spravujte členy a nastavení aktuální rodiny.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <PlusIcon />
                    Vytvořit rodinu
                </Link>
            </Button>
        </div>

        <Empty v-if="family === null" class="min-h-80 border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <UsersRoundIcon />
                </EmptyMedia>
                <EmptyTitle>Zatím nemáte žádnou rodinu</EmptyTitle>
                <EmptyDescription>
                    Vytvořte rodinu a začněte sdílet společnou kuchařku.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button as-child>
                    <Link :href="create()">Vytvořit první rodinu</Link>
                </Button>
            </EmptyContent>
        </Empty>

        <template v-else>
            <Alert v-if="membershipError" variant="destructive">
                <CircleAlertIcon />
                <AlertTitle>Členství se nepodařilo odebrat</AlertTitle>
                <AlertDescription>{{ membershipError }}</AlertDescription>
            </Alert>

            <div class="grid items-start gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Členové rodiny {{ family.name }}</CardTitle>
                        <CardDescription>
                            Všichni členové rodiny mají stejná oprávnění.
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
                        <CardTitle>Přidat člena</CardTitle>
                        <CardDescription>
                            Přidejte existujícího uživatele do rodiny
                            {{ family.name }}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AddFamilyMemberForm />
                    </CardContent>
                </Card>
            </div>

            <Card class="border-destructive/40">
                <CardHeader>
                    <CardTitle>Smazat rodinu</CardTitle>
                    <CardDescription>
                        Trvale smažte rodinu {{ family.name }} a všechna její
                        data.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <DeleteFamilyDialog :family-name="family.name" />
                </CardContent>
            </Card>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, ArrowRightIcon, EyeIcon } from '@lucide/vue';
import DeleteSavedShoppingListDialog from '@/components/shopping-list-history/DeleteSavedShoppingListDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { show } from '@/routes/shopping-list-history';
import type {
    SavedShoppingListPagination,
    SavedShoppingListSummary,
} from '@/types';

defineProps<{
    snapshots: SavedShoppingListSummary[];
    pagination: SavedShoppingListPagination;
}>();
</script>

<template>
    <section aria-label="Uložené nákupní seznamy">
        <div
            v-if="snapshots.length > 0"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Card v-for="snapshot in snapshots" :key="snapshot.id">
                <CardHeader>
                    <CardTitle>{{ snapshot.generatedAt }}</CardTitle>
                    <CardDescription>{{
                        snapshot.sourceLabel
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Badge variant="secondary">
                        Verze záznamu {{ snapshot.schemaVersion }}
                    </Badge>
                </CardContent>
                <CardFooter class="flex flex-wrap justify-between gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="show(snapshot.id)">
                            <EyeIcon data-icon="inline-start" />
                            Zobrazit seznam
                        </Link>
                    </Button>
                    <DeleteSavedShoppingListDialog
                        :snapshot="snapshot"
                        focus-target-id="shopping-list-history-heading"
                    />
                </CardFooter>
            </Card>
        </div>
        <Empty v-else>
            <EmptyHeader>
                <EmptyTitle>Historie nákupů je prázdná</EmptyTitle>
                <EmptyDescription>
                    Vytvořený nákupní seznam můžete uložit z rychlého plánu nebo
                    z kalendáře.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>

        <nav
            v-if="
                pagination.previousUrl !== null || pagination.nextUrl !== null
            "
            class="mt-6 flex items-center justify-between gap-3"
            aria-label="Stránkování historie nákupů"
        >
            <Button
                v-if="pagination.previousUrl !== null"
                as-child
                variant="outline"
            >
                <Link :href="pagination.previousUrl" preserve-scroll>
                    <ArrowLeftIcon data-icon="inline-start" />
                    Novější seznamy
                </Link>
            </Button>
            <span v-else />
            <Button
                v-if="pagination.nextUrl !== null"
                as-child
                variant="outline"
            >
                <Link :href="pagination.nextUrl" preserve-scroll>
                    Starší seznamy
                    <ArrowRightIcon data-icon="inline-end" />
                </Link>
            </Button>
        </nav>
    </section>
</template>

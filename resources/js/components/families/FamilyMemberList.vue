<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FamilyMemberController from '@/actions/App/FamilyAccess/Http/Controllers/FamilyMemberController';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { FamilyMember } from '@/types';

const props = defineProps<{
    members: FamilyMember[];
    authenticatedUserId: number;
}>();

const processingMemberId = ref<number | null>(null);
const isFinalMembership = computed(() => props.members.length === 1);

const removeMember = (member: FamilyMember): void => {
    processingMemberId.value = member.id;

    router.delete(FamilyMemberController.destroy(member.id).url, {
        preserveScroll: true,
        onFinish: () => {
            processingMemberId.value = null;
        },
    });
};
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Člen</TableHead>
                <TableHead class="hidden sm:table-cell">E-mail</TableHead>
                <TableHead class="w-24 text-right">Akce</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="member in members" :key="member.id">
                <TableCell>
                    <div class="font-medium">{{ member.name }}</div>
                    <div class="text-xs text-muted-foreground sm:hidden">
                        {{ member.email }}
                    </div>
                </TableCell>
                <TableCell class="hidden text-muted-foreground sm:table-cell">
                    {{ member.email }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge
                        v-if="member.id === authenticatedUserId"
                        variant="secondary"
                        class="mr-2"
                    >
                        Vy
                    </Badge>

                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button
                                variant="ghost"
                                size="sm"
                                :disabled="isFinalMembership"
                            >
                                {{
                                    member.id === authenticatedUserId
                                        ? 'Opustit'
                                        : 'Odebrat'
                                }}
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    {{
                                        member.id === authenticatedUserId
                                            ? 'Opustit tuto rodinu?'
                                            : `Odebrat uživatele ${member.name}?`
                                    }}
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    Tímto odeberete členství v rodině. Účet
                                    uživatele zůstane zachován.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Zrušit</AlertDialogCancel>
                                <AlertDialogAction
                                    :disabled="processingMemberId === member.id"
                                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                    @click="removeMember(member)"
                                >
                                    Potvrdit
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <p v-if="isFinalMembership" class="mt-3 text-sm text-muted-foreground">
        Poslední členství nelze odebrat. Místo toho smažte rodinu.
    </p>
</template>

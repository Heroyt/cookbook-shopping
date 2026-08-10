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
                <TableHead>Member</TableHead>
                <TableHead class="hidden sm:table-cell">Email</TableHead>
                <TableHead class="w-24 text-right">Action</TableHead>
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
                        You
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
                                        ? 'Leave'
                                        : 'Remove'
                                }}
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    {{
                                        member.id === authenticatedUserId
                                            ? 'Leave this Family?'
                                            : `Remove ${member.name}?`
                                    }}
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    This removes the Family Membership. It does
                                    not delete the User account.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    :disabled="processingMemberId === member.id"
                                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                    @click="removeMember(member)"
                                >
                                    Confirm
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <p v-if="isFinalMembership" class="mt-3 text-sm text-muted-foreground">
        The final membership cannot be removed. Delete the Family instead.
    </p>
</template>

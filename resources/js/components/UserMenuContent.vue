<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BotIcon, HistoryIcon, LogOut, Settings } from '@lucide/vue';
import AgentChangeSetHistoryController from '@/actions/App/AgentIntegration/Http/Controllers/AgentChangeSetHistoryController';
import AgentCredentialController from '@/actions/App/AgentIntegration/Http/Controllers/AgentCredentialController';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
    showFamilyLinks: boolean;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings />
                Nastavení
            </Link>
        </DropdownMenuItem>
        <template v-if="showFamilyLinks">
            <DropdownMenuItem :as-child="true">
                <Link
                    class="block w-full cursor-pointer"
                    :href="AgentCredentialController.index()"
                    prefetch
                >
                    <BotIcon />
                    Přístupy agentů
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link
                    class="block w-full cursor-pointer"
                    :href="AgentChangeSetHistoryController.index()"
                    prefetch
                >
                    <HistoryIcon />
                    Historie změn agentů
                </Link>
            </DropdownMenuItem>
        </template>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut />
            Odhlásit se
        </Link>
    </DropdownMenuItem>
</template>

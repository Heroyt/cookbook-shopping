<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    StoreIcon,
    UsersRound,
    WheatIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import FamilySwitcher from '@/components/families/FamilySwitcher.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as familiesIndex } from '@/routes/families';
import { index as ingredientsIndex } from '@/routes/ingredients';
import { index as storesIndex } from '@/routes/stores';
import type { NavItem } from '@/types';

const page = usePage();
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Přehled',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Rodiny',
        href: familiesIndex(),
        icon: UsersRound,
    },
    ...(page.props.currentFamily === null
        ? []
        : [
              {
                  title: 'Suroviny',
                  href: ingredientsIndex(),
                  icon: WheatIcon,
              },
              {
                  title: 'Obchody',
                  href: storesIndex(),
                  icon: StoreIcon,
              },
          ]),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repozitář',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Dokumentace',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <FamilySwitcher />
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

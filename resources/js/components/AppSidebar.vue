<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDaysIcon,
    ClipboardListIcon,
    HistoryIcon,
    LayoutGrid,
    StoreIcon,
    NotebookTabsIcon,
    UsersRound,
    WheatIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import FamilySwitcher from '@/components/families/FamilySwitcher.vue';
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
import { index as calendarIndex } from '@/routes/calendar';
import { index as familiesIndex } from '@/routes/families';
import { index as ingredientsIndex } from '@/routes/ingredients';
import { index as recipesIndex } from '@/routes/recipes';
import { index as shoppingListHistoryIndex } from '@/routes/shopping-list-history';
import { index as simplePlanIndex } from '@/routes/simple-plan';
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
                  title: 'Kalendář',
                  href: calendarIndex(),
                  icon: CalendarDaysIcon,
              },
              {
                  title: 'Rychlý plán',
                  href: simplePlanIndex(),
                  icon: ClipboardListIcon,
              },
              {
                  title: 'Historie nákupů',
                  href: shoppingListHistoryIndex(),
                  icon: HistoryIcon,
              },
              {
                  title: 'Recepty',
                  href: recipesIndex(),
                  icon: NotebookTabsIcon,
              },
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

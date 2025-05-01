<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Banknote, CalendarCheck, CircleUser, LayoutGrid, ListEnd, Logs, Network, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLogo from './AppLogo.vue';

const navItems = ref<{ title: string; items: NavItem[] }[]>([
    {
        title: 'Platform',
        items: [
            {
                title: 'Dashboard',
                href: '/dashboard',
                icon: LayoutGrid,
            },
        ],
    },
    {
        title: 'Master',
        items: [
            {
                title: 'Role Management',
                href: '/dashboard/role-management',
                activePaths: ['dashboard.role-management.index', 'dashboard.role-management.create', 'dashboard.role-management.show'],
                icon: Network,
            },
            {
                title: 'User Account',
                href: '/dashboard/users',
                activePaths: ['dashboard.users.index', 'dashboard.users.create', 'dashboard.users.show'],
                icon: CircleUser,
                isAdmin: true,
            },
            {
                title: 'Seminar',
                href: '/dashboard/seminars',
                activePaths: ['dashboard.seminars.index', 'dashboard.seminars.create', 'dashboard.seminars.show'],
                icon: CalendarCheck
            }
        ],
    },
    {
        title: 'Participants',
        items: [
            {
                title: 'Participant',
                href: '/dashboard/participants',
                activePaths: ['dashboard.participants.index', 'dashboard.participants.create', 'dashboard.participants.show'],
                icon: Users
            },
        ],
    },
    {
        title: 'Payments',
        items: [
            {
                title: 'Payment',
                href: '/dashboard/payments',
                activePaths: ['dashboard.payments.index', 'dashboard.payments.create', 'dashboard.payments.show'],
                icon: Banknote
            },
        ],
    },
    {
        title: 'Audits',
        items: [
            {
                title: 'Audit Trail',
                href: '/dashboard/audits',
                activePaths: ['dashboard.audits.index'],
                icon: Logs
            },
        ],
    },
    {
        title: 'Downloads',
        items: [
            {
                title: 'Export & Import',
                href: '/dashboard/downloads',
                activePaths: ['dashboard.downloads.index'],
                icon: ListEnd
            },
        ],
    }
]);

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <Link :href="route('dashboard')" class="flex items-center justify-center rounded-md p-2">
                        <AppLogo />
                    </Link>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-for="(navItem, ni) in navItems" :title="navItem.title" :items="navItem.items" :key="ni" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
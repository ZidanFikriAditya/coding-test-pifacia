<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { User, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
    title: string;
}>();

const page = usePage<SharedData>();
const user = page.props.auth.user as User

const currentPage = route().current() || '';

</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ title }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="`sidebar_key_${item.title}`">
                <SidebarMenuItem v-if="!item.isAdmin || user.role?.slug === 'administrator'">
                    <SidebarMenuButton as-child :is-active="item.activePaths ? item.activePaths.includes(currentPage) : item.href === page.url">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

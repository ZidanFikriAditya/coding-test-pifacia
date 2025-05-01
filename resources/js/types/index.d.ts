import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    isAdmin?: boolean;
    activePaths?: string[];
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    role?: {
        id: number;
        name: string;
        slug: string;
        guard_name: string;
    };
    created_at: string;
    updated_at: string;
}

export interface ResponseTable {
    data: any[];
    meta: {
        total: number;
        perPage: number;
        currentPage: number;
        lastPage: number;
    };
}

export type BreadcrumbItemType = BreadcrumbItem;

<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { toTitleCase } from '@/lib/utils';
import { AlignJustifyIcon } from 'lucide-vue-next';

interface Props {
    actions: Array<'edit' | 'delete' | 'view'>;

}

const props = defineProps<Props>();
const emit = defineEmits(['action']);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger>
            <AlignJustifyIcon class="h-4 w-4" />
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuLabel>Action</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem v-for="action in actions" :key="action" @click="$emit('action', action)"> {{ toTitleCase(action) }} </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

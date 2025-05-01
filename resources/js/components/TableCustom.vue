<script setup lang="ts">
import { tableStore } from '@/stores/index';
import { ArrowDownAZ, ArrowUpAZ, ArrowUpDown, ChevronLeft, Search } from 'lucide-vue-next';
import { SelectValue } from 'reka-ui';
import { h, isVNode, ref, VNode, watch } from 'vue';
import Button from './ui/button/Button.vue';
import { Input } from './ui/input';
import {
  Pagination,
  PaginationEllipsis,
  PaginationList,
  PaginationListItem,
} from '@/components/ui/pagination'
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger } from './ui/select/index';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import ActionTableCustom from './ActionTableCustom.vue';

export type TableHeaderType = {
    key: string;
    label: string;
    sortable?: boolean;
    render?: (value: any, index: number | string) => string | VNode;
};

interface Props {
    headers: TableHeaderType[];
    data: any[];
    pagination?: {
        total: number;
        perPage: number;
        currentPage: number;
        lastPage: number;
    };
    multipleDelete?: boolean;
    searching?: boolean;
    tableTitle?: string;
    primaryColumn?: string;
    actions?: Array<'edit' | 'delete' | 'view'>;
}

const props = withDefaults(defineProps<Props>(), {
    primaryColumn: 'id',
});
const modalConfirmDelete = ref<{
    show: boolean;
    id?: null | string | number;
}>({
    show: false,
    id: null,
});
const emit = defineEmits(['delete', 'action']);

const renderComponent = (render: string | VNode) => {
    return isVNode(render)
        ? render
        : h(
              'span',
              {
                  class: 'text-gray-900 font-normal',
              },
              render,
          );
};

const goToPage = (page: number) => {
    tableStore.page = page;
};

const handleSort = (key: string) => {
    if (tableStore.sorting.key === key && tableStore.sorting.order === 'asc') {
        tableStore.resetOrder();
    } else {
        tableStore.sorting.key = key;
        tableStore.sorting.order = tableStore.sorting.order === 'asc' ? 'desc' : 'asc';
    }
};

const handleChecked = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.checked) {
        if (!tableStore.selected.includes(target.value)) {
            tableStore.selected.push(target.value);
        }
    } else {
        tableStore.selected = tableStore.selected.filter((item) => item !== target.value);
    }
};

const handleCheckedAll = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.checked) {
        tableStore.selected = props.data.map((item) => item[props.primaryColumn]?.toString());
    } else {
        tableStore.selected = [];
    }
};
</script>

<template>
    <div class="max-w-full overflow-x-auto px-6">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center">
                <h1 class="text-2xl font-bold text-gray-900" v-if="props.tableTitle && tableStore.selected.length == 0">{{ props.tableTitle }}</h1>
                <div class="flex items-center gap-2" :class="`${tableStore.selected.length > 0 ? 'opacity-100 duration-300' : 'opacity-0'}`">
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="
                            modalConfirmDelete = {
                                id: null,
                                show: true,
                            }
                        "
                    >
                        Delete
                    </Button>
                    <span class="text-gray-500">Selected {{ tableStore.selected.length }} items</span>
                </div>
            </div>
            <div class="flex items-center justify-end" v-if="searching">
                <div class="relative w-full max-w-xs items-center">
                    <Input v-model="tableStore.search" type="text" placeholder="Search..." class="py-3 pl-10" />
                    <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
                        <Search class="size-5 text-gray-300" />
                    </span>
                </div>
            </div>
        </div>
        <table class="min-w-full rounded-md">
            <thead class="bg-white" style="border: 1px solid #d6ddeb">
                <tr class="text-sm font-normal text-gray-400 select-none">
                    <th class="w-12 px-4 py-3 text-left" v-if="multipleDelete">
                        <input class="rounded border-gray-300" type="checkbox" @change="handleCheckedAll" />
                    </th>
                    <th class="px-4 py-3 text-left whitespace-nowrap" v-for="header in props.headers" :key="`table_header_${header.key}`">
                        <div class="flex items-center gap-1 select-none" :class="header.sortable && 'cursor-pointer'" @click="handleSort(header.key)">
                            {{ header.label }}
                            <template v-if="header.sortable">
                                <ArrowUpDown class="h-4 w-4 text-gray-400" v-if="tableStore.sorting?.key != header.key" />
                                <ArrowDownAZ
                                    class="h-4 w-4 text-gray-400"
                                    v-if="tableStore.sorting?.key == header.key && tableStore.sorting.order == 'asc'"
                                />
                                <ArrowUpAZ
                                    class="h-4 w-4 text-gray-400"
                                    v-if="tableStore.sorting?.key == header.key && tableStore.sorting.order == 'desc'"
                                />
                            </template>
                        </div>
                    </th>
                    <th v-if="actions" class="px-4 py-3 text-left whitespace-nowrap">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td :colspan="props.headers.length" class="!border-none bg-transparent py-2"></td>
                </tr>
            </tbody>
            <tbody class="mt-5 divide-y divide-gray-100 text-sm" style="border: 1px solid #d6ddeb">
                <tr>
                    <td colspan="100%" class="py-4 text-center" v-if="props.data.length === 0">
                        <span class="text-gray-400">No data available</span>
                    </td>
                </tr>
                <tr v-for="(item, index) in props.data" :key="`table_row_${index}`" :class="`${index % 2 === 0 ? 'bg-[#F8F8FD]' : ''}`">
                    <td class="w-12 px-4 py-3" v-if="multipleDelete">
                        <input
                            class="rounded border-gray-300"
                            type="checkbox"
                            @change="handleChecked"
                            :value="item[primaryColumn]"
                            :checked="tableStore.selected.includes(item[props.primaryColumn]?.toString())"
                        />
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap" v-for="header in props.headers" :key="`table_row_${index}_${header.key}`">
                        <template v-if="header.render">
                            <component :is="renderComponent(header.render(item, index))" />
                        </template>
                        <template v-else>
                            {{ item[header.key] }}
                        </template>
                    </td>
                    <td v-if="actions" class="px-4 py-3 whitespace-nowrap">
                        <ActionTableCustom 
                            :actions="actions"
                            @action="(action) => $emit('action', {
                                id: item[props.primaryColumn],
                                action: action,
                            })"
                        />
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="props.pagination" class="mt-4 flex items-center justify-between">
            <div class="inline-flex items-center gap-2">
                <div>Showing</div>
                <div>
                    <Select v-model="tableStore.perPage">
                        <SelectTrigger>
                            <SelectValue placeholder="Order" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Order</SelectLabel>
                                <SelectItem v-for="item in [10, 50, 100, 500]" :key="`table_order_${item}`" :value="item">{{ item }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div>of {{ props.pagination.total }} items</div>
            </div>

            <Pagination
                v-slot="{ page }"
                :items-per-page="props.pagination.perPage"
                :total="props.pagination.total"
                :sibling-count="1"
                show-edges
                :default-page="props.pagination.currentPage"
            >
                <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                    <Button @click="goToPage(1)" variant="ghost" class="h-10 w-10 p-0" :disabled="page === 1">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>

                    <template v-for="(item, index) in items">
                        <PaginationListItem v-if="item.type === 'page'" :key="index" :value="item.value" as-child>
                            <Button
                                @click="goToPage(item.value)"
                                class="h-8 w-8"
                                :variant="item.value === page ? 'default' : 'outline'"
                                :class="`${item.value === page ? 'bg-blue-500' : ''}`"
                            >
                                {{ item.value }}
                            </Button>
                        </PaginationListItem>
                        <PaginationEllipsis v-else :key="item.type" :index="index" />
                    </template>

                    <Button
                        @click="props.pagination.currentPage < props.pagination.lastPage ? goToPage(props.pagination.currentPage + 1) : null"
                        variant="ghost"
                        class="h-10 w-10 p-0"
                        :disabled="page === props.pagination.lastPage"
                    >
                        <ChevronLeft class="h-4 w-4 rotate-180" />
                    </Button>
                </PaginationList>
            </Pagination>
        </div>
    </div>

    <AlertDialog :open="modalConfirmDelete.show" v-on:update:open="(value) => (modalConfirmDelete.show = value)">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                <AlertDialogDescription>
                    This action cannot be undone. This will permanently delete this data from our servers.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction class="bg-red-500" @click="$emit('delete', tableStore.selected)">Delete</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

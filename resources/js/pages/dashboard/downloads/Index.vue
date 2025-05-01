<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SelectSearch, { OptionType } from '@/components/SelectSearch.vue';
import TableCustom, { TableHeaderType } from '@/components/TableCustom.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import { tableStore } from '@/stores';
import { BreadcrumbItem, ResponseTable } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, h, onBeforeMount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Downloads',
        href: '/dashboard/downloads',
    },
];

const headers: TableHeaderType[] = [
    {
        key: 'name',
        label: 'File Name',
        sortable: true,
    },
    {
        key: 'type',
        label: 'Type',
        sortable: true,
    },
    {
        key: 'created_at',
        label: 'Exported At',
        sortable: true,
    },
    {
        key: 'finished_at',
        label: 'Finished At',
        sortable: true,
    },
    {
        key: 'status',
        label: 'Status',
        sortable: true,
    },
    {
        key: 'link',
        label: 'Link',
        render: (item: any) => {
            return item?.link ? h('a', { href: item.link, target: '_blank' }, 'Download') : '-';
        },
    }
];

const basePath = '/dashboard/downloads';
const data = ref<ResponseTable>();
const modal = ref<{
    show: boolean;
    type: string;
    data: any;
    title: string;
    description: string;
    processing: boolean;
}>({
    show: false,
    type: 'create',
    data: {},
    title: 'Create User',
    description: 'Are you sure you want to create a new user?',
    processing: false,
});
const form = ref<{
    [key: string]: {
        value: any;
        preview: any;
        error: string;
        required?: boolean;
    };
}>();

const getData = () => {
    axios
        .get(basePath + '/data', {
            params: {
                search: tableStore.search,
                page: tableStore.page,
                limit: tableStore.perPage,
                order_by: tableStore.sorting.key,
                order: tableStore.sorting.order,
            },
        })
        .then((response) => {
            data.value = response.data;
        })
        .catch((error) => {
            console.error(error);
        });
};

const handleAction = (action: string, id: number) => {
    modal.value.show = true;
    modal.value.type = 'delete';
    modal.value.data = { id };
    modal.value.title = 'Delete Download';
    modal.value.description = 'Are you sure you want to delete this download? This action cannot be undone.';
};

const deleteData = (id: number) => {
    modal.value.processing = true;
    axios
        .delete(`${basePath}/${id}`)
        .then((response) => {
            getData();
            tableStore.resetState();

            modal.value.processing = false;
            modal.value.show = false;

            toast.success('Successfully deleted user!');
        })
        .catch((error) => {
            console.error(error);

            toast.error('Failed to delete user!');
        });
};

const bulkDelete = (selectedIds: number[]) => {
    axios
        .post(basePath + '/bulk-destroy', { ids: selectedIds })
        .then((response) => {
            getData();
            tableStore.resetState();

            toast.success('Successfully deleted user!');
        })
        .catch((error) => {
            console.error(error);

            toast.error('Failed to delete user!');
        });
};

watch(
    () => tableStore.search,
    debounce(() => {
        getData();
    }, 500),
);

watch(
    () => [tableStore.perPage, tableStore.page, tableStore.sorting],
    () => {
        getData();
    },
    {
        deep: true,
    },
);

onBeforeMount(() => {
    tableStore.resetState();
});

onMounted(() => {
    getData();
});
</script>

<template>
    <Head title="Download" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mt-4">
            <TableCustom
                :headers="headers"
                :data="data?.data || []"
                :pagination="data?.meta"
                :multiple-delete="true"
                :searching="true"
                table-title="Download"
                :actions="['delete']"
                @action="(action) => handleAction(action.action, action.id)"
                @delete="bulkDelete"
            />
        </div>
    </AppLayout>

    <Dialog :open="modal.show" @update:open="modal.show = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ modal.title }}</DialogTitle>
                <DialogDescription>
                    {{ modal.description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" @click="modal.show = false"> Cancel </Button>
                <Button
                    v-if="modal.type === 'delete'"
                    type="button"
                    variant="destructive"
                    @click="deleteData(modal.data.id)"
                    :disabled="modal.processing"
                >
                    {{ modal.processing ? 'Deleting...' : 'Yes, delete it' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

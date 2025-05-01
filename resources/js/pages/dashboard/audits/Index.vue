<script setup lang="ts">
import TableCustom, { TableHeaderType } from '@/components/TableCustom.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce, toTitleCase } from '@/lib/utils';
import { tableStore } from '@/stores';
import { BreadcrumbItem, ResponseTable } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onBeforeMount, onMounted, ref, watch } from 'vue';

interface Props {
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Audits',
        href: '/dashboard/users',
    },
];

const headers: TableHeaderType[] = [
    {
        key: 'created_at',
        label: 'Date',
        sortable: true
    },
    {
        key: 'event',
        label: 'Action',
        sortable: true,
        render: (item: any) => {
            return toTitleCase(item.event);
        }
    },
    {
        key: 'user',
        label: 'User',
        sortable: true,
    },
    {
        key: 'note',
        label: 'Note',
    }
];

const basePath = '/dashboard/audits';
const data = ref<ResponseTable>();

const getData = () => {
    axios
        .get(basePath + '/data', {
            params: {
                search: tableStore.search,
                page: tableStore.page,
                limit: tableStore.perPage,
                order_by: tableStore.sorting.key,
                order: tableStore.sorting.order,
                per_page: tableStore.perPage,
            }
        })
        .then((response) => {
            data.value = response.data;
        })
        .catch((error) => {
            console.error(error);
        });
};

watch(() => tableStore.search, debounce(() => {
    getData()
}, 500))

watch(() => [tableStore.perPage, tableStore.page, tableStore.sorting], () => {
    getData()
}, {
    deep: true
})

onBeforeMount(() => {
    tableStore.resetState()
})

onMounted(() => {
    getData();
});
</script>

<template>
    <Head title="Audits" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mt-4">
            <TableCustom
                :headers="headers"
                :data="data?.data || []"
                :pagination="data?.meta"
                :searching="true"
                table-title="Audits"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SelectSearch, { OptionType } from '@/components/SelectSearch.vue';
import TableCustom, { TableHeaderType } from '@/components/TableCustom.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Switch from '@/components/ui/switch/Switch.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import { tableStore } from '@/stores';
import { BreadcrumbItem, ResponseTable, SharedData, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Download, Upload } from 'lucide-vue-next';
import { h, onBeforeMount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Props {}

const basePath = '/dashboard/participants';
const props = defineProps<Props>();
const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Participant',
        href: '/dashboard/participants',
    },
];

const headers: TableHeaderType[] = [
    {
        key: 'name',
        label: 'Name',
        sortable: true,
    },
    {
        key: 'email',
        label: 'Email',
        sortable: true,
    },
    {
        key: 'seminar',
        label: 'Seminar',
        sortable: true,
    },
    {
        key: 'is_confirmed',
        label: 'Is Confirmed',
        sortable: true,
        render: (item: any) => {
            if (user?.role?.slug === 'administrator') {
                return h(Switch, {
                    modelValue: item.is_confirmed ? true : false,
                    'onUpdate:modelValue': (value: boolean) => {
                        axios
                            .post(basePath + '/' + item.id + '/update-status', { is_confirmed: value })
                            .then((response) => {
                                getData();
                                toast.success('Status updated successfully!');
                            })
                            .catch((error) => {
                                console.error(error);
                                toast.error('Failed to update status!');
                            });
                    },
                });
            } else {
                return h('span', { class: 'text-sm font-medium text-gray-900 dark:text-gray-300' }, item.is_confirmed ? 'Yes' : 'No');
            }
        },
    },
];

const data = ref<ResponseTable>();

const optionsDynamicColumn = [
    { label: 'Seminar', value: 'seminar' },
    { label: 'Name', value: 'name' },
    { label: 'Email', value: 'email' },
    { label: 'Registered At', value: 'registered_at' },
    { label: 'Confirmed', value: 'is_confirmed' },
    { label: 'Extra Data', value: 'extra_data' },
    { label: 'Created At', value: 'created_at' },
];
const formDynamicColumn = ref<OptionType[]>(optionsDynamicColumn);
const importFile = ref<File | null>(null);

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
    title: 'Create Participant',
    description: 'Are you sure you want to create a new user?',
    processing: false,
});

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
    if (action === 'edit') {
        router.visit(basePath + '/' + id);
    } else if (action === 'delete') {
        modal.value.show = true;
        modal.value.type = 'delete';
        modal.value.data = { id };
        modal.value.title = 'Delete Participant';
        modal.value.description = 'Are you sure you want to delete this participant? This action cannot be undone.';
    }
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

            toast.success('Delete successful!');
        })
        .catch((error) => {
            console.error(error);
            toast.error('Delete failed!');
        });
};

const bulkDelete = (selectedIds: number[]) => {
    axios
        .post(basePath + '/bulk-destroy', { ids: selectedIds })
        .then((response) => {
            getData();
            tableStore.resetState();

            toast.success('Bulk delete successful!');
        })
        .catch((error) => {
            console.error(error);

            toast.error('Bulk delete failed!');
        });
};

const handleExport = () => {
    modal.value.processing = true;
    axios
        .post(basePath + '/export', {
            headers: formDynamicColumn.value,
        })
        .then((response) => {
            modal.value.processing = false;
            modal.value.show = false;

            formDynamicColumn.value = optionsDynamicColumn;
            toast.success('Export successful!');
        })
        .catch((error) => {
            console.error(error);
            modal.value.processing = false;
            modal.value.show = false;

            toast.error('Export failed!');
        });
};

const handleImport = () => {
    modal.value.processing = true;
    const formData = new FormData();
    if (importFile.value) {
        formData.append('file', importFile.value);
    }
    axios
        .post(basePath + '/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        .then((response) => {
            modal.value.processing = false;
            modal.value.show = false;
            getData();
            importFile.value = null;
            toast.success('Import successful!');
        })
        .catch((error) => {
            console.error(error);
            modal.value.processing = false;
            modal.value.show = false;

            toast.error('Import failed!');
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
    <Head title="Participant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto my-6 px-6">
            <div class="flex justify-end gap-1">
                <Button
                    @click="
                        () => {
                            modal.show = true;
                            modal.type = 'import';
                            modal.title = 'Import Participant';
                            modal.description = 'Are you sure you want to import this seminar?';
                            modal.processing = false;
                        }
                    "
                    variant="outline"
                    ><Upload /> Import</Button
                >
                <Button
                    @click="
                        () => {
                            modal.show = true;
                            modal.type = 'export';
                            modal.title = 'Export Participant';
                            modal.description = 'Are you sure you want to export this seminar?';
                            modal.processing = false;
                        }
                    "
                    variant="outline"
                    ><Download /> Export</Button
                >
                <Button :as="Link" href="/dashboard/participants/create">Create Participant</Button>
            </div>
        </div>

        <TableCustom
            :headers="headers"
            :data="data?.data || []"
            :pagination="data?.meta"
            :multiple-delete="true"
            :searching="true"
            table-title="Participant"
            :actions="['edit', 'delete']"
            @action="(action) => handleAction(action.action, action.id)"
            @delete="bulkDelete"
        />
    </AppLayout>

    <Dialog :open="modal.type == 'delete' && modal.show" @update:open="modal.show = $event">
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

    <Dialog :open="modal.type == 'export' && modal.show" @update:open="modal.show = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ modal.title }}</DialogTitle>
                <DialogDescription>
                    {{ modal.description }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <Label for="export-format">Dynamic Column</Label>
                <SelectSearch
                    id="export-format"
                    :options="optionsDynamicColumn"
                    @change="
                        (value: OptionType[]) => {
                            formDynamicColumn = value;
                        }
                    "
                    :default-value="formDynamicColumn?.map((item) => item.value as string)"
                    :placeholder="'Select export format'"
                    :is-multiple="true"
                />
                <InputError :message="modal.data.exportFormatError" class="mt-2" />
            </div>

            <DialogFooter>
                <Button @click="handleExport" :disabled="modal?.processing || false">Export</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="modal.type == 'import' && modal.show" @update:open="modal.show = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ modal.title }}</DialogTitle>
                <DialogDescription>
                    {{ modal.description }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 gap-4 py-4">
                <div>
                    <Label>Template</Label>
                    <a href="/import-templates/participant.xlsx" class="text-sm text-blue-500 hover:underline">Download Template</a>
                </div>
                <div>
                    <Label for="import-file">Import File</Label>
                    <Input
                        type="file"
                        id="import-file"
                        accept=".csv, .xlsx, .xls"
                        @change="
                            (event: Event) => {
                                const file = (event.target as HTMLInputElement).files?.[0];
                                if (file) {
                                    importFile = file;
                                }
                            }
                        "
                    />
                    <InputError :message="modal.data.fileError" class="mt-2" />
                </div>
            </div>

            <DialogFooter>
                <Button @click="handleImport" :disabled="modal?.processing || false">Import</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

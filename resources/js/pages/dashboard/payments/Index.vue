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
import { BreadcrumbItem, ResponseTable, SharedData, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, Download, Upload, X } from 'lucide-vue-next';
import { h, onBeforeMount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const basePath = '/dashboard/payments';
const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Payment',
        href: '/dashboard/payments',
    },
];

const headers: TableHeaderType[] = [
    {
        key: 'participant_name',
        label: 'Participant Name',
        sortable: true,
    },
    {
        key: 'participant_email',
        label: 'Participant Email',
        sortable: true,
    },
    {
        key: 'uploaded_at',
        label: 'Uploaded At',
    },
    {
        key: 'file_path',
        label: 'Attachment',
        render: (item: any) => {
            return item.file_path ? h('a', { href: item.file_path, target: '_blank', class: 'underline hover:text-blue-500' }, 'Download') : '-';
        },
    },
    {
        key: 'is_verified',
        label: 'Verifying',
        sortable: true,
        render: (item: any) => {
            if (user?.role?.slug === 'administrator') {
                if (item.is_verified === null)  {
                    return h('div', { class: 'flex items-center gap-1' }, [
                        h(
                            Button,
                            {
                                onClick: () => {
                                    modalShowVerified(item.id);
                                },
                                size: 'sm',
                            },
                            () => 'Verify',
                        ),
                    ]);
                } else {
                    return h('span', { class: 'text-sm font-medium text-gray-900 dark:text-gray-300' }, item.is_verified ? 'Yes' : 'No');
                }
            } else {
                return h('span', { class: 'text-sm font-medium text-gray-900 dark:text-gray-300' }, item.is_verified ? 'Yes' : 'No');
            }
        },
    },
];

const data = ref<ResponseTable>();

const optionsDynamicColumn = [
    { label: 'Participant Name', value: 'participant_name' },
    { label: 'Participant Email', value: 'participant_email' },
    { label: 'File URL', value: 'file_path' },
    { label: 'Uploaded At', value: 'uploaded_at' },
    { label: 'Verified', value: 'is_verified' },
    { label: 'Metadata', value: 'metadata' },
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
    title: 'Create Payment',
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
        router.visit(basePath + '/' + id + '/edit');
    } else if (action === 'delete') {
        modal.value.show = true;
        modal.value.type = 'delete';
        modal.value.data = { id };
        modal.value.title = 'Delete Payment';
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

const modalShowVerified = (id: number) => {
    axios
        .get(basePath + '/' + id)
        .then((response) => {
            modal.value.show = true;
            modal.value.type = 'verified';
            modal.value.data = response.data;
            modal.value.title = 'Verify Payment';
            modal.value.description = 'Are you sure you want to verify this payment? This action cannot be undone.';
        })
        .catch((error) => {
            console.error(error);
            toast.error('Failed to fetch payment data!');
        });
};

const handleVerifiedAction = (id: string, is_verified: boolean = false) => {
    axios
        .post(basePath + '/' + id + '/update-status', {
            is_verified: is_verified,
        })
        .then((response) => {
            getData();
            modal.value.show = false;
            toast.success('Payment verified successfully!');
        })
        .catch((error) => {
            console.error(error);
            toast.error('Failed to verify payment!');
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
    <Head title="Payment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto my-6 px-6">
            <div class="flex justify-end gap-1">
                <Button
                    @click="
                        () => {
                            modal.show = true;
                            modal.type = 'import';
                            modal.title = 'Import Payment';
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
                            modal.title = 'Export Payment';
                            modal.description = 'Are you sure you want to export this seminar?';
                            modal.processing = false;
                        }
                    "
                    variant="outline"
                    ><Download /> Export</Button
                >
                <Button :as="Link" href="/dashboard/payments/create">Create Payment</Button>
            </div>
        </div>

        <TableCustom
            :headers="headers"
            :data="data?.data || []"
            :pagination="data?.meta"
            :multiple-delete="true"
            :searching="true"
            table-title="Payment"
            :actions="['edit', 'delete']"
            @action="(action) => handleAction(action.action, action.id)"
            @delete="bulkDelete"
        />
    </AppLayout>

    <Dialog v-if="modal.type == 'delete'" :open="modal.show" @update:open="modal.show = $event">
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
    <Dialog v-else-if="modal.type == 'verified'" :open="modal.show" @update:open="modal.show = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ modal.title }}</DialogTitle>
                <DialogDescription>
                    {{ modal.description }}
                </DialogDescription>
            </DialogHeader>
            <div class="mb-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">Participant Name: {{ modal.data?.participant?.name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Participant Email: {{ modal.data?.participant?.email }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Uploaded At: {{ modal.data.uploaded_at }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Attachment: <a :href="modal.data.file_path" target="_blank" class="underline hover:text-blue-500">Download</a></p>
            </div>
            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="handleVerifiedAction(modal.data.id, true)"
                    :disabled="modal.processing"
                >
                    {{ modal.processing ? 'Verifying...' : 'Yes, Verifying' }}
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    @click="handleVerifiedAction(modal.data.id, false)"
                    :disabled="modal.processing"
                >
                    {{ modal.processing ? 'Unverifying...' : 'No, Unverifying' }}
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
                    <a href="/import-templates/payment.xlsx" class="text-sm text-blue-500 hover:underline">Download Template</a>
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

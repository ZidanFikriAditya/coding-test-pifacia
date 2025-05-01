<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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
import { onBeforeMount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Role Management',
        href: '/dashboard/roles',
    },
];

const headers: TableHeaderType[] = [
    {
        key: 'name',
        label: 'Name',
        sortable: true,
    },
];

const basePath = '/dashboard/role-management';
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
    title: 'Create Role',
    description: 'Are you sure you want to create a new role?',
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
    if (action === 'edit') {
        getEditData(id);
    } else if (action === 'delete') {
        modal.value.show = true;
        modal.value.type = 'delete';
        modal.value.data = { id };
        modal.value.title = 'Delete Role';
        modal.value.description = 'Are you sure you want to delete this role? This action cannot be undone.';
    }
};

const getEditData = (id: number) => {
    axios
        .get(`${basePath}/${id}`)
        .then((response) => {
            modal.value.data = response.data;
            modal.value.show = true;
            modal.value.type = 'edit';
            modal.value.title = 'Edit Role';
            modal.value.description = 'Are you sure you want to edit this role?';

            form.value = {
                name: {
                    value: response.data.name,
                    preview: null,
                    error: '',
                    required: true,
                },
            };
        })
        .catch((error) => {
            console.error(error);
        });
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

            toast.success('Role deleted successfully');
        })
        .catch((error) => {
            console.error(error);

            toast.error('Failed to delete role');
        });
};

const bulkDelete = (selectedIds: number[]) => {
    axios
        .post(basePath + '/bulk-destroy', { ids: selectedIds })
        .then((response) => {
            getData();
            tableStore.resetState();

            toast.success('Roles deleted successfully');
        })
        .catch((error) => {
            console.error(error);

            toast.error('Failed to delete roles');
        });
};

const handleSave = () => {
    const formData = new FormData();
    modal.value.processing = true;

    let hasError = false;
    for (const key in form.value) {
        if (form.value[key].required && !form.value[key].value) {
            hasError = true;
            form.value[key] = {
                ...form.value[key],
                error: 'This field is required',
            };
        }

        formData.append(key, form.value[key].value);
    }

    if (hasError) {
        return;
    }

    if (modal.value.type === 'create') {
        axios
            .post(basePath, formData)
            .then((response) => {
                getData();
                modal.value.show = false;
                modal.value.processing = false;
                tableStore.resetState();

                form.value = {};

                toast.success('Role created successfully');
            })
            .catch((error) => {
                console.error(error);

                toast.error('Failed to create role');
            });
    } else if (modal.value.type === 'edit') {
        formData.append('_method', 'PUT');
        axios
            .post(`${basePath}/${modal.value.data.id}`, formData)
            .then((response) => {
                getData();
                modal.value.show = false;
                modal.value.processing = false;
                tableStore.resetState();

                form.value = {};

                toast.success('Role edit successfully');
            })
            .catch((error) => {
                console.error(error);
                toast.error('Failed to edit role');
            });
    }
};

const handleChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        form.value = {
            ...form.value,
            [target.name]: {
                value: file,
                preview: URL.createObjectURL(file),
                error: '',
                required: target.required,
            },
        };
    } else {
        form.value = {
            ...form.value,
            [target.name]: {
                value: target.value,
                preview: null,
                error: '',
                required: target.required,
            },
        };
    }

    if (target.required && !target.value) {
        form.value = {
            ...form.value,
            [target.name]: {
                ...form.value[target.name],
                error: 'This field is required',
            },
        };
    } else {
        form.value = {
            ...form.value,
            [target.name]: {
                ...form.value[target.name],
                error: '',
            },
        };
    }
};

const handleCreateModal = () => {
    modal.value.show = true;
    modal.value.type = 'create';
    modal.value.data = {};
    modal.value.title = 'Create Role';
    modal.value.description = 'Are you sure you want to create a new role?';
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
    <Head title="Role Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto my-6 px-6">
            <div class="flex justify-end">
                <Button @click="handleCreateModal">Create Role</Button>
            </div>
        </div>

        <TableCustom
            :headers="headers"
            :data="data?.data || []"
            :pagination="data?.meta"
            :multiple-delete="true"
            :searching="true"
            table-title="Role Management"
            :actions="['edit', 'delete']"
            @action="(action) => handleAction(action.action, action.id)"
            @delete="bulkDelete"
        />
    </AppLayout>

    <Dialog :open="modal.show" @update:open="modal.show = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ modal.title }}</DialogTitle>
                <DialogDescription>
                    {{ modal.description }}
                </DialogDescription>
            </DialogHeader>
            <form v-if="modal.type === 'create' || modal.type === 'edit'" id="modal-form" @submit.prevent="handleSave">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Role Name"
                        @input="handleChange"
                        :model-value="form?.name?.value || ''"
                        :required="true"
                    />
                    <InputError :message="form?.name?.error" v-if="form?.name?.error" class="mt-2" />
                </div>
            </form>
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
                <Button v-else type="button" @click="handleSave" :disabled="modal.processing">
                    {{ modal.processing ? 'Saving...' : 'Save' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

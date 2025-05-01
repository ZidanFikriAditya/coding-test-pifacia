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
import { computed, onBeforeMount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
    roles: {
        id: number;
        name: string;
    }[];
}

const props = defineProps<Props>();

const roleParse = computed((): OptionType[] => {
    return props.roles.map((role) => ({
        value: role.id,
        label: role.name,
    }));
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'User Accounts',
        href: '/dashboard/users',
    },
];

const headers: TableHeaderType[] = [
    {
        key: 'name',
        label: 'Name',
        sortable: true
    },
    {
        key: 'email',
        label: 'Email',
        sortable: true
    },
    {
        key: 'role',
        label: 'Role',
        sortable: true
    },
];

const basePath = '/dashboard/users';
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
                order: tableStore.sorting.order
            }
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
            modal.value.title = 'Edit User';
            modal.value.description = 'Are you sure you want to edit this user?';

            const { name, email, role_id } = response.data;

            form.value = {
                name: {
                    value: name,
                    preview: null,
                    error: '',
                    required: true,
                },
                email: {
                    value: email,
                    preview: null,
                    error: '',
                    required: true,
                },
                role_id: {
                    value: role_id,
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

                toast.success('User created successfully!');
            })
            .catch((error) => {
                console.error(error);
                toast.error('Failed to create user!');

                if (error.response.status === 422) {
                    const errors = error.response?.data?.errors || {};
                    Object.keys(errors).forEach((key) => {
                        form.value = {
                            ...form.value,
                            [key]: {
                                ...(form.value?.[key] || {
                                    value: '',
                                    preview: null
                                }),
                                error: errors[key][0] || '',
                            },
                        };
                    });
                }

                modal.value.processing = false;
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

                toast.success('Successfully created user!');
            })
            .catch((error) => {
                console.error(error);
                toast.error('Failed to create user!');

                if (error.response.status === 422) {
                    const errors = error.response?.data?.errors || {};
                    Object.keys(errors).forEach((key) => {
                        form.value = {
                            ...form.value,
                            [key]: {
                                ...(form.value?.[key] || {
                                    value: '',
                                    preview: null
                                }),
                                error: errors[key][0] || '',
                            },
                        };
                    });
                }

                modal.value.processing = false;
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

    if (target.name === 'password_confirmation') {
        form.value = {
            ...form.value,
            [target.name]: {
                ...form.value[target.name],
                error: target.value !== form.value.password.value ? 'Password confirmation does not match' : '',
            },
        };
    }
};

const handleCreateModal = () => {
    modal.value.show = true;
    modal.value.type = 'create';
    modal.value.data = {};
    modal.value.title = 'Create User';
    modal.value.description = 'Are you sure you want to create a new user?';
};

const handleChangeSelect = (value: OptionType) => {
    form.value = {
        ...form.value,
        role_id: {
            value: value.value,
            preview: null,
            error: '',
            required: true,
        },
    };
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
    <Head title="User Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto my-6 px-6">
            <div class="flex justify-end">
                <Button @click="handleCreateModal">Create User</Button>
            </div>
        </div>

        <TableCustom
            :headers="headers"
            :data="data?.data || []"
            :pagination="data?.meta"
            :multiple-delete="true"
            :searching="true"
            table-title="User Accounts"
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
            <form v-if="modal.type === 'create' || modal.type === 'edit'" id="modal-form" @submit.prevent="handleSave" class="grid grid-cols-1 gap-4">
                <div>
                    <Label class="mb-1" for="name">Name</Label>
                    <Input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="User Name"
                        @input="handleChange"
                        :model-value="form?.name?.value || ''"
                        :required="true"
                    />
                    <InputError :message="form?.name?.error" v-if="form?.name?.error" class="mt-1" />
                </div>
                <div>
                    <Label class="mb-1" for="email">Email</Label>
                    <Input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="User Email"
                        @input="handleChange"
                        :model-value="form?.email?.value || ''"
                        :required="true"
                    />
                    <InputError :message="form?.email?.error" v-if="form?.email?.error" class="mt-1" />
                </div>
                <div>
                    <Label class="mb-1" for="password">Password</Label>
                    <Input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="User Password"
                        @input="handleChange"
                        :model-value="form?.password?.value || ''"
                    />
                    <InputError :message="form?.password?.error" v-if="form?.password?.error" class="mt-1" />
                </div>
                <div>
                    <Label class="mb-1" for="password_confirmation">Password Confirmation</Label>
                    <Input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Password Confirmation"
                        @input="handleChange"
                        :model-value="form?.password_confirmation?.value || ''"
                    />
                    <InputError :message="form?.password_confirmation?.error" v-if="form?.password_confirmation?.error" class="mt-1" />
                </div>
                <div>
                    <Label class="mb-1" for="role_id">Role</Label>
                    <SelectSearch
                        :default-value="form?.role_id?.value || ''"
                        placeholder="Select Role"
                        :options="roleParse"
                        @change="handleChangeSelect"
                    />
                    <InputError :message="form?.email?.error" v-if="form?.email?.error" class="mt-1" />
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

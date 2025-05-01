<script setup lang="ts">
import AuditTrail from '@/components/AuditTrail.vue';
import InputError from '@/components/InputError.vue';
import SelectSearch, { OptionType } from '@/components/SelectSearch.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus, Trash } from 'lucide-vue-next';
import moment from 'moment';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
    participant: {
        id: string;
        name: string;
        email: string;
        registered_at: string;
        seminar_id: number;
        extra_data: string[];
    };
    seminars: {
        id: number;
        title: string;
    }[];
}

const props = defineProps<Props>();
const basePath = '/dashboard/participants/' + props?.participant?.id;

const form = ref<{
    [key: string]: {
        value: any;
        preview: any;
        error: string;
        required?: boolean;
    };
}>({
    seminar_id: {
        value: props?.participant?.seminar_id || null,
        preview: null,
        error: '',
        required: true,
    },
    name: {
        value: props?.participant?.name || '',
        preview: null,
        error: '',
        required: true,
    },
    email: {
        value: props?.participant?.email || '',
        preview: null,
        error: '',
        required: true,
    },
    registered_at: {
        value: moment(props?.participant?.registered_at).format('YYYY-MM-DDTHH:mm'),
        preview: null,
        error: '',
        required: true,
    },
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'participant',
        href: '/dashboard/participants',
    },
    {
        title: 'Edit participant',
        href: '/dashboard/participants/' + props?.participant?.id,
    },
];

const extraDatas = ref<
    {
        value: string;
        error?: null | string;
    }[]
>(
    props?.participant?.extra_data?.map((item) => ({
        value: item,
        error: null,
    })) || [
        {
            value: '',
            error: null,
        },
    ],
);

const handleChange = (event: any) => {
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

const isProcessing = ref(false);

const handleSave = () => {
    isProcessing.value = true;
    const formData = new FormData();
    formData.append('_method', 'PUT');

    let hasError = false;
    for (const key in form.value) {
        if (form.value[key].required && !form.value[key].value) {
            hasError = true;
            form.value[key] = {
                ...form.value[key],
                error: 'This field is required',
            };
        }

        if (key === 'is_active') {
            formData.append(key, form.value?.[key]?.value ? '1' : '0');
        } else {
            formData.append(key, form.value[key].value);
        }
    }

    if (hasError) {
        isProcessing.value = false;
        toast.error('Please fill in all required fields!');
        return;
    }

    extraDatas.value.forEach((item, index) => {
        formData.append('extra_data[]', item.value);
    });

    axios
        .post(basePath, formData)
        .then((response) => {
            toast.success('participant created successfully!');

            setTimeout(() => {
                router.visit(basePath);
                isProcessing.value = false;
            }, 1000);
        })
        .catch((error) => {
            console.error(error);
            toast.error('Failed to create participant!');
            isProcessing.value = false;

            if (error.response.status === 422) {
                const errors = error.response?.data?.errors || {};
                Object.keys(errors).forEach((key) => {
                    form.value = {
                        ...form.value,
                        [key]: {
                            ...(form.value?.[key] || {
                                value: '',
                                preview: null,
                            }),
                            error: errors[key][0] || '',
                        },
                    };

                    // Additional info error handling
                    extraDatas.value.forEach((_, index) => {
                        if (errors[`extra_data.${index}`]) {
                            extraDatas.value[index] = {
                                ...extraDatas.value[index],
                                error: errors[`extra_data.${index}`][0] || '',
                            };
                        }
                    });
                });
            }
        });
};
</script>
<template>
    <Head title="Create participant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-4">
                <h1 class="text-2xl font-bold">Edit Participant</h1>
                <p>This form to edit participant</p>
            </div>
            <div class="grid grid-cols-4 gap-4">
                <form class="col-span-3" @submit.prevent="handleSave">
                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div>
                            <Label for="seminar_id" class="mb-2">Seminar</Label>
                            <SelectSearch
                                :options="props.seminars.map((seminar) => ({ value: seminar.id, label: seminar.title }))"
                                :default-value="form?.seminar_id?.value || null"
                                @change="
                                    (value: OptionType) =>
                                        handleChange({
                                            target: {
                                                name: 'seminar_id',
                                                value: value?.value,
                                            },
                                        })
                                "
                            />
                            <InputError v-if="form?.seminar_id?.error" :message="form.seminar_id.error" class="mt-2" />
                        </div>
                        <div>
                            <Label for="name" class="mb-2">Name</Label>
                            <Input
                                type="text"
                                name="name"
                                id="name"
                                placeholder="Enter name here..."
                                :model-value="form?.name?.value || ''"
                                @input="handleChange"
                                :required="true"
                            />
                            <InputError v-if="form?.name?.error" :message="form.name.error" class="mt-2" />
                        </div>
                        <div>
                            <Label for="email" class="mb-2">Email</Label>
                            <Input
                                type="text"
                                name="email"
                                id="email"
                                placeholder="Enter email here..."
                                :model-value="form?.email?.value || ''"
                                @input="handleChange"
                                :required="true"
                            />
                            <InputError v-if="form?.email?.error" :message="form.email.error" class="mt-2" />
                        </div>
                        <div>
                            <Label for="registered_at" class="mb-2">Registered At</Label>
                            <Input
                                type="datetime-local"
                                name="registered_at"
                                id="registered_at"
                                :model-value="form?.registered_at?.value || ''"
                                @input="handleChange"
                                :required="true"
                            />
                            <InputError v-if="form?.registered_at?.error" :message="form.registered_at.error" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <div class="mb-3 grid grid-cols-4 items-end gap-3" v-for="(extraData, index) in extraDatas" :key="`extra_data${index}`">
                                <div class="col-span-3">
                                    <Label for="title" class="mb-2">Extra Data {{ index + 1 }}</Label>
                                    <Input
                                        type="text"
                                        :name="`extra_data${index}`"
                                        id="title"
                                        :model-value="extraData?.value || ''"
                                        @update:model-value="(value) => (extraDatas[index] = { ...extraDatas[index], value: value.toString() })"
                                        :placeholder="`Extra Data ${index + 1}`"
                                    />
                                    <InputError v-if="extraData?.error" :message="extraData?.error" class="mt-2" />
                                </div>
                                <div class="col-span-1 inline-flex items-center gap-2">
                                    <Button
                                        type="button"
                                        v-if="index === extraDatas.length - 1"
                                        @click="
                                            extraDatas.push({
                                                value: '',
                                                error: null,
                                            })
                                        "
                                        ><Plus />
                                    </Button>
                                    <Button type="button" v-if="extraDatas.length > 1" @click="extraDatas.splice(index, 1)" variant="destructive"
                                        ><Trash />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <Button type="submit" :disabled="isProcessing">Submit</Button>
                        </div>
                    </div>
                </form>
                <div>
                    <AuditTrail slug="participants" :id="props?.participant?.id" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

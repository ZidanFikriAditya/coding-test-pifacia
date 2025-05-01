<script setup lang="ts">
import AuditTrail from '@/components/AuditTrail.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus, Trash } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    seminar: {
        id: string;
        title: string;
        schedule: string;
        description: string;
        is_active: boolean;
        additional_info: string[];
    };
}>();

const form = ref<{
    [key: string]: {
        value: any;
        preview: any;
        error: string;
        required?: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Seminar',
        href: '/dashboard/seminars',
    },
    {
        title: 'Edit Seminar',
        href: '/dashboard/seminars/' + props.seminar.id,
    },
];

const basePath = '/dashboard/seminars';

const termAndConditions = ref<
    {
        value: string;
        error?: null | string;
    }[]
>(
    props.seminar?.additional_info?.map((item) => ({
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

    termAndConditions.value.forEach((item, index) => {
        formData.append('additional_info[]', item.value);
    });

    axios
        .post(basePath + '/' + props.seminar.id, formData)
        .then((response) => {
            toast.success('Seminar Edited successfully!');

            setTimeout(() => {
                router.visit(basePath);
                isProcessing.value = false;
            }, 1000);
        })
        .catch((error) => {
            console.error(error);
            toast.error('Failed to edit seminar!');
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
                    termAndConditions.value.forEach((_, index) => {
                        if (errors[`additional_info.${index}`]) {
                            termAndConditions.value[index] = {
                                ...termAndConditions.value[index],
                                error: errors[`additional_info.${index}`][0] || '',
                            };
                        }
                    });
                });
            }
        });
};

onMounted(() => {
    form.value = {
        title: {
            value: props.seminar?.title || '',
            preview: null,
            error: '',
            required: true,
        },
        schedule: {
            value: props.seminar?.schedule || '',
            preview: null,
            error: '',
            required: true,
        },
        description: {
            value: props.seminar?.description || '',
            preview: null,
            error: '',
            required: true,
        },
        is_active: {
            value: props.seminar?.is_active ? true : false,
            preview: null,
            error: '',
        },
    };
});
</script>
<template>
    <Head title="Edit Seminar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-4">
                <h1 class="text-2xl font-bold">Edit Seminar</h1>
                <p>This form to create seminar</p>
            </div>
            <div class="grid grid-cols-4 gap-4">
                <form class="col-span-3" @submit.prevent="handleSave">
                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div>
                            <Label for="title" class="mb-2">Title</Label>
                            <Input
                                type="text"
                                name="title"
                                id="title"
                                placeholder="Enter title here..."
                                :model-value="form?.title?.value || ''"
                                @input="handleChange"
                                :required="true"
                            />
                            <InputError v-if="form?.title?.error" :message="form.title.error" class="mt-2" />
                        </div>
                        <div>
                            <Label for="schedule" class="mb-2">Schedule</Label>
                            <Input
                                type="datetime-local"
                                name="schedule"
                                id="schedule"
                                :model-value="form?.schedule?.value || ''"
                                @input="handleChange"
                                :required="true"
                            />
                            <InputError v-if="form?.schedule?.error" :message="form.schedule.error" class="mt-2" />
                        </div>
                        <div class="col-span-1">
                            <Label for="description" class="mb-2">Description</Label>
                            <Textarea
                                type="text"
                                name="description"
                                id="description"
                                placeholder="Enter description here..."
                                :model-value="form?.description?.value || ''"
                                @input="handleChange"
                                rows="5"
                            />
                            <InputError v-if="form?.description?.error" :message="form.description.error" class="mt-2" />
                        </div>
                        <div>
                            <Label for="is_active" class="mb-2">Activate</Label>
                            <Switch
                                :model-value="form?.is_active?.value || false"
                                @update:model-value="(value) => handleChange({ target: { name: 'is_active', value } })"
                            />
                            <InputError v-if="form?.is_active?.error" :message="form.is_active.error" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <div
                                class="mb-3 grid grid-cols-4 items-end gap-3"
                                v-for="(termAndCondition, index) in termAndConditions"
                                :key="`term_and_condition_${index}`"
                            >
                                <div class="col-span-3">
                                    <Label for="title" class="mb-2">Term And Condition {{ index + 1 }}</Label>
                                    <Input
                                        type="text"
                                        :name="`term_and_condition_${index}`"
                                        id="title"
                                        :model-value="termAndCondition?.value || ''"
                                        @update:model-value="
                                            (value) => (termAndConditions[index] = { ...termAndConditions[index], value: value.toString() })
                                        "
                                        :placeholder="`Term And Condition ${index + 1}`"
                                    />
                                    <InputError v-if="termAndCondition?.error" :message="termAndCondition?.error" class="mt-2" />
                                </div>
                                <div class="col-span-1 inline-flex items-center gap-2">
                                    <Button
                                        type="button"
                                        v-if="index === termAndConditions.length - 1"
                                        @click="
                                            termAndConditions.push({
                                                value: '',
                                                error: null,
                                            })
                                        "
                                        ><Plus />
                                    </Button>
                                    <Button
                                        type="button"
                                        v-if="termAndConditions.length > 1"
                                        @click="termAndConditions.splice(index, 1)"
                                        variant="destructive"
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
                    <AuditTrail slug="seminars" :id="props.seminar.id" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

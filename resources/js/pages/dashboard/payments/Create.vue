<script setup lang="ts">
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
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import moment from 'moment';

interface Props {
    participants: {
        id: number;
        name: string;
    }[];
}

const props = defineProps<Props>();
const basePath = '/dashboard/payments';

const form = ref<{
    [key: string]: {
        value: any;
        preview: any;
        error: string;
        required?: boolean;
    };
}>({
    uploaded_at: {
        value: moment().format('YYYY-MM-DDTHH:mm'),
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
        title: 'Payment',
        href: '/dashboard/payments',
    },
    {
        title: 'Create',
        href: '/dashboard/payments/create',
    },
];

const metaDatas = ref<
    {
        value: string;
        error?: null | string;
    }[]
>([
    {
        value: '',
        error: null,
    },
]);

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

    metaDatas.value.forEach((item, index) => {
        formData.append('extra_data[]', item.value);
    });

    axios
        .post(basePath, formData)
        .then((response) => {
            toast.success('Payment created successfully!');

            setTimeout(() => {
                router.visit(basePath);
                isProcessing.value = false;
            }, 1000);
        })
        .catch((error) => {
            console.error(error);
            toast.error('Failed to create payment!');
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
                    metaDatas.value.forEach((_, index) => {
                        if (errors[`extra_data.${index}`]) {
                            metaDatas.value[index] = {
                                ...metaDatas.value[index],
                                error: errors[`extra_data.${index}`][0] || '',
                            };
                        }
                    });
                });
            }
        });
};

const getLink = computed(() => form?.value?.file_path?.value ? URL.createObjectURL(form?.value?.file_path?.value) : form.value?.file_path?.preview);


</script>
<template>
    <Head title="Create Payment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-4">
                <h1 class="text-2xl font-bold">Create Payment</h1>
                <p>This form to create payment</p>
            </div>
            <form @submit.prevent="handleSave">
                <div class="mt-5 grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <Label for="participant_id" class="mb-2">Participant</Label>
                        <SelectSearch 
                            :options="props.participants.map((seminar) => ({ value: seminar.id, label: seminar.name }))"
                            :default-value="form?.participant_id?.value || null"
                            @change="(value: OptionType) => handleChange({
                                target: {
                                    name: 'participant_id',
                                    value: value?.value,
                                }
                            })"
                        />
                        <InputError v-if="form?.participant_id?.error" :message="form.seminar_id.error" class="mt-2" />
                    </div>
                    <div>
                        <Label for="file_path" class="mb-2">File</Label>
                        <Input
                            type="file"
                            name="file_path"
                            id="file_path"
                            placeholder="Enter file here..."
                            @input="handleChange"
                            :required="true"
                        />
                        <div v-if="getLink" class="mt-2">
                            <a :href="getLink" target="_blank" class="text-blue-500 underline">Preview</a>
                        </div>
                        <InputError v-if="form?.name?.error" :message="form.name.error" class="mt-2" />
                    </div>
                    <div>
                        <Label for="uploaded_at" class="mb-2">Uploaded At</Label>
                        <Input
                            type="datetime-local"
                            name="uploaded_at"
                            id="uploaded_at"
                            :model-value="form?.uploaded_at?.value || ''"
                            @input="handleChange"
                            :required="true"
                        />
                        <InputError v-if="form?.uploaded_at?.error" :message="form.uploaded_at.error" class="mt-2" />
                    </div>
                    <div class="col-span-2">
                        <div
                            class="mb-3 grid grid-cols-4 items-end gap-3"
                            v-for="(metadata, index) in metaDatas"
                            :key="`extra_data${index}`"
                        >
                            <div class="col-span-3">
                                <Label for="title" class="mb-2">Metadata {{ index + 1 }}</Label>
                                <Input
                                    type="text"
                                    :name="`extra_data${index}`"
                                    id="title"
                                    :model-value="metadata?.value || ''"
                                    @update:model-value="
                                        (value) => (metaDatas[index] = { ...metaDatas[index], value: value.toString() })
                                    "
                                    :placeholder="`Metadata ${index + 1}`"
                                />
                                <InputError v-if="metadata?.error" :message="metadata?.error" class="mt-2" />
                            </div>
                            <div class="col-span-1 inline-flex items-center gap-2">
                                <Button
                                    type="button"
                                    v-if="index === metaDatas.length - 1"
                                    @click="
                                        metaDatas.push({
                                            value: '',
                                            error: null,
                                        })
                                    "
                                    ><Plus />
                                </Button>
                                <Button
                                    type="button"
                                    v-if="metaDatas.length > 1"
                                    @click="metaDatas.splice(index, 1)"
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
        </div>
    </AppLayout>
</template>

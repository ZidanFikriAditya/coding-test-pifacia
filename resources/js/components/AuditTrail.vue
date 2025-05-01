<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { Card, CardContent } from './ui/card';

interface Props {
    slug: string;
    id?: string;
}

const props = defineProps<Props>();
const activities = ref<
    {
        id: number;
        event: string;
        user: string;
        created_at: string;
        human_readable: string;
    }[]
>([]);
const meta = ref<{
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}>();

const currentPage = ref(1);

const getData = () => {
    axios
        .get('/dashboard/audits/data/' + props.slug + '/' + props.id, {
            params: {
                page: currentPage.value,
            },
        })
        .then((response) => {
            activities.value?.push(...response.data?.data || []);
            meta.value = response.data?.meta || {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
            };

            currentPage.value = meta.value?.currentPage || 1;
        })
        .catch((error) => {
            console.error('Error fetching activity log:', error);
        });
};

onMounted(() => {
    getData();
});
</script>

<template>
    <div class="activity-log">
        <div class="card">
            <div class="card-header">
                <h3>Activity Log</h3>
            </div>
            <div class="card-body">
                <div class="py-2 text-sm">
                    <Card v-for="(activity, index) in activities" :key="`activity_${index}`" class="mb-2">
                        <CardContent>
                            <p>
                                <strong>{{ activity.user }}</strong> {{ activity.event }} this {{ activity.human_readable }}
                            </p>
                        </CardContent>
                    </Card>
                    <div v-if="activities?.length === 0" class="text-center text-gray-500">No activity log available.</div>
                    <div v-if="(meta?.total || 1) > (meta?.perPage || 10) && currentPage < (meta?.lastPage || 1)" class="mt-4 flex justify-center">
                        <button
                            @click="
                                () => {
                                    currentPage++;
                                    getData();
                                }
                            "
                            class="btn btn-primary"
                        >
                            Load More
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

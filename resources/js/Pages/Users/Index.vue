<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

defineProps<{
    users: User[];
}>();

const getSeverity = (role: string) => {
    switch (role) {
        case 'admin': return 'danger';
        case 'PAID': return 'success';
        default: return 'info';
    }
};
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-2xl font-semibold mb-6">Users Management</h2>
                    
                    <DataTable :value="users" stripedRows>
                        <Column field="name" header="Name" sortable />
                        <Column field="email" header="Email" sortable />
                        <Column field="role" header="Role" sortable>
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.role" :severity="getSeverity(slotProps.data.role)" />
                            </template>
                        </Column>
                        <Column field="created_at" header="Joined" sortable>
                            <template #body="slotProps">
                                {{ new Date(slotProps.data.created_at).toLocaleDateString() }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

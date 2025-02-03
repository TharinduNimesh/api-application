<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ApiCard from "@/Components/ApiCard.vue";
import { ref, computed } from "vue";
import ApiFilterBar from "@/Components/ApiFilterBar.vue";
import Paginator from "primevue/paginator";
import { Link } from "@inertiajs/vue3";

// Extended sample APIs
const apis = ref([
    {
        id: "1",
        name: "User Management API",
        description:
            "Complete user management system with authentication and authorization.",
        type: "PAID" as const,
        endpointCount: 12,
        createdAt: "2023-08-15",
    },
    {
        id: "2",
        name: "Weather API",
        description: "Real-time weather data for locations worldwide.",
        type: "FREE" as const,
        endpointCount: 5,
        createdAt: "2023-08-10",
    },
    {
        id: "3",
        name: "Payment Gateway API",
        description:
            "Secure payment processing with support for multiple payment providers and currencies.",
        type: "PAID" as const,
        endpointCount: 18,
        createdAt: "2023-08-08",
    },
    {
        id: "4",
        name: "Email Service API",
        description:
            "Comprehensive email sending and template management system.",
        type: "FREE" as const,
        endpointCount: 8,
        createdAt: "2023-08-05",
    },
    {
        id: "5",
        name: "Authentication API",
        description:
            "Complete authentication system with OAuth2 and social login support.",
        type: "PAID" as const,
        endpointCount: 15,
        createdAt: "2023-08-03",
    },
    {
        id: "6",
        name: "File Storage API",
        description:
            "Cloud storage solution with automatic backup and version control.",
        type: "PAID" as const,
        endpointCount: 10,
        createdAt: "2023-08-01",
    },
    {
        id: "7",
        name: "Analytics API",
        description:
            "Real-time analytics and reporting system with customizable dashboards.",
        type: "FREE" as const,
        endpointCount: 12,
        createdAt: "2023-07-28",
    },
    {
        id: "8",
        name: "Notification API",
        description:
            "Multi-channel notification system supporting push, SMS, and email.",
        type: "FREE" as const,
        endpointCount: 6,
        createdAt: "2023-07-25",
    },
]);

const filters = ref({
    search: "",
    type: "ALL",
    sort: "name",
});

// Pagination state
const pagination = ref({
    rows: 6,
    first: 0,
    totalRecords: 0,
});

// Modified computed for pagination
const filteredApis = computed(() => {
    const filtered = apis.value
        .filter((api) => {
            const matchesSearch = api.name
                .toLowerCase()
                .includes(filters.value.search.toLowerCase());
            const matchesType =
                filters.value.type === "ALL" || api.type === filters.value.type;
            return matchesSearch && matchesType;
        })
        .sort((a, b) => {
            const isDesc = filters.value.sort.startsWith("-");
            const field = isDesc
                ? filters.value.sort.slice(1)
                : filters.value.sort;
            const direction = isDesc ? -1 : 1;

            return a[field] > b[field] ? direction : -direction;
        });

    // Update total records for pagination
    pagination.value.totalRecords = filtered.length;

    // Return paginated results
    return filtered.slice(
        pagination.value.first,
        pagination.value.first + pagination.value.rows
    );
});

const onPageChange = (event: { first: number; rows: number; page: number }) => {
    pagination.value.first = event.first;
    pagination.value.rows = event.rows;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-7">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-5">
                    <h2
                        class="text-2xl font-semibold leading-tight text-gray-800 font-display"
                    >
                        Available APIs
                    </h2>
                    <Link :href="route('api.create')">
                        <Button
                            label="Create New API"
                            icon="pi pi-plus"
                            severity="contrast"
                            class="font-medium"
                        />
                    </Link>
                </div>

                <!-- Replace old filters with new component -->
                <div class="mb-6">
                    <ApiFilterBar v-model="filters" />
                </div>

                <!-- Results count -->
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Showing
                        <span class="font-medium text-gray-900">
                            {{ pagination.first + 1 }} -
                            {{
                                Math.min(
                                    pagination.first + pagination.rows,
                                    pagination.totalRecords
                                )
                            }}
                        </span>
                        of
                        <span class="font-medium text-gray-900">
                            {{ pagination.totalRecords }}
                        </span>
                        APIs
                    </p>
                </div>

                <!-- API Grid -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6"
                >
                    <ApiCard
                        v-for="api in filteredApis"
                        :key="api.id"
                        v-bind="api"
                    />
                </div>

                <!-- Pagination -->
                <Paginator
                    v-if="pagination.totalRecords > pagination.rows"
                    v-model:first="pagination.first"
                    v-model:rows="pagination.rows"
                    :totalRecords="pagination.totalRecords"
                    :rowsPerPageOptions="[6, 12, 24, 48]"
                    @page="onPageChange"
                    class="border border-gray-100 rounded-lg bg-white"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

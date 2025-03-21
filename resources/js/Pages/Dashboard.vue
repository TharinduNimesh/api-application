<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ApiCard from "@/Components/ApiCard.vue";
import { ref, computed, onMounted } from "vue";
import ApiFilterBar from "@/Components/ApiFilterBar.vue";
import Paginator from "primevue/paginator";
import { Link, usePage } from "@inertiajs/vue3";
import axios from 'axios';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from "primevue/usetoast";
import type { Api as ApiType } from '@/types/api';
import CreateApiModal from "@/Components/CreateApiModal.vue";
import Button from "primevue/button";

type SortableFields = keyof Pick<ApiType, 'name' | 'createdAt'>;

// Update the interface to include specific sort fields
interface ApiFilters {
  search: string;
  type: "ALL" | ApiType['type'];
  status: "ALL" | ApiType['status'];
  sort: `${'-' | ''}${SortableFields}`;
}

const apis = ref<ApiType[]>([]);
// Add loading state
const loading = ref(true);

// Add fetch function
const fetchApis = async () => {
  try {
    loading.value = true;
    const response = await axios.get(route('api.list'), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    apis.value = response.data;
  } catch (error: any) {
    console.error('Error fetching APIs:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load APIs. ' + (error?.response?.data?.message || 'Please try again later.'),
      life: 5000
    });
    apis.value = []; // Reset APIs on error
  } finally {
    loading.value = false;
  }
};

// Fetch APIs on component mount
onMounted(() => {
  fetchApis();
});

const page = usePage();

// Update the filters ref
const filters = ref<ApiFilters>({
  search: "",
  type: "ALL",
  status: "ALL",
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
      const matchesStatus =
        filters.value.status === "ALL" || api.status === filters.value.status;
        
      return matchesSearch && matchesType && matchesStatus;
    })
    .sort((a, b) => {
      const isDesc = filters.value.sort.startsWith("-");
      const field = (isDesc ? filters.value.sort.slice(1) : filters.value.sort) as SortableFields;
      const direction = isDesc ? -1 : 1;

      if (field === 'name') {
        return a.name.localeCompare(b.name) * direction;
      }
      
      if (field === 'createdAt') {
        return (new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime()) * direction;
      }

      return 0;
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

const hasApis = computed(() => apis.value.length > 0);
const hasFilteredResults = computed(() => filteredApis.value.length > 0);
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

const toast = useToast();

const handleActivateApi = async (apiId: string) => {
    try {
        await axios.patch(route('api.activate', apiId));
        await fetchApis(); // Refresh the list
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'API activated successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error activating API:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to activate API',
            life: 3000
        });
    }
};

const showCreateModal = ref(false);
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
          <div v-if="$page.props.auth.user.role === 'admin'">
            <Button
              label="Create New API"
              icon="pi pi-plus"
              severity="contrast"
              class="font-medium"
              @click="showCreateModal = true"
            />
            <CreateApiModal v-model:visible="showCreateModal" @refresh="fetchApis" />
          </div>
        </div>

        <!-- Replace old filters with new component -->
        <div class="mb-6">
          <ApiFilterBar v-model="filters" />
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <ProgressSpinner />
        </div>

        <template v-else>
          <!-- Empty States -->
          <div v-if="!hasApis" class="text-center py-12">
            <div class="space-y-6">
              <div class="flex justify-center">
                <i class="pi pi-database text-4xl text-gray-400" />
              </div>
              <div class="space-y-2">
                <h3 class="text-lg font-medium text-gray-900">No APIs Available</h3>
                <p class="text-gray-500 max-w-sm mx-auto">
                  {{ isAdmin 
                    ? "Start by creating your first API to make it available to users."
                    : "No APIs are currently available. Please check back later."
                  }}
                </p>
              </div>
              <div v-if="isAdmin">
                <Link :href="route('api.create')">
                  <Button
                    label="Create New API"
                    icon="pi pi-plus"
                    severity="primary"
                    :link="true"
                  />
                </Link>
              </div>
            </div>
          </div>

          <div v-else-if="!hasFilteredResults" class="text-center py-12">
            <div class="space-y-6">
              <div class="flex justify-center">
                <i class="pi pi-filter-slash text-4xl text-gray-400" />
              </div>
              <div class="space-y-2">
                <h3 class="text-lg font-medium text-gray-900">No Matching Results</h3>
                <p class="text-gray-500 max-w-sm mx-auto">
                  No APIs match your current filters. Try adjusting or clearing your filters.
                </p>
              </div>
              <div>
                <Button
                  label="Clear All Filters"
                  icon="pi pi-filter-slash"
                  severity="secondary"
                  text
                  @click="filters = {
                    search: '',
                    type: 'ALL',
                    status: 'ALL',
                    sort: 'name'
                  }"
                />
              </div>
            </div>
          </div>

          <!-- Results Display -->
          <template v-else>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
              <ApiCard 
                v-for="api in filteredApis" 
                :key="api.id" 
                v-bind="api" 
                :onActivate="isAdmin ? handleActivateApi : undefined" 
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
          </template>
        </template>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

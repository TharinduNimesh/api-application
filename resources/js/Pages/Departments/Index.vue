<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import InputText from "primevue/inputtext";
import Menu from "primevue/menu";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputGroup from "primevue/inputgroup";
import InputGroupAddon from "primevue/inputgroupaddon";
import { ref, computed, onMounted } from "vue";
import axios, { AxiosError } from 'axios';
import { useToast } from "primevue/usetoast";
import CreateDepartmentModal from '@/Components/Departments/CreateDepartmentModal.vue';

const toast = useToast();

interface Department {
  id: string; // MongoDB uses string IDs
  name: string;
  description: string;
  is_active: boolean;
  api_assignments: {
    id: string;
    permissions: {
      rate_limit: number;
    };
  }[];
  user_assignments: string[];
  created_at: string;
  created_by: string;
}

interface DepartmentFilters {
  search: string;
  status: "ALL" | "active" | "inactive";
}

interface DepartmentForm {
  name: string;
  description: string;
  apiAssignments: {
    apiId: string;
    rateLimit: number;
  }[];
}

interface ApiOption {
  id: string;
  name: string;
  type: 'FREE' | 'PAID';
  status: 'ACTIVE' | 'INACTIVE';
}

const props = defineProps<{
  departments: Department[];
}>();

const departments = ref<Department[]>(props.departments);

const filters = ref<DepartmentFilters>({
  search: "",
  status: "ALL"
});

const statusOptions = [
  { label: "All Status", value: "ALL" },
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" }
];

const menuRef = ref();
const selectedDepartment = ref<Department | null>(null);
const createModalRef = ref();
const apis = ref<ApiOption[]>([]);

const filteredDepartments = computed(() => {
  return departments.value.filter((dept) => {
    const searchTerm = filters.value.search.toLowerCase();
    const matchesSearch = !searchTerm || 
      (dept?.name?.toLowerCase().includes(searchTerm) || 
       dept?.description?.toLowerCase().includes(searchTerm));
    
    // Handle is_active boolean mapping to status string
    const deptStatus = dept.is_active ? 'active' : 'inactive';
    const matchesStatus = filters.value.status === "ALL" || deptStatus === filters.value.status;
    
    return matchesSearch && matchesStatus;
  });
});

const menuItems = computed(() => [
  {
    label: 'More Information',
    icon: 'pi pi-info-circle',
    command: () => {
      window.location.href = route('departments.show', selectedDepartment.value?.id);
    }
  },
  {
    label: selectedDepartment.value?.is_active ? 'Deactivate' : 'Activate',
    icon: selectedDepartment.value?.is_active ? 'pi pi-power-off' : 'pi pi-check',
    class: selectedDepartment.value?.is_active ? 'text-orange-500' : 'text-green-500',
    command: async () => {
      if (selectedDepartment.value) {
        try {
          const response = await axios.patch(route('departments.toggle-status', selectedDepartment.value.id));
          const index = departments.value.findIndex(d => d.id === selectedDepartment.value?.id);
          if (index !== -1) {
            departments.value[index] = response.data.department;
          }
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Department ${selectedDepartment.value.is_active ? 'deactivated' : 'activated'} successfully`,
            life: 3000
          });
        } catch (error) {
          console.error('Failed to toggle department status:', error);
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update department status',
            life: 5000
          });
        }
      }
    }
  }
]);

const toggleMenu = (event: Event, department: Department) => {
  selectedDepartment.value = department;
  menuRef.value?.toggle(event);
};

const getStatusSeverity = (isActive: boolean) => {
  return isActive ? 'success' : 'danger';
};

const emptyMessage = computed(() => {
  if (departments.value.length > 0 && filteredDepartments.value.length === 0) {
    if (filters.value.search) {
      return `No departments found matching "${filters.value.search}"`;
    }
    if (filters.value.status !== 'ALL') {
      return `No ${filters.value.status} departments found`;
    }
    return 'No departments match the selected filters';
  }
  return 'No departments available';
});

const clearFilters = () => {
  filters.value = {
    search: '',
    status: 'ALL'
  };
};

const createDepartment = () => {
  createModalRef.value?.show();
};

const fetchApis = async () => {
  try {
    const response = await axios.get(route('api.list'));
    apis.value = response.data.filter((api: ApiOption) => api.status === 'ACTIVE');
  } catch (error) {
    console.error('Error fetching APIs:', error);
  }
};

onMounted(() => {
  fetchApis();
});

const handleCreateSubmit = async (form: DepartmentForm) => {
  try {
    const response = await axios.post(route('departments.store'), form);
    departments.value.unshift(response.data.department);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Department created successfully',
      life: 3000
    });
  } catch (error: unknown) {
    console.error('Failed to create department:', error);
    const axiosError = error as AxiosError<{message: string}>;
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: axiosError.response?.data?.message || 'Failed to create department',
      life: 5000
    });
  }
};
</script>

<template>
  <Head title="Departments" />

  <AuthenticatedLayout>
    <div class="py-7">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-2xl font-semibold leading-tight text-gray-800 font-display">
            Departments Management
          </h2>
          <Button
            label="Create Department"
            icon="pi pi-plus"
            severity="primary"
            @click="createDepartment"
          />
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <InputGroup class="w-full">
              <InputGroupAddon>
                <i class="pi pi-search text-gray-400"></i>
              </InputGroupAddon>
              <InputText
                v-model="filters.search"
                placeholder="Search departments..."
                class="w-full"
              />
            </InputGroup>
            <Dropdown
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select Status"
              class="w-full"
            />
          </div>
        </div>

        <!-- Departments Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <DataTable
            :value="filteredDepartments"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50]"
            stripedRows
            class="p-4"
          >
            <!-- Empty state template -->
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-building text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">{{ emptyMessage }}</p>
                <div v-if="filters.search || filters.status !== 'ALL'" class="mt-2">
                  <Button
                    label="Clear Filters"
                    severity="secondary"
                    text
                    @click="clearFilters"
                  />
                </div>
              </div>
            </template>

            <Column field="name" header="Department Name" sortable>
              <template #body="{ data }">
                <div class="flex flex-col gap-1">
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ data.name }}</span>
                    <Tag 
                      :value="data.is_active ? 'Active' : 'Inactive'"
                      :severity="getStatusSeverity(data.is_active)"
                    />
                  </div>
                  <div class="text-sm text-gray-500">{{ data.description }}</div>
                </div>
              </template>
            </Column>

            <Column field="user_assignments" header="Active Users" sortable>
              <template #body="{ data }">
                <Tag :value="(data.user_assignments?.length || 0).toString()" severity="info" />
              </template>
            </Column>

            <Column field="api_assignments" header="Active APIs" sortable>
              <template #body="{ data }">
                <Tag :value="(data.api_assignments?.length || 0).toString()" severity="success" />
              </template>
            </Column>

            <Column field="created_at" header="Created At" sortable>
              <template #body="{ data }">
                {{ new Date(data.created_at).toLocaleDateString() }}
              </template>
            </Column>

            <Column header="Actions" style="width: 5rem">
              <template #body="{ data }">
                <Button
                  icon="pi pi-ellipsis-h"
                  severity="secondary"
                  text
                  rounded
                  @click="toggleMenu($event, data)"
                  aria-controls="department_menu"
                  aria-haspopup="true"
                />
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Context Menu -->
        <Menu ref="menuRef" :model="menuItems" :popup="true" />

        <CreateDepartmentModal
          ref="createModalRef"
          :apis="apis"
          @submit="handleCreateSubmit"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
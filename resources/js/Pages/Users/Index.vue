<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";

interface UserFilters {
  search: string;
  role: string;
  trialStatus: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  created_at: string;
}

const props = defineProps<{
  users: User[];
}>();

const filters = ref<UserFilters>({
  search: "",
  role: "ALL",
  trialStatus: "ALL",
});

const roleOptions = [
  { label: "All Roles", value: "ALL" },
  { label: "Admin", value: "admin" },
  { label: "User", value: "user" },
];

const trialOptions = [
  { label: "All Users", value: "ALL" },
  { label: "Trial Active", value: "TRIAL" },
  { label: "Trial Expired", value: "EXPIRED" },
];

const isInTrialPeriod = (createdAt: string) => {
  const trialPeriod = 15 * 24 * 60 * 60 * 1000; // 15 days in milliseconds
  const creationDate = new Date(createdAt).getTime();
  const now = Date.now();
  return now - creationDate < trialPeriod;
};

const filteredUsers = computed(() => {
  return props.users.filter((user) => {
    const matchesSearch =
      user.name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      user.email.toLowerCase().includes(filters.value.search.toLowerCase());
      
    const matchesRole =
      filters.value.role === "ALL" || user.role === filters.value.role;

    // Modified trial status filtering:
    let matchesTrialStatus = false;
    if (filters.value.trialStatus === "ALL") {
      matchesTrialStatus = true;
    } else if (user.role === "admin") {
      matchesTrialStatus = false;
    } else if (filters.value.trialStatus === "TRIAL") {
      matchesTrialStatus = isInTrialPeriod(user.created_at);
    } else if (filters.value.trialStatus === "EXPIRED") {
      matchesTrialStatus = !isInTrialPeriod(user.created_at);
    }

    return matchesSearch && matchesRole && matchesTrialStatus;
  });
});

const getSeverity = (role: string) => {
  switch (role) {
    case "admin":
      return "danger";
    case "user":
      return "info";
    default:
      return "secondary";
  }
};

const getTrialStatus = (user: User) => {
  if (user.role === 'admin') return null;
  
  if (isInTrialPeriod(user.created_at)) {
    return { label: "Trial Active", severity: "success" };
  }
  return { label: "Trial Expired", severity: "warning" };
};

const emptyMessage = computed(() => {
    // If there are users but none match the filters
    if (props.users.length > 0 && filteredUsers.value.length === 0) {
        if (filters.value.search) {
            return `No users found matching "${filters.value.search}"`;
        }
        if (filters.value.trialStatus !== 'ALL') {
            return `No ${filters.value.trialStatus.toLowerCase()} users found`;
        }
        if (filters.value.role !== 'ALL') {
            return `No ${filters.value.role.toLowerCase()} users found`;
        }
        return 'No users match the selected filters';
    }
    // If there are no users at all
    return 'No users available';
});
</script>

<template>
  <Head title="Users" />

  <AuthenticatedLayout>
    <div class="py-7">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-5">
          <h2
            class="text-2xl font-semibold leading-tight text-gray-800 font-display"
          >
            Users Management
          </h2>
        </div>

        <!-- Filters -->
        <div
          class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200"
        >
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <InputGroup class="w-full">
              <InputGroupAddon>
                <i class="pi pi-search text-gray-400"></i>
              </InputGroupAddon>
              <InputText
                v-model="filters.search"
                placeholder="Search users..."
                class="w-full"
              />
            </InputGroup>
            <Dropdown
              v-model="filters.role"
              :options="roleOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select Role"
              class="w-full"
            />
            <Dropdown
              v-model="filters.trialStatus"
              :options="trialOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Trial Status"
              class="w-full"
            />
          </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <DataTable
            :value="filteredUsers"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50]"
            stripedRows
            class="p-4"
          >
            <!-- Add empty template -->
            <template #empty>
                <div class="text-center py-8">
                    <i class="pi pi-users text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">{{ emptyMessage }}</p>
                    <div v-if="filters.search || filters.role !== 'ALL' || filters.trialStatus !== 'ALL'" class="mt-2">
                        <Button
                            label="Clear Filters"
                            severity="secondary"
                            text
                            @click="filters = { search: '', role: 'ALL', trialStatus: 'ALL' }"
                        />
                    </div>
                </div>
            </template>

            <Column field="name" header="Name" sortable>
              <template #body="slotProps">
                <div class="flex flex-col gap-1">
                  <div class="font-medium">{{ slotProps.data.name }}</div>
                  <Tag
                    v-if="getTrialStatus(slotProps.data)"
                    :value="getTrialStatus(slotProps.data)?.label"
                    :severity="getTrialStatus(slotProps.data)?.severity"
                    size="small"
                  />
                </div>
              </template>
            </Column>
            <Column field="email" header="Email" sortable />
            <Column field="role" header="Role" sortable>
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.role"
                  :severity="getSeverity(slotProps.data.role)"
                />
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

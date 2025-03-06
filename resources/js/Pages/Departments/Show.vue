<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Divider from 'primevue/divider';
import Message from 'primevue/message';

const toast = useToast();

const editingRows = ref([]);
const editingRateLimits = ref<{ [key: string]: number }>({});
const savingRateLimit = ref<{ [key: string]: boolean }>({});

const props = defineProps<{
  department: {
    id: string;
    name: string;
    description: string;
    is_active: boolean;
    api_assignments: {
      apiId: string;
      api: {
        id: string;
        name: string;
        type: 'FREE' | 'PAID';
        description: string;
      };
      rateLimit: number;
    }[];
    user_assignments: {
      userId: string;
      user: {
        id: string;
        name: string;
        email: string;
        role: string;
      };
    }[];
    created_at: string;
    created_by: string;
  };
  available_apis: {
    id: string;
    name: string;
    type: 'FREE' | 'PAID';
    description: string;
  }[];
  available_users: {
    id: string;
    name: string;
    email: string;
    role: string;
  }[];
}>();

console.log('Department props:', {
  apiCount: props.available_apis?.length ?? 0,
  userCount: props.available_users?.length ?? 0,
  department: props.department
});

// API Assignment Management
const showApiAssignmentDialog = ref(false);
const selectedApi = ref<typeof props.available_apis[0] | null>(null);
const newRateLimit = ref(100);

const availableApis = computed(() => {
  if (!Array.isArray(props.available_apis)) return [];
  return props.available_apis.filter(api => 
    !props.department.api_assignments.some(assignment => assignment.apiId === api.id)
  );
});

const handleApiAssign = async () => {
  if (!selectedApi.value) return;
  
  if (!props.department.is_active) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Cannot modify inactive department',
      life: 3000
    });
    return;
  }

  try {
    const response = await axios.post(route('departments.assign-api', props.department.id), {
      apiId: selectedApi.value.id,
      rateLimit: newRateLimit.value
    });
    
    // Add the new API assignment using response data
    props.department.api_assignments.push(response.data.assignment);
    
    // Remove the API from available APIs
    const index = props.available_apis.findIndex(api => api.id === selectedApi.value?.id);
    if (index !== -1) {
      props.available_apis.splice(index, 1);
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000
    });
    
    showApiAssignmentDialog.value = false;
    selectedApi.value = null;
    newRateLimit.value = 100;
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to assign API',
      life: 3000
    });
  }
};

const handleApiRemove = async (apiId: string) => {
  if (!props.department.is_active) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Cannot modify inactive department',
      life: 3000
    });
    return;
  }

  try {
    const response = await axios.delete(route('departments.remove-api', [props.department.id, apiId]));
    
    // Find and remove the API assignment
    const assignmentIndex = props.department.api_assignments.findIndex(a => a.apiId === apiId);
    if (assignmentIndex !== -1) {
      const removedApi = props.department.api_assignments[assignmentIndex];
      props.department.api_assignments.splice(assignmentIndex, 1);
      
      // Add the API back to available APIs
      props.available_apis.push({
        id: removedApi.api.id,
        name: removedApi.api.name,
        type: removedApi.api.type,
        description: removedApi.api.description
      });
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000
    });
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to remove API',
      life: 3000
    });
  }
};

const handleRateLimitChange = (apiId: string, newValue: number) => {
  if (!props.department.is_active) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Cannot modify inactive department',
      life: 3000
    });
    return;
  }
  editingRateLimits.value[apiId] = newValue;
};

const handleRateLimitUpdate = async (apiId: string) => {
  if (!props.department.is_active) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Cannot modify inactive department',
      life: 3000
    });
    return;
  }

  try {
    savingRateLimit.value[apiId] = true;
    const newLimit = editingRateLimits.value[apiId];
    
    const response = await axios.patch(route('departments.update-rate-limit', [props.department.id, apiId]), {
      rateLimit: newLimit
    });
    
    // Update the local state by finding and updating the specific assignment
    const assignment = props.department.api_assignments.find(a => a.apiId === apiId);
    if (assignment) {
      assignment.rateLimit = newLimit;
    }
    
    // Clear the editing state
    delete editingRateLimits.value[apiId];
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000
    });
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update rate limit',
      life: 3000
    });
  } finally {
    savingRateLimit.value[apiId] = false;
  }
};

// User Assignment Management
const showUserAssignmentDialog = ref(false);
const selectedUser = ref<typeof props.available_users[0] | null>(null);

const availableUsers = computed(() => {
  if (!Array.isArray(props.available_users)) return [];
  return props.available_users.filter(user => 
    !props.department.user_assignments.some(assignment => assignment.userId === user.id)
  );
});

const handleUserAssign = async () => {
  if (!selectedUser.value) return;
  
  if (!props.department.is_active) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Cannot modify inactive department',
      life: 3000
    });
    return;
  }

  try {
    const response = await axios.post(route('departments.assign-user', props.department.id), {
      userId: selectedUser.value.id
    });
    
    props.department.user_assignments.push(response.data.assignment);
    
    const index = props.available_users.findIndex(user => user.id === selectedUser.value?.id);
    if (index !== -1) {
      props.available_users.splice(index, 1);
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000
    });
    
    showUserAssignmentDialog.value = false;
    selectedUser.value = null;
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to assign user',
      life: 3000
    });
  }
};

const handleUserRemove = async (userId: string) => {
  if (!props.department.is_active) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Cannot modify inactive department',
      life: 3000
    });
    return;
  }

  try {
    const response = await axios.delete(route('departments.remove-user', [props.department.id, userId]));
    
    const assignmentIndex = props.department.user_assignments.findIndex(a => a.userId === userId);
    if (assignmentIndex !== -1) {
      const removedUser = props.department.user_assignments[assignmentIndex];
      props.department.user_assignments.splice(assignmentIndex, 1);
      
      props.available_users.push({
        id: removedUser.user.id,
        name: removedUser.user.name,
        email: removedUser.user.email,
        role: 'user'
      });
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000
    });
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to remove user',
      life: 3000
    });
  }
};

const getApiTypeLabel = (type: string) => {
  return type === 'PAID' ? 'Premium' : 'Free';
};

const getSeverity = (type: string) => {
  return type === 'PAID' ? 'warning' : 'success';
};
</script>

<template>
  <Head :title="`${department.name} - Department Details`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Department Details</h2>
        <Tag 
          :value="department.is_active ? 'Active' : 'Inactive'"
          :severity="department.is_active ? 'success' : 'danger'"
        />
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Department Info Card -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-building text-blue-500"></i>
              <span class="text-xl font-semibold">{{ department.name }}</span>
            </div>
          </template>
          
          <template #content>
            <div class="space-y-4">
              <p class="text-gray-600">{{ department.description }}</p>
              
              <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-sm text-gray-500">Created On</div>
                  <div class="font-medium">{{ new Date(department.created_at).toLocaleDateString() }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-sm text-gray-500">Total APIs</div>
                  <div class="font-medium">{{ department.api_assignments.length }}</div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- API Assignments -->
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-link text-indigo-500"></i>
                <span class="text-xl font-semibold">API Assignments</span>
              </div>
              <Button 
                label="Assign API" 
                icon="pi pi-plus"
                @click="showApiAssignmentDialog = true"
                severity="primary"
                :disabled="!department.is_active"
              />
            </div>
          </template>
          
          <template #content>
            <Message v-if="!department.is_active" severity="warn" class="mb-4">
              This department is currently inactive. Editing is disabled.
            </Message>
            <DataTable 
              :value="department.api_assignments"
              stripedRows
              class="mt-2"
              v-model:editingRows="editingRows"
              dataKey="apiId"
            >
              <Column field="api.name" header="API Name">
                <template #body="slotProps">
                  <div class="flex items-center gap-2">
                    <Tag 
                      :value="getApiTypeLabel(slotProps.data.api?.type)" 
                      :severity="getSeverity(slotProps.data.api?.type)" 
                    />
                    {{ slotProps.data.api?.name }}
                  </div>
                </template>
              </Column>
              
              <Column field="rateLimit" header="Rate Limit">
                <template #body="slotProps">
                  <div class="flex items-center gap-2">
                    <InputNumber
                      :model-value="editingRateLimits[slotProps.data.apiId] ?? slotProps.data.rateLimit"
                      :min="1"
                      suffix=" /hour"
                      :showButtons="true"
                      buttonLayout="horizontal"
                      decrementButtonClass="p-button-secondary"
                      incrementButtonClass="p-button-secondary"
                      incrementButtonIcon="pi pi-plus"
                      decrementButtonIcon="pi pi-minus"
                      @update:model-value="(value) => handleRateLimitChange(slotProps.data.apiId, value)"
                      :disabled="!department.is_active"
                    />
                    <Button
                      v-if="editingRateLimits[slotProps.data.apiId] !== undefined"
                      icon="pi pi-check"
                      severity="success"
                      text
                      rounded
                      :loading="savingRateLimit[slotProps.data.apiId]"
                      @click="handleRateLimitUpdate(slotProps.data.apiId)"
                    />
                  </div>
                </template>
              </Column>
              
              <Column style="width: 5rem">
                <template #body="slotProps">
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    @click="handleApiRemove(slotProps.data.apiId)"
                    :disabled="!department.is_active"
                  />
                </template>
              </Column>
            </DataTable>

            <Message v-if="department.api_assignments.length === 0" severity="info" class="mt-4">
              No APIs assigned to this department yet.
            </Message>
          </template>
        </Card>

        <!-- User Assignments -->
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-users text-green-500"></i>
                <span class="text-xl font-semibold">User Assignments</span>
              </div>
              <Button 
                label="Assign User" 
                icon="pi pi-plus"
                @click="showUserAssignmentDialog = true"
                severity="primary"
                :disabled="!department.is_active"
              />
            </div>
          </template>
          
          <template #content>
            <Message v-if="!department.is_active" severity="warn" class="mb-4">
              This department is currently inactive. Editing is disabled.
            </Message>
            <DataTable 
              :value="department.user_assignments"
              stripedRows
              class="mt-2"
              dataKey="userId"
            >
              <Column field="user.name" header="User Name">
                <template #body="slotProps">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-user text-gray-500"></i>
                    {{ slotProps.data.user?.name }}
                  </div>
                </template>
              </Column>
              
              <Column field="user.email" header="Email">
                <template #body="slotProps">
                  {{ slotProps.data.user?.email }}
                </template>
              </Column>
              
              <Column style="width: 5rem">
                <template #body="slotProps">
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    @click="handleUserRemove(slotProps.data.userId)"
                    :disabled="!department.is_active"
                  />
                </template>
              </Column>
            </DataTable>

            <Message v-if="department.user_assignments.length === 0" severity="info" class="mt-4">
              No users assigned to this department yet.
            </Message>
          </template>
        </Card>
      </div>
    </div>

    <!-- API Assignment Dialog -->
    <Dialog
      v-model:visible="showApiAssignmentDialog"
      modal
      :header="'Assign API'"
      :style="{ width: '500px' }"
    >
      <div class="space-y-4">
        <div class="field">
          <label class="block text-gray-700 font-medium mb-2">Select API</label>
          <Dropdown
            v-model="selectedApi"
            :options="availableApis"
            optionLabel="name"
            placeholder="Choose an API"
            class="w-full"
          >
            <template #option="{ option }">
              <div class="flex items-center gap-2">
                <Tag :value="getApiTypeLabel(option.type)" :severity="getSeverity(option.type)" />
                {{ option.name }}
              </div>
            </template>
          </Dropdown>
        </div>

        <div class="field">
          <label class="block text-gray-700 font-medium mb-2">Rate Limit</label>
          <InputNumber
            v-model="newRateLimit"
            :min="1"
            suffix=" /hour"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            @click="showApiAssignmentDialog = false"
            text
          />
          <Button
            label="Assign"
            icon="pi pi-check"
            @click="handleApiAssign"
            :disabled="!selectedApi"
          />
        </div>
      </template>
    </Dialog>

    <!-- User Assignment Dialog -->
    <Dialog
      v-model:visible="showUserAssignmentDialog"
      modal
      :header="'Assign User'"
      :style="{ width: '500px' }"
    >
      <div class="space-y-4">
        <div class="field">
          <label class="block text-gray-700 font-medium mb-2">Select User</label>
          <Dropdown
            v-model="selectedUser"
            :options="availableUsers"
            optionLabel="name"
            placeholder="Choose a user"
            class="w-full"
          >
            <template #option="{ option }">
              <div class="flex flex-col">
                <span>{{ option.name }}</span>
                <span class="text-sm text-gray-500">{{ option.email }}</span>
              </div>
            </template>
          </Dropdown>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            @click="showUserAssignmentDialog = false"
            text
          />
          <Button
            label="Assign"
            icon="pi pi-check"
            @click="handleUserAssign"
            :disabled="!selectedUser"
          />
        </div>
      </template>
    </Dialog>
  </AuthenticatedLayout>
</template>
<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import EndpointForm from "@/Components/Form/EndpointForm.vue";
import { CreateApiSchema } from "@/types/api";
import { useToast } from "primevue/usetoast";
import Accordion from "primevue/accordion";
import Textarea from "primevue/textarea";
import EndpointEditor from "@/Components/Form/EndpointEditor.vue";
import EndpointSummary from "@/Components/Form/EndpointSummary.vue";
import Dialog from "primevue/dialog";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import type { Endpoint, Api } from "@/types/api";
import axios from 'axios';
import { v4 as uuidv4 } from 'uuid';

const props = defineProps<{
  api: Api;
}>();

// Initialize form with API data
const form = ref({
  name: props.api.name,
  description: props.api.description || "",
  type: props.api.type,
  baseUrl: props.api.baseUrl,
  rateLimit: props.api.rateLimit,
  endpoints: props.api.endpoints || [],
});

const toast = useToast();
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const showEndpointEditor = ref(false);
const currentEndpoint = ref<Endpoint>({
  id: "",
  name: "",
  method: "GET",
  path: "",
  description: "",
  parameters: [],
});
const editingEndpointId = ref<string | null>(null);

const showDeleteModal = ref(false);
const selectedEndpointForDelete = ref<Endpoint | null>(null);
const deleteLoading = ref(false);

// Add these new refs to track changes
const updatedParameters = ref(new Set<string>());
const deletedParameters = ref(new Set<string>());
const editLoading = ref(false);

// Add new refs for API changes
const newEndpoints = ref<Endpoint[]>([]);
const showSaveConfirmation = ref(false);
const saveLoading = ref(false);

const handleAddEndpoint = () => {
  currentEndpoint.value = {
    id: "",
    name: "",
    method: "GET",
    path: "",
    description: "",
    parameters: [],
  };
  showEndpointEditor.value = true;
};

// Modify handleEditEndpoint to clone the endpoint
const handleEditEndpoint = (endpoint: Endpoint) => {
  // Deep clone the endpoint to track changes
  currentEndpoint.value = JSON.parse(JSON.stringify(endpoint));
  editingEndpointId.value = endpoint.id || null;
  showEndpointEditor.value = true;
  
  // Reset change tracking sets
  updatedParameters.value.clear();
  deletedParameters.value.clear();
};

const confirmDelete = (endpointId: string) => {
  const endpoint = form.value.endpoints.find(e => e.id === endpointId);
  selectedEndpointForDelete.value = endpoint || null;
  showDeleteModal.value = true;
};

const handleDeleteEndpoint = async (endpointId: string) => {
  try {
    deleteLoading.value = true;
    
    // Make API call to delete endpoint
    await axios.delete(route('api.endpoints.delete', {
      api: props.api.id,
      endpoint: endpointId
    }));
    
    // Update local state
    form.value.endpoints = form.value.endpoints.filter(
      (e: Endpoint) => e.id !== endpointId
    );
    
    // Show success message
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Endpoint deleted successfully',
      life: 3000
    });

    showDeleteModal.value = false;
  } catch (error) {
    console.error('Error deleting endpoint:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete endpoint',
      life: 3000
    });
  } finally {
    deleteLoading.value = false;
    selectedEndpointForDelete.value = null;
  }
};

// Add parameter tracking methods
const trackParameterUpdate = (parameterId: string) => {
  updatedParameters.value.add(parameterId);
};

const trackParameterDelete = (parameterId: string) => {
  if (parameterId) {
    deletedParameters.value.add(parameterId);
  }
};

// Modify handleSaveEndpoint to include parameter tracking
const handleSaveEndpoint = async () => {
  try {
    editLoading.value = true;

    if (editingEndpointId.value) {
      // Check if we're editing an unsaved endpoint
      const unsavedEndpoint = newEndpoints.value.find(e => e.id === editingEndpointId.value);
      
      if (unsavedEndpoint) {
        // Update the unsaved endpoint in newEndpoints array
        const index = newEndpoints.value.findIndex(e => e.id === editingEndpointId.value);
        if (index !== -1) {
          newEndpoints.value[index] = {
            ...currentEndpoint.value,
            isNew: true // Maintain the isNew flag
          };
        }

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Endpoint updated (not saved to server)',
          life: 3000
        });
      } else {
        // Update existing endpoint on server
        const response = await axios.patch(
          route('api.endpoints.update', {
            api: props.api.id,
            endpoint: editingEndpointId.value
          }), {
            endpoint: currentEndpoint.value,
            updatedParameters: Array.from(updatedParameters.value),
            deletedParameters: Array.from(deletedParameters.value)
          }
        );

        // Update local state with the response data
        const index = form.value.endpoints.findIndex(
          (e: Endpoint) => e.id === editingEndpointId.value
        );
        if (index !== -1) {
          form.value.endpoints[index] = response.data.endpoint;
        }

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Endpoint updated successfully',
          life: 3000
        });
      }
    } else {
      // Add new endpoint to newEndpoints array
      newEndpoints.value.push({
        ...currentEndpoint.value,
        id: uuidv4(),
        isNew: true
      });

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'New endpoint added (not saved to server)',
        life: 3000
      });
    }

    showEndpointEditor.value = false;
    editingEndpointId.value = null;
    
  } catch (error) {
    console.error('Error saving endpoint:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save endpoint',
      life: 3000
    });
  } finally {
    editLoading.value = false;
  }
};

const handleCancelEndpoint = () => {
  showEndpointEditor.value = false;
  editingEndpointId.value = null;
};

// Add methods for endpoint table
const getMethodSeverity = (method: string) => {
  const severities = {
    GET: 'success',
    POST: 'info',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'warning'
  };
  return severities[method as keyof typeof severities] || 'secondary';
};

const formatPath = (endpoint: Endpoint) => {
  return `${form.value.baseUrl}${endpoint.path}`;
};

// Modify sortedEndpoints computed to include new endpoints
const sortedEndpoints = computed(() => {
  // Combine existing and new endpoints
  const allEndpoints = [
    ...form.value.endpoints,
    ...newEndpoints.value.map(endpoint => ({
      ...endpoint,
      isNew: true // Add flag to identify new endpoints
    }))
  ];
  
  return allEndpoints.sort((a, b) => {
    // Sort by method first
    if (a.method !== b.method) {
      return a.method.localeCompare(b.method);
    }
    // Then by path
    return a.path.localeCompare(b.path);
  });
});

// Add save confirmation handler
const handleSaveClick = () => {
    showSaveConfirmation.value = true;
};

// Add save API method
const handleSaveApi = async () => {
    try {
        saveLoading.value = true;
        
        const response = await axios.patch(
            route('api.update', props.api.id),
            {
                ...form.value,
                newEndpoints: newEndpoints.value
            }
        );

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'API updated successfully',
            life: 3000
        });

        // Redirect to API show page
        router.visit(route('api.show', props.api.id));
        
    } catch (error) {
        console.error('Error updating API:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update API',
            life: 3000
        });
    } finally {
        saveLoading.value = false;
        showSaveConfirmation.value = false;
    }
};
</script>

<template>
  <Head title="Edit API" />

  <AuthenticatedLayout>
    <Toast />

    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-5">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              text
              rounded
              @click="router.visit(route('api.show', api.id))"
              class="p-0"
              severity="contrast"
            />
            <h2 class="text-2xl font-semibold text-gray-800">Edit API</h2>
          </div>
        </div>

        <form @submit.prevent="handleSaveClick" class="space-y-6">
          <!-- Basic Info Card -->
          <div class="bg-white p-6 rounded-xl shadow-sm space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">API Name</label>
                <InputText
                  v-model="form.name"
                  :class="{ 'p-invalid': errors['name'] }"
                  class="w-full"
                />
                <small class="text-red-500">{{ errors["name"] }}</small>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Type</label>
                <SelectButton
                  v-model="form.type"
                  :options="['FREE', 'PAID']"
                  class="w-full"
                />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700">Base URL</label>
              <InputText
                v-model="form.baseUrl"
                :class="{ 'p-invalid': errors['baseUrl'] }"
                class="w-full"
                placeholder="https://api.example.com"
              />
              <small class="text-red-500">{{ errors["baseUrl"] }}</small>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700">Description</label>
              <Textarea
                v-model="form.description"
                rows="5"
                class="w-full"
                placeholder="Provide a detailed description of your API..."
                :class="{ 'p-invalid': errors['description'] }"
              />
              <small class="text-red-500">{{ errors["description"] }}</small>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700"
                >Rate Limit (requests per Hour)</label
              >
              <InputNumber
                v-model="form.rateLimit"
                :min="1"
                :max="1000"
                class="w-full"
              />
            </div>
          </div>

          <!-- Endpoints Card -->
          <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Endpoints</h3>
              <Button
                label="Add Endpoint"
                icon="pi pi-plus"
                @click="handleAddEndpoint"
                severity="secondary"
              />
            </div>

            <!-- Add DataTable for endpoints -->
            <DataTable 
              :value="sortedEndpoints"
              class="mb-4"
              :rows="10"
              stripedRows
              showGridlines
              v-if="sortedEndpoints.length > 0"
            >
              <Column field="method" header="Method" style="width: 100px">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <Tag
                      :value="data.method"
                      :severity="getMethodSeverity(data.method)"
                    />
                    <Tag
                      v-if="data.isNew"
                      value="Not Saved"
                      severity="warning"
                      class="text-xs"
                    />
                  </div>
                </template>
              </Column>
              
              <Column field="name" header="Name" />
              
              <Column field="path" header="Path">
                <template #body="{ data }">
                  <div class="text-sm font-mono break-all">
                    {{ formatPath(data) }}
                  </div>
                </template>
              </Column>

              <Column field="description" header="Description">
                <template #body="{ data }">
                  <div class="truncate max-w-xs" :title="data.description">
                    {{ data.description || 'No description' }}
                  </div>
                </template>
              </Column>

              <Column header="Actions" style="width: 150px">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button
                      icon="pi pi-pencil"
                      severity="secondary"
                      text
                      rounded
                      @click="handleEditEndpoint(data)"
                      v-tooltip.top="'Edit endpoint'"
                    />
                    <Button
                      icon="pi pi-trash"
                      severity="danger"
                      text
                      rounded
                      @click.stop="confirmDelete(data.id)"
                      v-tooltip.top="'Delete endpoint'"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>

            <div v-else class="text-center py-8 text-gray-500">
              No endpoints added yet. Click "Add Endpoint" to create one.
            </div>

            <!-- Keep existing Dialog and other components -->
            <Dialog
              v-model:visible="showEndpointEditor"
              modal
              :style="{ width: '80vw' }"
              :header="editingEndpointId ? 'Edit Endpoint' : 'Add Endpoint'"
            >
              <EndpointEditor
                v-model="currentEndpoint"
                @save="handleSaveEndpoint"
                @cancel="handleCancelEndpoint"
                @parameter-updated="trackParameterUpdate"
                @parameter-deleted="trackParameterDelete"
                :loading="editLoading"
                :isEditing="!!editingEndpointId"
              />
            </Dialog>

            <!-- Add Delete Confirmation Modal -->
            <Dialog
              v-model:visible="showDeleteModal"
              modal
              header="Delete Endpoint"
              :style="{ width: '450px' }"
              :closable="!deleteLoading"
            >
              <p class="my-4">
                Are you sure you want to delete this endpoint? This action cannot be undone.
              </p>
              <p class="text-sm text-gray-500 mt-2" v-if="selectedEndpointForDelete">
                <strong>Method:</strong> {{ selectedEndpointForDelete.method }}
                <br>
                <strong>Path:</strong> {{ selectedEndpointForDelete.path }}
              </p>
              <template #footer>
                <Button
                  label="Cancel"
                  icon="pi pi-times"
                  text
                  @click="showDeleteModal = false"
                  :disabled="deleteLoading"
                />
                <Button
                  label="Delete"
                  icon="pi pi-trash"
                  severity="danger"
                  @click="handleDeleteEndpoint(selectedEndpointForDelete?.id ?? '')"
                  :loading="deleteLoading"
                />
              </template>
            </Dialog>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end gap-3">
            <Button
              label="Cancel"
              severity="secondary"
              text
              type="button"
              @click="router.visit(route('api.show', api.id))"
            />
            <Button
              label="Save Changes"
              type="submit"
              severity="primary"
              :loading="saveLoading"
            />
          </div>
        </form>

        <!-- Add Save Confirmation Modal -->
        <Dialog
            v-model:visible="showSaveConfirmation"
            modal
            header="Save Changes"
            :style="{ width: '450px' }"
            :closable="!saveLoading"
        >
            <div class="space-y-4">
                <p class="text-gray-600">
                    Are you sure you want to save these changes to the API?
                </p>
                
                <!-- Show summary of changes -->
                <div class="bg-gray-50 p-4 rounded text-sm space-y-2">
                    <p><strong>Name:</strong> {{ form.name }}</p>
                    <p><strong>Type:</strong> {{ form.type }}</p>
                    <p><strong>Rate Limit:</strong> {{ form.rateLimit }} requests/hour</p>
                    <p><strong>New Endpoints:</strong> {{ newEndpoints.length }}</p>
                    <p><strong>Total Endpoints:</strong> {{ form.endpoints.length + newEndpoints.length }}</p>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="showSaveConfirmation = false"
                    :disabled="saveLoading"
                />
                <Button
                    label="Save Changes"
                    icon="pi pi-check"
                    severity="primary"
                    @click="handleSaveApi"
                    :loading="saveLoading"
                />
            </template>
        </Dialog>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Reuse the same styles from Create.vue */
.p-button {
  height: 40px;
}

.p-tag {
  white-space: nowrap;
}

/* Add these new styles */
:deep(.p-datatable) {
  border-radius: 0.5rem;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #64748b;
  font-weight: 600;
  font-size: 0.875rem;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f1f5f9;
}

:deep(.p-tag) {
  min-width: 65px;
  justify-content: center;
}

/* Add these styles for the delete confirmation dialog */
:deep(.p-dialog-header) {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.p-dialog-content) {
  padding: 1.5rem;
}

:deep(.p-dialog-footer) {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

:deep(.p-dialog .p-button) {
  min-width: 100px;
}

/* Add style for the "Not Saved" tag */
:deep(.p-tag.text-xs) {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}
</style>

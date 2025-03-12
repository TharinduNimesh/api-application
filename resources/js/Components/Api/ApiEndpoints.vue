<script setup>
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import EndpointDrawer from './EndpointDrawer.vue';
import { ref } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  endpoints: {
    type: Array,
    required: true
  },
  apiId: {
    type: String,
    required: true
  }
});

const page = usePage();
const expandedRows = ref({});
const drawerVisible = ref(false);
const selectedEndpoint = ref(null);

// Convert endpoints to ref for reactivity
const localEndpoints = ref([...props.endpoints]);

const getMethodSeverity = (method) => {
  const severities = {
    GET: 'success',
    POST: 'info',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'warning'
  };
  return severities[method] || 'info';
};

// Return custom classes for method tags to make them more vibrant
const getMethodClass = (method) => {
  const classes = {
    GET: 'bg-emerald-100 text-emerald-800 border-emerald-200 font-medium',
    POST: 'bg-blue-100 text-blue-800 border-blue-200 font-medium',
    PUT: 'bg-amber-100 text-amber-800 border-amber-200 font-medium',
    DELETE: 'bg-red-100 text-red-800 border-red-200 font-medium',
    PATCH: 'bg-violet-100 text-violet-800 border-violet-200 font-medium'
  };
  return classes[method] || '';
};

const openDrawer = (endpoint) => {
  selectedEndpoint.value = endpoint;
  drawerVisible.value = true;
};

// Delete endpoint functionality
const showDeleteModal = ref(false);
const selectedEndpointForDelete = ref(null);
const deleteLoading = ref(false);

const confirmDelete = (endpoint) => {
  selectedEndpointForDelete.value = endpoint;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  try {
    deleteLoading.value = true;
    await axios.delete(route('api.endpoints.delete', {
      api: props.apiId,
      endpoint: selectedEndpointForDelete.value.id
    }));
    
    // Update local state by removing the deleted endpoint
    localEndpoints.value = localEndpoints.value.filter(
      endpoint => endpoint.id !== selectedEndpointForDelete.value.id
    );
    
    showDeleteModal.value = false;
  } catch (error) {
    console.error('Error deleting endpoint:', error);
  } finally {
    deleteLoading.value = false;
  }
};
</script>

<template>
  <Card class="shadow-lg hover:shadow-xl transition-shadow duration-200">
    <template #title>
      <div class="flex items-center gap-2 mb-4">
        <i class="pi pi-list text-blue-500"></i>
        <span class="text-xl font-semibold">API Endpoints</span>
      </div>
    </template>
    <template #content>
      <DataTable
        :value="localEndpoints"
        :expandedRows="expandedRows"
        v-model:expandedRows="expandedRows"
        dataKey="id"
        tableStyle="min-width: 50rem"
        class="p-datatable-hoverable-rows"
        stripedRows
        showGridlines
      >
        <template #empty>
          <div class="text-center py-8">
            <i class="pi pi-inbox text-4xl text-gray-400"></i>
            <p class="mt-2 text-gray-500">No endpoints available</p>
          </div>
        </template>

        <template #loading>
          <div class="text-center py-8">
            <ProgressSpinner />
          </div>
        </template>

        <Column expander style="width: 2rem">
          <template #expander="{ expanded }">
            <Button
              :icon="expanded ? 'pi pi-chevron-down' : 'pi pi-chevron-right'"
              text
              rounded
              class="p-1"
              :class="expanded ? 'text-blue-600' : 'text-gray-500'"
            />
          </template>
        </Column>

        <Column field="method" header="Method" style="width: 8rem">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <Tag 
                :value="data.method" 
                :severity="getMethodSeverity(data.method)"
                :class="getMethodClass(data.method)"
              />
              <Tag
                v-if="data.isNew"
                value="New"
                severity="info"
                class="text-xs bg-blue-50 text-blue-700 border border-blue-200"
              />
            </div>
          </template>
        </Column>

        <Column field="name" header="Name">
          <template #body="{ data }">
            <div class="font-medium">{{ data.name }}</div>
          </template>
        </Column>

        <Column field="path" header="Path">
          <template #body="{ data }">
            <code class="px-3 py-1.5 bg-gray-50 rounded-md text-sm border border-gray-100">{{ data.path }}</code>
          </template>
        </Column>

        <Column style="width: 6rem">
          <template #body="{ data }">
            <div class="flex gap-1 justify-center">
              <Button
                icon="pi pi-code"
                text
                rounded
                @click="openDrawer(data)"
                class="p-1 text-indigo-500 hover:bg-indigo-50"
                v-tooltip.top="'Test Endpoint'"
              />
              
              <!-- Admin-only actions -->
              <template v-if="$page.props.auth.user.role === 'admin'">
                <Button
                  icon="pi pi-trash"
                  text
                  rounded
                  severity="danger"
                  class="p-1"
                  v-tooltip.top="'Delete Endpoint'"
                  @click.stop="confirmDelete(data)"
                />
              </template>
            </div>
          </template>
        </Column>

        <template #expansion="slotProps">
          <div class="p-6 space-y-4 bg-gray-50/70">
            <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
              <h4 class="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                <i class="pi pi-file-edit text-indigo-500"></i>
                Description
              </h4>
              <p class="text-gray-900">{{ slotProps.data.description }}</p>
            </div>

            <div v-if="slotProps.data.parameters?.length" class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
              <h4 class="text-sm font-medium text-gray-500 flex items-center gap-2 mb-3">
                <i class="pi pi-list text-emerald-500"></i>
                Parameters
              </h4>
              <DataTable 
                :value="slotProps.data.parameters" 
                class="text-sm"
                stripedRows
              >
                <Column field="name" header="Name">
                  <template #body="{ data }">
                    <div class="font-medium">{{ data.name }}</div>
                  </template>
                </Column>
                <Column field="type" header="Type">
                  <template #body="{ data }">
                    <code class="px-2 py-1 bg-gray-50 rounded-md text-xs border border-gray-100">{{ data.type }}</code>
                  </template>
                </Column>
                <Column field="required" header="Required">
                  <template #body="{ data }">
                    <Tag 
                      :severity="data.required ? 'danger' : 'info'" 
                      :value="data.required ? 'Required' : 'Optional'"
                      :class="data.required ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                      class="text-xs"
                    />
                  </template>
                </Column>
                <Column field="description" header="Description" />
              </DataTable>
            </div>
          </div>
        </template>
      </DataTable>
    </template>
  </Card>

  <EndpointDrawer
    v-if="selectedEndpoint"
    v-model:visible="drawerVisible"
    :endpoint="selectedEndpoint"
    :api="page.props.api"
    :isDraft="false"
  />

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
    <p class="text-sm text-gray-500 mt-2">
      <strong>Method:</strong> {{ selectedEndpointForDelete?.method }}
      <br>
      <strong>Path:</strong> {{ selectedEndpointForDelete?.path }}
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
        @click="handleDelete"
        :loading="deleteLoading"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.p-datatable.p-datatable-hoverable-rows .p-datatable-tbody > tr:not(.p-highlight):hover {
  background-color: #f9fafb;
}

.p-datatable .p-datatable-tbody > tr.p-highlight {
  background-color: #f3f4f6;
}

/* Add styles for action buttons */
:deep(.p-button.p-button-text) {
  padding: 0.5rem;
  width: 2rem;
  height: 2rem;
}

:deep(.p-button.p-button-text:hover) {
  background: rgba(0, 0, 0, 0.05);
}

:deep(.p-button.p-button-text.p-button-danger:hover) {
  background: rgb(254 242 242);
}

/* Card styles */
:deep(.p-card) {
  border-radius: 0.75rem;
  overflow: hidden;
}

/* Dialog styles */
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
</style>

<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, ref } from "vue";
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import { useToast } from "primevue/usetoast";
import { DataTableExpandedRows } from 'primevue/datatable';

// Define proper interfaces for the API types
interface Parameter {
  name: string;
  type: string;
  required: boolean;
  description: string;
}

interface Endpoint {
  id: string;
  method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
  name: string;
  path: string;
  description: string;
  parameters?: Parameter[];
}

interface ExtendedApi {
  id: string;
  name: string;
  description: string;
  type: 'FREE' | 'PAID';
  status: 'ACTIVE' | 'INACTIVE';
  baseUrl: string;
  rateLimit: number;
  createdAt: string;
  endpoints: Endpoint[];
  createdBy?: {
    name: string;
    email: string;
  };
}

// Update props definition with the new interface
const props = defineProps<{
  api: ExtendedApi;
}>();

// Add computed properties for safe access
const creatorName = computed(() => props.api.createdBy?.name ?? 'Unknown User');
const apiStatus = computed(() => props.api.status ?? 'INACTIVE');
const apiType = computed(() => props.api.type ?? 'FREE');

const toast = useToast();

const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text);
    toast.add({
      severity: 'success',
      summary: 'Copied',
      detail: 'Text copied to clipboard',
      life: 3000
    });
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to copy text',
      life: 3000
    });
  }
};

const getMethodSeverity = (method: string) => {
  const severities: Record<string, string> = {
    GET: 'success',
    POST: 'info',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'warning'
  };
  return severities[method] || 'info';
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Update expandedRows type definition
const expandedRows = ref<DataTableExpandedRows>({});

// Add handler for expandedRows update
const onExpandedRowsChange = (newExpandedRows: any[] | DataTableExpandedRows) => {
  expandedRows.value = newExpandedRows as DataTableExpandedRows;
};
</script>

<template>
  <Head :title="api.name" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              text
              rounded
              @click="router.visit(route('dashboard'))"
              class="p-0"
              severity="contrast"
            />
            <div>
              <h1 class="text-2xl font-semibold text-gray-800">{{ api.name }}</h1>
              <p class="text-sm text-gray-500">Created by {{ creatorName }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <Tag :severity="apiType === 'FREE' ? 'success' : 'warning'" :value="apiType" />
            <Tag :severity="apiStatus === 'ACTIVE' ? 'success' : 'danger'" :value="apiStatus" />
          </div>
        </div>

        <TabView>
          <!-- Overview Panel -->
          <TabPanel header="Overview" value="overview">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <!-- API Details Card -->
              <div class="lg:col-span-2">
                <Card>
                  <template #title>
                    API Details
                  </template>
                  <template #content>
                    <div class="space-y-6">
                      <div>
                        <h3 class="text-sm font-medium text-gray-500">Description</h3>
                        <p class="mt-1 text-gray-900">{{ api.description }}</p>
                      </div>
                      
                      <div>
                        <h3 class="text-sm font-medium text-gray-500">Base URL</h3>
                        <div class="mt-1 flex items-center gap-2">
                          <code class="px-2 py-1 bg-gray-100 rounded text-sm">{{ api.baseUrl }}</code>
                          <Button
                            icon="pi pi-copy"
                            text
                            rounded
                            @click="copyToClipboard(api.baseUrl)"
                            class="p-1"
                          />
                        </div>
                      </div>

                      <div class="grid grid-cols-2 gap-6">
                        <div>
                          <h3 class="text-sm font-medium text-gray-500">Rate Limit</h3>
                          <p class="mt-1 text-gray-900">{{ api.rateLimit }} requests/minute</p>
                        </div>
                        <div>
                          <h3 class="text-sm font-medium text-gray-500">Created On</h3>
                          <p class="mt-1 text-gray-900">{{ formatDate(api.createdAt) }}</p>
                        </div>
                      </div>
                    </div>
                  </template>
                </Card>
              </div>

              <!-- Stats Card -->
              <div class="lg:col-span-1">
                <Card>
                  <template #title>
                    Usage Statistics
                  </template>
                  <template #content>
                    <div class="space-y-4">
                      <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500">Total Requests</h4>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">12,345</p>
                      </div>
                      <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500">Success Rate</h4>
                        <p class="mt-1 text-2xl font-semibold text-green-600">98.5%</p>
                      </div>
                      <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500">Average Response Time</h4>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">245ms</p>
                      </div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </TabPanel>

          <!-- Endpoints Panel -->
          <TabPanel header="Endpoints" value="endpoints">
            <Card>
              <template #content>
                <DataTable
                  :value="api.endpoints"
                  :expandedRows="expandedRows"
                  @update:expandedRows="onExpandedRowsChange"
                  dataKey="id"
                  tableStyle="min-width: 50rem"
                >
                  <Column expander style="width: 2rem" />
                  <Column field="method" header="Method" style="width: 7rem">
                    <template #body="{ data }">
                      <Tag :value="data.method" :severity="getMethodSeverity(data.method)" />
                    </template>
                  </Column>
                  <Column field="name" header="Name" />
                  <Column field="path" header="Path">
                    <template #body="{ data }">
                      <code class="px-2 py-1 bg-gray-100 rounded text-sm">{{ data.path }}</code>
                    </template>
                  </Column>
                  <template #expansion="slotProps">
                    <div class="p-4 space-y-4">
                      <div>
                        <h4 class="text-sm font-medium text-gray-500">Description</h4>
                        <p class="mt-1 text-gray-900">{{ slotProps.data.description }}</p>
                      </div>

                      <div v-if="slotProps.data.parameters?.length">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Parameters</h4>
                        <DataTable :value="slotProps.data.parameters" class="text-sm">
                          <Column field="name" header="Name" />
                          <Column field="type" header="Type" />
                          <Column field="required" header="Required">
                            <template #body="{ data }">
                              <Tag :severity="data.required ? 'danger' : 'info'" :value="data.required ? 'Required' : 'Optional'" />
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
          </TabPanel>

          <!-- Documentation Panel -->
          <TabPanel header="Documentation" value="documentation">
            <Card>
              <template #content>
                <div class="prose max-w-none">
                  <!-- Add documentation content here -->
                  <h2>Getting Started</h2>
                  <p>Follow these steps to start using the API:</p>
                  
                  <h3>Authentication</h3>
                  <p>Add your API key to the request headers:</p>
                  <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                  
                  <h3>Rate Limiting</h3>
                  <p>This API has a rate limit of {{ api.rateLimit }} requests per minute.</p>
                </div>
              </template>
            </Card>
          </TabPanel>
        </TabView>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

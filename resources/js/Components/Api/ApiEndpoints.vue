<script setup>
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import EndpointDrawer from './EndpointDrawer.vue';
import { ref } from 'vue';

const props = defineProps({
  endpoints: {
    type: Array,
    required: true
  }
});

const expandedRows = ref({});
const drawerVisible = ref(false);
const selectedEndpoint = ref(null);

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

const openDrawer = (endpoint) => {
  selectedEndpoint.value = endpoint;
  drawerVisible.value = true;
};
</script>

<template>
  <Card class="shadow-lg">
    <template #title>
      <div class="flex items-center gap-2 mb-4">
        <i class="pi pi-list text-blue-500"></i>
        <span class="text-xl font-semibold">API Endpoints</span>
      </div>
    </template>
    <template #content>
      <DataTable
        :value="endpoints"
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
            />
          </template>
        </Column>

        <Column field="method" header="Method" style="width: 7rem">
          <template #body="{ data }">
            <Tag :value="data.method" :severity="getMethodSeverity(data.method)" />
          </template>
        </Column>

        <Column field="name" header="Name">
          <template #body="{ data }">
            <div class="font-medium">{{ data.name }}</div>
          </template>
        </Column>

        <Column field="path" header="Path">
          <template #body="{ data }">
            <code class="px-2 py-1 bg-gray-100 rounded text-sm">{{ data.path }}</code>
          </template>
        </Column>

        <Column style="width: 4rem">
          <template #body="{ data }">
            <Button
              icon="pi pi-code"
              text
              rounded
              @click="openDrawer(data)"
              class="p-1"
              v-tooltip.top="'Test Endpoint'"
            />
          </template>
        </Column>

        <template #expansion="slotProps">
          <div class="p-6 space-y-4 bg-gray-50">
            <div class="bg-white p-4 rounded-lg shadow-sm">
              <h4 class="text-sm font-medium text-gray-500">Description</h4>
              <p class="mt-2 text-gray-900">{{ slotProps.data.description }}</p>
            </div>

            <div v-if="slotProps.data.parameters?.length" class="bg-white p-4 rounded-lg shadow-sm">
              <h4 class="text-sm font-medium text-gray-500 mb-4">Parameters</h4>
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
                    <code class="px-2 py-1 bg-gray-100 rounded text-xs">{{ data.type }}</code>
                  </template>
                </Column>
                <Column field="required" header="Required">
                  <template #body="{ data }">
                    <Tag 
                      :severity="data.required ? 'danger' : 'info'" 
                      :value="data.required ? 'Required' : 'Optional'"
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
  />
</template>

<style scoped>
.p-datatable.p-datatable-hoverable-rows .p-datatable-tbody > tr:not(.p-highlight):hover {
  background-color: #f9fafb;
}

.p-datatable .p-datatable-tbody > tr.p-highlight {
  background-color: #f3f4f6;
}
</style>

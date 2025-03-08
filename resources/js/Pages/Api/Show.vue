<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from 'vue';
// Replace TabView imports with Tabs components

import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import type { Api } from '@/types/api';
import ApiHeader from '@/Components/Api/ApiHeader.vue';
import ApiDetails from '@/Components/Api/ApiDetails.vue';
import ApiStats from '@/Components/Api/ApiStats.vue';
import ApiEndpoints from '@/Components/Api/ApiEndpoints.vue';

const props = defineProps<{
  api: Api;
}>();

const handleDelete = () => {
  router.delete(route('api.destroy', props.api.id));
};

const handleBack = () => {
  router.visit(route('dashboard'));
};
</script>

<template>
  <Head :title="api.name" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <ApiHeader 
          :api="api" 
          :onDelete="handleDelete"
          @back="handleBack"
        />

        <Tabs value="0">
          <TabList class="border-none bg-white rounded-t-lg shadow-sm">
            <Tab value="0">Overview</Tab>
            <Tab value="1">Endpoints</Tab>
          </TabList>
          <TabPanels>
            <TabPanel value="0">
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                  <ApiDetails :api="api" />
                </div>
                <div class="lg:col-span-1">
                  <ApiStats :apiId="api.id" />
                </div>
              </div>
            </TabPanel>
            <TabPanel value="1">
              <ApiEndpoints 
                :endpoints="api.endpoints" 
                :apiId="api.id"
              />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
:deep(.p-tabview-nav) {
  border: none !important;
  background-color: transparent !important;
}

:deep(.p-tabview-nav-link) {
  border: none !important;
  border-radius: 0.5rem !important;
  margin: 0 0.25rem !important;
  padding: 1rem 1.5rem !important;
}

:deep(.p-tabview-selected) .p-tabview-nav-link {
  background-color: #f3f4f6 !important;
  color: #4f46e5 !important;
}

:deep(.p-tabpanel) {
  padding: 1.5rem 0 !important;
}
</style>

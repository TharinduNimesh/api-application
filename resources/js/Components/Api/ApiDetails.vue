<script setup lang="ts">
import Card from 'primevue/card';
import type { Api } from '@/types/api';

const props = defineProps<{
  api: Api;
}>();

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <Card class="shadow-lg hover:shadow-xl transition-shadow duration-200">
    <template #title>
      <div class="flex items-center gap-2 mb-4">
        <i class="pi pi-info-circle text-blue-500"></i>
        <span class="text-xl font-semibold">API Details</span>
      </div>
    </template>
    <template #content>
      <div class="space-y-6">
        <div class="bg-gray-50/80 p-5 rounded-lg border border-gray-100 hover:shadow-sm transition-shadow">
          <h3 class="text-sm font-medium text-gray-500 flex items-center gap-2">
            <i class="pi pi-file-edit text-indigo-500"></i>
            Description
          </h3>
          <p class="mt-2 text-gray-900">{{ api.description }}</p>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <div class="bg-gray-50/80 p-5 rounded-lg border border-gray-100 hover:shadow-sm transition-shadow">
            <h3 class="text-sm font-medium text-gray-500 flex items-center gap-2">
              <i class="pi pi-stop-circle text-amber-500"></i>
              Rate Limit
            </h3>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-bold text-indigo-600">{{ api.rateLimit }}</span>
              <span class="text-gray-500">requests/hour</span>
            </div>
          </div>
          <div class="bg-gray-50/80 p-5 rounded-lg border border-gray-100 hover:shadow-sm transition-shadow">
            <h3 class="text-sm font-medium text-gray-500 flex items-center gap-2">
              <i class="pi pi-calendar text-emerald-500"></i>
              Created On
            </h3>
            <div class="text-2xl font-bold text-gray-900">
              {{ formatDate(api.createdAt) }}
            </div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.p-card {
  border-radius: 0.75rem;
  overflow: hidden;
}
</style>

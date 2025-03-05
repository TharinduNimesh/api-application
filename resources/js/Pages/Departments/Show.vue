<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps<{
  department: {
    id: string;
    name: string;
    // Add more department properties as needed
  }
}>();
</script>

<template>
  <Head title="Department Details" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Department Details</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            Single department view content will go here
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
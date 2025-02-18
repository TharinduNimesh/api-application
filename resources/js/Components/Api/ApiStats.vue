<script setup lang="ts">
import Card from 'primevue/card';
import Button from 'primevue/button';

// Remove chart-related imports and data
</script>

<template>
  <div class="space-y-4">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-4">
      <!-- Success Rate Card -->
      <div class="relative bg-gradient-to-br from-blue-500/90 to-blue-600/90 p-6 rounded-xl text-white overflow-hidden">
        <div class="absolute top-0 left-0 w-3 h-3 bg-blue-400 rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-700 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2">
          <i class="pi pi-check-circle text-2xl text-blue-200"></i>
          <h4 class="text-sm font-medium text-blue-100">Success Rate</h4>
        </div>
        <p class="mt-2 text-3xl font-bold flex items-baseline">
          98.5%
          <span class="text-sm ml-2 text-blue-200">↑ 2.1%</span>
        </p>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-400/20 rounded-full blur-xl"></div>
      </div>

      <!-- Average Response Card -->
      <div
        class="relative bg-gradient-to-br from-emerald-500/90 to-emerald-600/90 p-6 rounded-xl text-white overflow-hidden">
        <div class="absolute top-0 left-0 w-3 h-3 bg-emerald-400 rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-700 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2">
          <i class="pi pi-clock text-2xl text-emerald-200"></i>
          <h4 class="text-sm font-medium text-emerald-100">Avg Response</h4>
        </div>
        <p class="mt-2 text-3xl font-bold flex items-baseline">
          245ms
          <span class="text-sm ml-2 text-emerald-200">↓ 12ms</span>
        </p>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-400/20 rounded-full blur-xl"></div>
      </div>

      <!-- Total Requests Card -->
      <div
        class="relative bg-gradient-to-br from-purple-500/90 to-purple-600/90 p-6 rounded-xl text-white overflow-hidden">
        <div class="absolute top-0 left-0 w-3 h-3 bg-purple-400 rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-3 h-3 bg-purple-700 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2">
          <i class="pi pi-server text-2xl text-purple-200"></i>
          <h4 class="text-sm font-medium text-purple-100">Total Requests</h4>
        </div>
        <p class="mt-2 text-3xl font-bold flex items-baseline">
          12,345
          <span class="text-sm ml-2 text-purple-200">This Month</span>
        </p>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-purple-400/20 rounded-full blur-xl"></div>
      </div>
    </div>
  </div>
</template>

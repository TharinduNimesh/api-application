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
      <div class="relative bg-gradient-to-br from-blue-500/90 to-blue-600/90 p-6 rounded-xl text-white overflow-hidden shadow-lg transform transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
        <div class="absolute top-0 left-0 w-3 h-3 bg-blue-300 rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-800 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2">
          <i class="pi pi-check-circle text-2xl text-blue-100"></i>
          <h4 class="text-sm font-medium text-blue-50">Success Rate</h4>
        </div>
        <p class="mt-2 text-3xl font-bold flex items-baseline">
          98.5%
          <span class="text-sm ml-2 text-blue-200">↑ 2.1%</span>
        </p>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-400/20 rounded-full blur-xl"></div>
      </div>

      <!-- Average Response Card -->
      <div
        class="relative bg-gradient-to-br from-emerald-500/90 to-emerald-600/90 p-6 rounded-xl text-white overflow-hidden shadow-lg transform transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
        <div class="absolute top-0 left-0 w-3 h-3 bg-emerald-300 rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-800 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2">
          <i class="pi pi-clock text-2xl text-emerald-100"></i>
          <h4 class="text-sm font-medium text-emerald-50">Avg Response</h4>
        </div>
        <p class="mt-2 text-3xl font-bold flex items-baseline">
          245ms
          <span class="text-sm ml-2 text-emerald-200">↓ 12ms</span>
        </p>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-400/20 rounded-full blur-xl"></div>
      </div>

      <!-- Total Requests Card -->
      <div
        class="relative bg-gradient-to-br from-violet-500/90 to-violet-600/90 p-6 rounded-xl text-white overflow-hidden shadow-lg transform transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
        <div class="absolute top-0 left-0 w-3 h-3 bg-violet-300 rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-3 h-3 bg-violet-800 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2">
          <i class="pi pi-server text-2xl text-violet-100"></i>
          <h4 class="text-sm font-medium text-violet-50">Total Requests</h4>
        </div>
        <p class="mt-2 text-3xl font-bold flex items-baseline">
          12,345
          <span class="text-sm ml-2 text-violet-200">This Month</span>
        </p>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-violet-400/20 rounded-full blur-xl"></div>
      </div>
    </div>
  </div>
</template>

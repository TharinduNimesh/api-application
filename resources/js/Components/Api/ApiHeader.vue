<script setup lang="ts">
import { ref, defineProps, defineEmits, onMounted } from 'vue';
import { Link } from "@inertiajs/vue3";
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Api } from '@/types/api';

const props = defineProps<{
  api: Api;
  onDelete: () => void;
}>();

defineEmits<{
  (e: 'back'): void;
}>();

// Remove subtitle text and simplify mobile menu
const showMobileActions = ref(false);

// Add ripple effect for buttons
const addRipple = () => {
  import('primevue/ripple').then(() => {});
};

onMounted(() => {
  addRipple();
});
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
    <!-- Header Background Pattern -->
    <div class="absolute inset-0 opacity-5">
      <div class="absolute inset-0 pattern-background"></div>
    </div>

    <!-- Main Content -->
    <div class="relative p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Left Side -->
        <div class="flex items-center gap-4">
          <Button
            icon="pi pi-arrow-left"
            text
            rounded
            @click="$emit('back')"
            class="hover:bg-gray-100/80 backdrop-blur-sm transition-all duration-200 w-10 h-10"
            v-ripple
          />
            <div class="flex flex-col gap-3 min-w-0">
            <!-- Header Row -->
            <div>
              <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 truncate group-hover:text-indigo-600 transition-colors duration-200">
              {{ api.name }}
              </h1>
            </div>
            <!-- Status Indicators Row -->
            <div class="flex gap-2 items-center">
              <Tag 
              :severity="api.type === 'FREE' ? 'success' : 'warning'" 
              :value="api.type"
              class="text-xs font-medium px-2.5 py-1 rounded-full"
              />
              <div class="flex items-center gap-1.5">
              <span 
                :class="[
                'flex h-2.5 w-2.5 rounded-full',
                api.status === 'ACTIVE' ? 'bg-green-400 animate-pulse' : 'bg-red-400'
                ]"
              ></span>
              <Tag 
                :severity="api.status === 'ACTIVE' ? 'success' : 'danger'" 
                :value="api.status"
                class="text-xs font-medium px-2.5 py-1 rounded-full"
              />
              </div>
            </div>
            </div>
        </div>

        <!-- Right Side - Admin Actions -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <template v-if="$page.props.auth.user.role === 'admin'">
            <Link :href="route('dashboard', api.id)" class="w-1/2 sm:w-auto">
              <Button
          label="Edit API"
          icon="pi pi-pencil"
          severity="secondary"
          class="hover:scale-102 transition-all duration-200 shadow-sm hover:shadow w-full"
          v-ripple
              />
            </Link>
            <Button
              label="Delete API"
              icon="pi pi-trash"
              severity="danger"
              class="hover:scale-102 transition-all duration-200 shadow-sm hover:shadow w-1/2 sm:w-auto"
              v-ripple
              @click="onDelete"
            />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.p-button {
  height: 40px;
}

.p-tag {
  white-space: nowrap;
}

/* Add hover effect for buttons */
.hover\:scale-102:hover {
  transform: scale(1.02);
}

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Custom animation for active status */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.pattern-background {
  background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%239C92AC' fill-opacity='0.2' fill-rule='evenodd'%3E%3Ccircle cx='3' cy='3' r='3'/%3E%3Ccircle cx='13' cy='13' r='3'/%3E%3C/g%3E%3C/svg%3E");
}
</style>

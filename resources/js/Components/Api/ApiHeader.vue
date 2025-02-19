<script setup lang="ts">
import { ref, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import { Link, router } from "@inertiajs/vue3";
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import ButtonGroup from 'primevue/buttongroup';
import Dialog from 'primevue/dialog';
import type { Api } from '@/types/api';

const props = defineProps<{
  api: Api;
  onDelete: () => void;
}>();

const emit = defineEmits<{
  (e: 'back'): void;
}>();

// Modal visibility states
const showArchiveModal = ref(false);
const showRestoreModal = ref(false);
const showDeleteModal = ref(false);
const loading = ref(false);

// Create reactive local state
const localApi = ref({ ...props.api });

// API action handlers
const handleArchive = async () => {
  try {
    loading.value = true;
    await axios.patch(route('api.archive', props.api.id));
    
    // Update local state
    localApi.value.status = 'INACTIVE';
    showArchiveModal.value = false;
  } catch (error) {
    console.error('Error archiving API:', error);
  } finally {
    loading.value = false;
  }
};

const handleUnarchive = async () => {
  try {
    loading.value = true;
    await axios.patch(`/api/${props.api.id}/activate`);
    
    // Update local state
    localApi.value.status = 'ACTIVE';
    showRestoreModal.value = false;
  } catch (error) {
    console.error('Error restoring API:', error);
  } finally {
    loading.value = false;
  }
};

const handleDelete = async () => {
  try {
    loading.value = true;
    await axios.delete(route('api.destroy', props.api.id));
    
    // Navigate to dashboard using route
    router.visit(route('dashboard'));
  } catch (error) {
    console.error('Error deleting API:', error);
  } finally {
    loading.value = false;
    showDeleteModal.value = false;
  }
};
</script>

<template>
  <!-- Update template to use localApi instead of api prop -->
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
          />
            <div class="flex flex-col gap-3 min-w-0">
            <!-- Header Row -->
            <div>
              <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 truncate group-hover:text-indigo-600 transition-colors duration-200">
              {{ localApi.name }}
              </h1>
            </div>
            <!-- Status Indicators Row -->
            <div class="flex gap-2 items-center">
              <Tag 
              :severity="localApi.type === 'FREE' ? 'success' : 'warning'" 
              :value="localApi.type"
              class="text-xs font-medium px-2.5 py-1 rounded-full"
              />
              <div class="flex items-center gap-1.5">
              <span 
                :class="[
                'flex h-2.5 w-2.5 rounded-full',
                localApi.status === 'ACTIVE' ? 'bg-green-400 animate-pulse' : 'bg-red-400'
                ]"
              ></span>
              <Tag 
                :severity="localApi.status === 'ACTIVE' ? 'success' : 'danger'" 
                :value="localApi.status"
                class="text-xs font-medium px-2.5 py-1 rounded-full"
              />
              </div>
            </div>
            </div>
        </div>

        <!-- Right Side - Admin Actions -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
          <template v-if="$page.props.auth.user.role === 'admin'">
            <!-- Primary Actions -->
            <ButtonGroup class="w-full sm:w-auto">
              <Link :href="route('api.edit', api.id)" class="flex-1 sm:flex-none">
                <Button
                  v-tooltip.bottom="'Edit API configuration'"
                  label="Edit"
                  icon="pi pi-pencil"
                  severity="secondary"
                  class="action-button"
                />
              </Link>
              
              <!-- Archive/Unarchive Button -->
              <Button
                v-if="localApi.status === 'ACTIVE'"
                v-tooltip.bottom="'Archive this API'"
                label="Archive"
                icon="pi pi-inbox"
                severity="warning"
                class="action-button"
                @click="showArchiveModal = true"
              />
              <Button
                v-else
                v-tooltip.bottom="'Restore this API'"
                label="Restore"
                icon="pi pi-refresh"
                severity="success"
                class="action-button"
                @click="showRestoreModal = true"
              />
            </ButtonGroup>

            <!-- Delete Button (Only shown for archived APIs) -->
            <Button
              v-if="localApi.status !== 'ACTIVE'"
              v-tooltip.bottom="'Permanently delete this API'"
              label="Delete"
              icon="pi pi-trash"
              severity="danger"
              class="action-button danger-button"
              @click="showDeleteModal = true"
            />
          </template>
        </div>
      </div>
    </div>

    <!-- Confirmation Modals -->
    <Dialog
      v-model:visible="showArchiveModal"
      modal
      header="Archive API"
      :style="{ width: '450px' }"
      :closable="!loading"
    >
      <p class="my-4">
        Are you sure you want to archive this API? It will become inaccessible to users until restored.
      </p>
      <template #footer>
        <Button
          label="Cancel"
          icon="pi pi-times"
          text
          @click="showArchiveModal = false"
          :disabled="loading"
        />
        <Button
          label="Archive"
          icon="pi pi-archive"
          severity="warning"
          @click="handleArchive"
          :loading="loading"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="showRestoreModal"
      modal
      header="Restore API"
      :style="{ width: '450px' }"
      :closable="!loading"
    >
      <p class="my-4">
        Are you sure you want to restore this API? It will become accessible to users again.
      </p>
      <template #footer>
        <Button
          label="Cancel"
          icon="pi pi-times"
          text
          @click="showRestoreModal = false"
          :disabled="loading"
        />
        <Button
          label="Restore"
          icon="pi pi-check"
          severity="success"
          @click="handleUnarchive"
          :loading="loading"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="showDeleteModal"
      modal
      header="Delete API"
      :style="{ width: '450px' }"
      :closable="!loading"
    >
      <p class="my-4">
        Are you sure you want to permanently delete this API? This action cannot be undone.
      </p>
      <template #footer>
        <Button
          label="Cancel"
          icon="pi pi-times"
          text
          @click="showDeleteModal = false"
          :disabled="loading"
        />
        <Button
          label="Delete"
          icon="pi pi-trash"
          severity="danger"
          @click="handleDelete"
          :loading="loading"
        />
      </template>
    </Dialog>
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

.action-button {
  height: 2.5rem;
  padding-left: 1rem;
  padding-right: 1rem;
  transition: all 200ms;
  backdrop-filter: blur(4px);
  min-width: 120px;
}

.action-button:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  filter: brightness(1.1);
}

.danger-button {
  border: 2px solid rgb(220 38 38);
}

.danger-button:hover {
  background-color: rgb(254 242 242);
}

/* Button group styling */
:deep(.p-buttongroup) {
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  border-radius: 0.5rem;
  overflow: hidden;
}

:deep(.p-buttongroup .p-button) {
  border-radius: 0;
  border-right-width: 1px;
  border-right-style: solid;
  border-right-color: rgba(0, 0, 0, 0.1);
}

:deep(.p-buttongroup .p-button:last-child) {
  border-right: none;
}

/* Tooltip customization */
:deep(.p-tooltip) {
  font-size: 0.75rem;
  font-weight: 500;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .action-button {
    width: 100%;
    justify-content: center;
    min-width: auto;
  }
  
  :deep(.p-buttongroup) {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
  }
  
  :deep(.p-buttongroup .p-button) {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  }
}

/* Add modal styling */
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

<script setup lang="ts">
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import EndpointForm from "@/Components/Form/EndpointForm.vue";
import { CreateApiSchema } from "@/types/api";
import { useToast } from "primevue/usetoast";
import Accordion from "primevue/accordion";
import Textarea from "primevue/textarea";
import EndpointEditor from "@/Components/Form/EndpointEditor.vue";
import EndpointSummary from "@/Components/Form/EndpointSummary.vue";
import { v4 as uuidv4 } from "uuid";
// Add Dialog import
import Dialog from "primevue/dialog";
import { z } from "zod";
import type { Endpoint, CreateApi } from "@/types/api";
import axios from "axios";

const form = ref<CreateApi>({
  name: "",
  description: "",
  type: "FREE",
  baseUrl: "",
  rateLimit: 60,
  endpoints: [],
});

const toast = useToast();
const errors = ref<Record<string, string>>({});
// Add processing ref
const processing = ref(false);

const addEndpoint = () => {
  form.value.endpoints.push({
    name: "",
    method: "GET",
    path: "",
    description: "",
    parameters: [],
  });
};

const removeEndpoint = (index: number) => {
  form.value.endpoints.splice(index, 1);
};

const handleSubmit = async () => {
  try {
    processing.value = true;
    const validatedData = CreateApiSchema.parse(form.value);

    const response = await axios.post('/api/apis', validatedData);
    
    processing.value = false;
    toast.add({
      severity: "success",
      summary: "Success", 
      detail: "API created successfully",
      life: 3000,
    });
    
    // Redirect to dashboard after success
    router.visit(route('dashboard'));

  } catch (err) {
    processing.value = false;
    
    if (err instanceof z.ZodError) {
      err.errors.forEach((error: z.ZodIssue) => {
        errors.value[error.path.join(".")] = error.message;
      });
    } else if (axios.isAxiosError(err)) {
      // Handle API errors
      errors.value = err.response?.data?.errors || {};
      toast.add({
        severity: "error",
        summary: "Error",
        detail: "Please check the form for errors",
        life: 3000,
      });
    }
  }
};

const showEndpointEditor = ref(false);
const currentEndpoint = ref<Endpoint>({
  id: "",
  name: "",
  method: "GET",
  path: "",
  description: "",
  parameters: [],
});
const editingEndpointId = ref<string | null>(null);

const handleAddEndpoint = () => {
  currentEndpoint.value = {
    id: "",
    name: "",
    method: "GET",
    path: "",
    description: "",
    parameters: [],
  };
  showEndpointEditor.value = true;
};

const handleEditEndpoint = (endpoint: Endpoint) => {
  currentEndpoint.value = { ...endpoint };
  editingEndpointId.value = endpoint.id || null;
  showEndpointEditor.value = true;
};

const handleDeleteEndpoint = (endpointId: string) => {
  form.value.endpoints = form.value.endpoints.filter(
    (e: Endpoint) => e.id !== endpointId
  );
};

const handleSaveEndpoint = () => {
  if (editingEndpointId.value) {
    // Update existing endpoint
    const index = form.value.endpoints.findIndex(
      (e: Endpoint) => e.id === editingEndpointId.value
    );
    if (index !== -1) {
      form.value.endpoints[index] = { ...currentEndpoint.value };
    }
  } else {
    // Add new endpoint
    form.value.endpoints.push({
      ...currentEndpoint.value,
      id: uuidv4(),
    });
  }

  showEndpointEditor.value = false;
  editingEndpointId.value = null;
};

const handleCancelEndpoint = () => {
  showEndpointEditor.value = false;
  editingEndpointId.value = null;
};
</script>

<template>
  <Head title="Create API" />

  <AuthenticatedLayout>
    <Toast />
    <!-- Add this line at the top level -->

    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-5">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              text
              rounded
              @click="router.visit(route('dashboard'))"
              class="p-0"
              severity="contrast"
            />
            <h2 class="text-2xl font-semibold text-gray-800">Create New API</h2>
          </div>
        </div>
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Basic Info Card -->
          <div class="bg-white p-6 rounded-xl shadow-sm space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700"
                  >API Name</label
                >
                <InputText
                  v-model="form.name"
                  :class="{ 'p-invalid': errors['name'] }"
                  class="w-full"
                />
                <small class="text-red-500">{{ errors["name"] }}</small>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Type</label>
                <SelectButton
                  v-model="form.type"
                  :options="['FREE', 'PAID']"
                  class="w-full"
                />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700">Base URL</label>
              <InputText
                v-model="form.baseUrl"
                :class="{ 'p-invalid': errors['baseUrl'] }"
                class="w-full"
                placeholder="https://api.example.com"
              />
              <small class="text-red-500">{{ errors["baseUrl"] }}</small>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700"
                >Description</label
              >
              <Textarea
                v-model="form.description"
                rows="5"
                class="w-full"
                placeholder="Provide a detailed description of your API..."
                :class="{ 'p-invalid': errors['description'] }"
              />
              <small class="text-red-500">{{ errors["description"] }}</small>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700"
                >Rate Limit (requests per Hour)</label
              >
              <InputNumber
                v-model="form.rateLimit"
                :min="1"
                :max="1000"
                class="w-full"
              />
            </div>
          </div>

          <!-- Endpoints Card -->
          <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Endpoints</h3>
              <Button
                label="Add Endpoint"
                icon="pi pi-plus"
                @click="handleAddEndpoint"
                severity="secondary"
              />
            </div>

            <Dialog
              v-model:visible="showEndpointEditor"
              modal
              :style="{ width: '80vw' }"
              :header="editingEndpointId ? 'Edit Endpoint' : 'Add Endpoint'"
            >
              <EndpointEditor
                v-model="currentEndpoint"
                @save="handleSaveEndpoint"
                @cancel="handleCancelEndpoint"
              />
            </Dialog>

            <div class="space-y-4">
              <template v-if="form.endpoints.length === 0">
                <div class="text-center py-8 text-gray-500">
                  No endpoints added yet. Click "Add Endpoint" to create one.
                </div>
              </template>

              <template v-else>
                <EndpointSummary
                  v-for="endpoint in form.endpoints"
                  :key="endpoint.id"
                  :endpoint="endpoint"
                  @edit="handleEditEndpoint(endpoint)"
                  @delete="handleDeleteEndpoint(endpoint.id ?? '')"
                />
              </template>
            </div>

            <small class="text-red-500">{{ errors["endpoints"] }}</small>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end gap-3">
            <Button
              label="Cancel"
              severity="secondary"
              text
              type="button"
              @click="router.visit(route('dashboard'))"
            />
            <Button
              label="Create API"
              type="submit"
              severity="primary"
              :loading="processing"
            />
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

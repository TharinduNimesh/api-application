<script setup lang="ts">
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Message from 'primevue/message';
import { ref, computed } from 'vue';

interface ApiAssignment {
  apiId: string;
  rateLimit: number;
}

interface DepartmentForm {
  name: string;
  description: string;
  apiAssignments: ApiAssignment[];
}

interface ApiOption {
  id: string;
  name: string;
  type: 'FREE' | 'PAID';
  status: 'ACTIVE' | 'INACTIVE';
  description?: string;
  endpoint?: string;
}

const props = defineProps<{
  apis: ApiOption[];
}>();

const visible = ref(false);
const form = ref<DepartmentForm>({
  name: '',
  description: '',
  apiAssignments: []
});

// For the new API selection
const selectedApi = ref<ApiOption | null>(null);
const rateLimit = ref<number>(100);

const emit = defineEmits<{
  (e: 'submit', form: DepartmentForm): void
}>();

const availableApis = computed(() => {
  return props.apis.filter(api => 
    !form.value.apiAssignments.some(assignment => assignment.apiId === api.id)
  );
});

const assignedApis = computed(() => {
  return form.value.apiAssignments.map(assignment => {
    const apiDetails = props.apis.find(api => api.id === assignment.apiId);
    return {
      ...assignment,
      name: apiDetails?.name || 'Unknown API',
      type: apiDetails?.type || 'FREE',
      endpoint: apiDetails?.endpoint || '',
      description: apiDetails?.description || ''
    };
  });
});

const show = () => {
  visible.value = true;
};

const hide = () => {
  visible.value = false;
  resetForm();
};

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    apiAssignments: []
  };
  selectedApi.value = null;
  rateLimit.value = 100;
};

const addApiAssignment = () => {
  if (!selectedApi.value) return;
  
  form.value.apiAssignments.push({
    apiId: selectedApi.value.id,
    rateLimit: rateLimit.value
  });
  
  selectedApi.value = null;
  rateLimit.value = 100;
};

const removeApiAssignment = (index: number) => {
  form.value.apiAssignments.splice(index, 1);
};

const updateRateLimit = (apiId: string, newLimit: number) => {
  const assignment = form.value.apiAssignments.find(a => a.apiId === apiId);
  if (assignment) {
    assignment.rateLimit = newLimit;
  }
};

const getApiTypeLabel = (type: string) => {
  return type === 'PAID' ? 'Premium' : 'Free';
};

const getSeverity = (type: string) => {
  return type === 'PAID' ? 'warning' : 'success';
};

const handleSubmit = () => {
  emit('submit', form.value);
  hide();
};

defineExpose({
  show,
  hide
});
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="Create New Department"
    :style="{ width: '90vw', maxWidth: '700px' }"
    :closable="true"
    class="p-fluid create-department-modal"
  >
    <div class="flex flex-col gap-4 p-3">
      <div class="field w-full">
        <label for="name" class="font-medium text-gray-700 mb-2 block">Department Name</label>
        <InputText
          id="name"
          v-model="form.name"
          required
          class="w-full"
          placeholder="Enter department name"
          autofocus
        />
      </div>

      <div class="field w-full">
        <label for="description" class="font-medium text-gray-700 mb-2 block">Description</label>
        <Textarea
          id="description"
          v-model="form.description"
          required
          rows="3"
          class="w-full"
          placeholder="Enter department description"
        />
      </div>

      <Divider align="left">
        <div class="inline-flex align-items-center">
          <i class="pi pi-link mr-2"></i>
          <b>API Assignments</b>
        </div>
      </Divider>

      <div class="field w-full">
        <div class="card p-0">
          <div class="grid formgrid">
            <div class="col-12 md:col-7 mb-2">
              <label for="api-dropdown" class="font-medium text-gray-700 mb-2 block">Select API</label>
              <Dropdown
                v-model="selectedApi"
                :options="availableApis"
                optionLabel="name"
                placeholder="Choose an API to assign"
                class="w-full"
                :filter="true"
                inputId="api-dropdown"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="flex align-items-center">
                    <Tag :value="getApiTypeLabel(slotProps.value.type)" :severity="getSeverity(slotProps.value.type)" class="mr-2" />
                    <span>{{ slotProps.value.name }}</span>
                  </div>
                  <span v-else>{{ slotProps.placeholder }}</span>
                </template>
                <template #option="{ option }">
                  <div class="flex align-items-center">
                    <Tag :value="getApiTypeLabel(option.type)" :severity="getSeverity(option.type)" class="mr-2" />
                    <span>{{ option.name }}</span>
                  </div>
                </template>
              </Dropdown>
            </div>

            <div class="col-8 md:col-3 mb-2">
              <label for="rate-limit" class="font-medium text-gray-700 mb-2 block">Rate Limit</label>
              <InputNumber
                v-model="rateLimit"
                inputId="rate-limit"
                :min="1"
                suffix=" /hour"
                class="w-full"
                placeholder="100"
              />
            </div>

            <div class="col-4 md:col-2 mb-2">
              <label class="invisible font-medium text-gray-700 mb-2 block">Action</label>
              <Button
                icon="pi pi-plus"
                label="Add"
                class="w-full"
                @click="addApiAssignment"
                :disabled="!selectedApi"
              />
            </div>
          </div>

          <Message v-if="availableApis.length === 0" severity="info" :closable="false" class="my-3">
            All available APIs have been assigned.
          </Message>

          <div class="mt-3 border border-gray-200 rounded-lg overflow-hidden">
            <DataTable
              v-if="assignedApis.length > 0"
              :value="assignedApis"
              stripedRows
              class="p-datatable-sm assigned-apis-table"
              responsiveLayout="stack"
              breakpoint="768px"
            >
              <Column field="name" header="API Name">
                <template #body="{ data }">
                  <div class="flex align-items-center">
                    <Tag :value="getApiTypeLabel(data.type)" :severity="getSeverity(data.type)" class="mr-2" />
                    <span>{{ data.name }}</span>
                  </div>
                </template>
              </Column>
              <Column field="rateLimit" header="Rate Limit">
                <template #body="{ data }">
                  <div class="flex align-items-center">
                    <InputNumber
                      v-model="data.rateLimit"
                      :min="1"
                      suffix=" /hour"
                      :showButtons="true"
                      buttonLayout="horizontal"
                      decrementButtonClass="p-button-secondary p-button-sm"
                      incrementButtonClass="p-button-secondary p-button-sm"
                      incrementButtonIcon="pi pi-plus"
                      decrementButtonIcon="pi pi-minus"
                      class="flex-grow-1 w-full sm:w-auto"
                      size="small"
                      @update:modelValue="updateRateLimit(data.apiId, $event)"
                    />
                  </div>
                </template>
              </Column>
              <Column :exportable="false" style="width: 4rem; min-width: 4rem;">
                <template #body="{ index }">
                  <Button
                    icon="pi pi-trash"
                    rounded
                    text
                    severity="danger"
                    size="small"
                    @click="removeApiAssignment(index)"
                    aria-label="Delete"
                  />
                </template>
              </Column>
            </DataTable>
            
            <div v-else class="text-center py-4 text-gray-500 border-gray-200 border-dashed rounded-lg">
              <i class="pi pi-link-slash text-4xl mb-3 text-gray-400"></i>
              <div>No APIs assigned yet</div>
              <div class="text-sm text-gray-400">Select an API and specify rate limit to assign it</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-content-end gap-3 px-3 pt-3 border-t border-gray-200">
        <Button
          label="Cancel"
          icon="pi pi-times"
          @click="hide"
          class="p-button-text"
          severity="secondary"
        />
        <Button
          label="Create Department"
          icon="pi pi-check"
          @click="handleSubmit"
          severity="primary"
          :disabled="form.name.trim() === '' || form.apiAssignments.length === 0"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
:deep(.p-dialog-content) {
  padding: 0 !important;
}

:deep(.p-inputtext) {
  padding: 0.75rem 1rem;
}

:deep(.p-dialog-header) {
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.p-dialog-footer) {
  padding: 0 0 1.5rem 0;
}

:deep(.p-card-content) {
  padding: 0;
}

:deep(.p-tag) {
  padding: 0.15rem 0.5rem;
}

:deep(.p-inputnumber-input) {
  width: 100%;
}

:deep(.p-datatable.p-datatable-sm .p-datatable-thead > tr > th) {
  padding: 0.5rem 0.75rem;
  border-color: #e5e7eb;
}

:deep(.p-datatable.p-datatable-sm .p-datatable-tbody > tr > td) {
  padding: 0.5rem 0.75rem;
  border-color: #e5e7eb;
}

:deep(.assigned-apis-table) {
  border: none;
}

:deep(.p-float-label) {
  display: none;
}

:deep(.p-dropdown),
:deep(.p-inputtext),
:deep(.p-inputnumber-input) {
  border-color: #e5e7eb;
}

:deep(.p-dropdown:hover),
:deep(.p-inputtext:hover),
:deep(.p-inputnumber-input:hover) {
  border-color: #d1d5db;
}

:deep(.p-dropdown:focus),
:deep(.p-inputtext:focus),
:deep(.p-inputnumber-input:focus) {
  border-color: #f97316; /* Orange-500 */
  box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2); /* Orange with opacity */
}

@media screen and (max-width: 768px) {
  :deep(.p-datatable-responsive .p-datatable-tbody > tr > td .p-column-title) {
    font-weight: 600;
    margin-right: 0.5rem;
  }
}
</style>
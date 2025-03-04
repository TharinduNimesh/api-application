<script setup lang="ts">
import type { Endpoint, Parameter, ParameterLocation, ParameterType } from '@/types/api';
import { ref, computed } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import Dialog from 'primevue/dialog';
import Chips from 'primevue/chips';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';

interface MimeTypeOption {
    label: string;
    value: string[];
}

// Common MIME types for file uploads
const commonMimeTypes: MimeTypeOption[] = [
    { label: 'Images (JPEG, PNG)', value: ['image/jpeg', 'image/png'] },
    { label: 'PDF Documents', value: ['application/pdf'] },
    { label: 'Word Documents', value: ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'] },
    { label: 'Excel Spreadsheets', value: ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'] },
    { label: 'CSV Files', value: ['text/csv'] },
    { label: 'Text Files', value: ['text/plain'] },
    { label: 'Zip Archives', value: ['application/zip'] }
];

const selectedMimeTypes = computed({
    get: () => {
        if (!newParameter.value.fileConfig?.mimeTypes.length) return [];
        return commonMimeTypes.filter(type =>
            type.value.some(mime => newParameter.value.fileConfig!.mimeTypes.includes(mime))
        );
    },
    set: (selected: MimeTypeOption[]) => {
        if (newParameter.value.fileConfig) {
            newParameter.value.fileConfig.mimeTypes = selected.flatMap(item => item.value);
        }
    }
});

const props = defineProps<{
    modelValue: Endpoint;
    isEditing?: boolean; // Add new prop to determine if we're editing
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Endpoint): void;
    (e: 'save'): void;
    (e: 'cancel'): void;
    (e: 'parameter-updated', id: string): void;
    (e: 'parameter-deleted', id: string): void;
}>();

const parameterLocations: { label: string; value: ParameterLocation; icon: string }[] = [
    { label: 'Query', value: 'query', icon: 'pi pi-question-circle' },
    { label: 'Path', value: 'path', icon: 'pi pi-sliders-h' },
    { label: 'Body', value: 'body', icon: 'pi pi-box' },
    { label: 'Header', value: 'header', icon: 'pi pi-server' }
];

const httpMethods = [
    { label: 'GET', value: 'GET', severity: 'success' },
    { label: 'POST', value: 'POST', severity: 'info' },
    { label: 'PUT', value: 'PUT', severity: 'warning' },
    { label: 'DELETE', value: 'DELETE', severity: 'danger' },
    { label: 'PATCH', value: 'PATCH', severity: 'secondary' }
];

const parameterTypes = [
    { label: 'String', value: 'string' as ParameterType },
    { label: 'Number', value: 'number' as ParameterType },
    { label: 'Boolean', value: 'boolean' as ParameterType },
    { label: 'Object', value: 'object' as ParameterType },
    { label: 'Array', value: 'array' as ParameterType },
    { label: 'File', value: 'file' as ParameterType }
];

const newParameter = ref<{
    name: string;
    type: ParameterType;
    location: ParameterLocation;
    required: boolean;
    description: string;
    defaultValue: string;
    fileConfig?: {
        mimeTypes: string[];
        maxSize: number;
        multiple: boolean;
    };
}>({
    name: '',
    type: 'string',
    location: 'query',
    required: true,
    description: '',
    defaultValue: '',
    fileConfig: {
        mimeTypes: [],
        maxSize: 5 * 1024 * 1024, // 5MB default
        multiple: false
    }
});

const addParameter = () => {
    const endpoint = { ...props.modelValue };
    endpoint.parameters.push({ ...newParameter.value, id: uuidv4() });
    emit('update:modelValue', endpoint);
    // Reset form
    newParameter.value = {
        name: '',
        type: 'string',
        location: 'query',
        required: true,
        description: '',
        defaultValue: '',
        fileConfig: {
            mimeTypes: [],
            maxSize: 5 * 1024 * 1024,
            multiple: false
        }
    };
};

const handleParameterUpdate = (parameter: Parameter) => {
    if (parameter.id) {
        emit('parameter-updated', parameter.id);
    }
};

const handleParameterDelete = (parameter: Parameter) => {
    if (parameter.id) {
        emit('parameter-deleted', parameter.id);
    }
};

const isValid = computed(() => {
    return props.modelValue.name && 
           props.modelValue.path && 
           props.modelValue.description;
});

const showConfirmModal = ref(false);
const confirmLoading = ref(false);

const handleSave = () => {
    if (isValid.value) {
        if (props.isEditing) {
            showConfirmModal.value = true;
        } else {
            // For new endpoints, save directly
            emit('save');
        }
    }
};

const handleConfirmedSave = () => {
    confirmLoading.value = true;
    emit('save');
    showConfirmModal.value = false;
    confirmLoading.value = false;
};
</script>

<template>
    <div class="space-y-6 bg-white p-6 rounded-xl">
        <!-- Method and Name -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Method</label>
                <Dropdown
                    v-model="modelValue.method"
                    :options="httpMethods"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                >
                    <template #value="{ value }">
                        <Tag :severity="httpMethods.find(m => m.value === value)?.severity">
                            {{ value }}
                        </Tag>
                    </template>
                    <template #option="{ option }">
                        <Tag :severity="option.severity">{{ option.label }}</Tag>
                    </template>
                </Dropdown>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Endpoint Name</label>
                <InputText 
                    v-model="modelValue.name"
                    class="w-full"
                    placeholder="e.g., Get User Profile"
                />
            </div>
        </div>

        <!-- Path and Description -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Path</label>
                <InputText 
                    v-model="modelValue.path"
                    class="w-full"
                    placeholder="/users/{id}"
                />
                <small class="text-gray-500">Use {paramName} for path parameters</small>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <Textarea 
                    v-model="modelValue.description"
                    rows="3"
                    class="w-full"
                    placeholder="Describe what this endpoint does..."
                />
            </div>
        </div>

        <!-- Parameters -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-lg font-medium text-gray-900">Parameters</h4>
            </div>

            <!-- Parameter Form -->
            <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <InputText
                        v-model="newParameter.name"
                        placeholder="Parameter name"
                        class="w-full"
                    />
                    
                    <Dropdown
                        v-model="newParameter.location"
                        :options="parameterLocations"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Location"
                        class="w-full"
                        :disabled="newParameter.type === 'file'"
                        v-tooltip="newParameter.type === 'file' ? 'File parameters must be in body' : ''"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <i :class="option.icon" />
                                {{ option.label }}
                            </div>
                        </template>
                    </Dropdown>

                    <Dropdown
                        v-model="newParameter.type"
                        :options="parameterTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Type"
                        class="w-full"
                        @change="(e) => {
                            if (e.value === 'file') {
                                newParameter.location = 'body';
                            }
                        }"
                    />

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="newParameter.required"
                                :binary="true"
                            />
                            <label>Required</label>
                        </div>
                        <Button
                            icon="pi pi-plus"
                            @click="addParameter"
                            :disabled="!newParameter.name"
                            text
                            severity="secondary"
                        />
                    </div>
                </div>

                <InputText
                    v-model="newParameter.description"
                    placeholder="Parameter description"
                    class="w-full"
                />

                <!-- File Configuration Fields -->
                <div v-if="newParameter.type === 'file'" class="space-y-4 border-t pt-4">
                    <h5 class="font-medium">File Configuration</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm text-gray-600">Allowed MIME Types</label>
                            <div class="flex flex-col gap-2">
                                <MultiSelect
                                    v-model="selectedMimeTypes"
                                    :options="commonMimeTypes"
                                    optionLabel="label"
                                    :filter="true"
                                    placeholder="Select file types"
                                    class="w-full"
                                >
                                    <template #value="{ value }: { value: MimeTypeOption[] }">
                                        <div v-if="value && value.length" class="flex flex-wrap gap-1">
                                            {{ value.map((type: MimeTypeOption) => type.label).join(', ') }}
                                        </div>
                                        <div v-else>
                                            Select file types...
                                        </div>
                                    </template>
                                </MultiSelect>
                                <small class="text-gray-500">You can search and select multiple file types</small>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm text-gray-600">Max File Size (bytes)</label>
                            <InputNumber v-model="newParameter.fileConfig!.maxSize" class="w-full" />
                            <small class="text-gray-500">Default: 5MB (5242880 bytes)</small>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="newParameter.fileConfig!.multiple" :binary="true" />
                            <label class="text-sm text-gray-600">Allow Multiple Files</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parameters List -->
            <DataTable :value="modelValue.parameters" class="mt-4">
                <Column field="name" header="Name" />
                <Column field="location" header="Location">
                    <template #body="{ data }">
                        <Tag :severity="data.location === 'body' ? 'warning' : 'info'">
                            {{ data.location }}
                        </Tag>
                    </template>
                </Column>
                <Column field="type" header="Type">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data.type }}</div>
                            <div v-if="data.type === 'file'" class="text-xs text-gray-500">
                                <div>MIME Types: {{ data.fileConfig?.mimeTypes.join(', ') || 'Any' }}</div>
                                <div>Max Size: {{ (data.fileConfig?.maxSize || 0) / (1024 * 1024) }}MB</div>
                                <div>Multiple: {{ data.fileConfig?.multiple ? 'Yes' : 'No' }}</div>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column field="required" header="Required">
                    <template #body="{ data }">
                        <i :class="['pi', data.required ? 'pi-check text-green-500' : 'pi-times text-red-500']" />
                    </template>
                </Column>
                <Column :exportable="false" style="min-width: 8rem">
                    <template #body="{ data, index }">
                        <Button 
                            icon="pi pi-trash" 
                            @click="modelValue.parameters.splice(index, 1); handleParameterDelete(data);"
                            text
                            severity="danger"
                            size="small"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t">
            <Button
                label="Cancel"
                @click="$emit('cancel')"
                text
                severity="secondary"
            />
            <Button
                label="Save Endpoint"
                @click="handleSave"
                :disabled="!isValid"
                severity="primary"
            />
        </div>

        <!-- Add Confirmation Modal - Only shown when editing -->
        <Dialog
            v-if="isEditing"
            v-model:visible="showConfirmModal"
            modal
            header="Confirm Changes"
            :style="{ width: '450px' }"
            :closable="!confirmLoading"
        >
            <div class="space-y-4">
                <p class="text-gray-600">
                    Are you sure you want to save these changes to the endpoint?
                </p>
                
                <!-- Show summary of changes -->
                <div class="bg-gray-50 p-4 rounded text-sm space-y-2">
                    <p><strong>Method:</strong> {{ modelValue.method }}</p>
                    <p><strong>Name:</strong> {{ modelValue.name }}</p>
                    <p><strong>Path:</strong> {{ modelValue.path }}</p>
                    <p><strong>Parameters:</strong> {{ modelValue.parameters.length }}</p>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="showConfirmModal = false"
                    :disabled="confirmLoading"
                />
                <Button
                    label="Save Changes"
                    icon="pi pi-check"
                    severity="primary"
                    @click="handleConfirmedSave"
                    :loading="confirmLoading"
                />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
/* Add styles for confirmation dialog */
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

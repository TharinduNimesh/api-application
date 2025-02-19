<script setup lang="ts">
import type { Endpoint, ParameterLocation, ParameterType } from '@/types/api';
import { ref, computed } from 'vue';
import { v4 as uuidv4 } from 'uuid';

const props = defineProps<{
    modelValue: Endpoint;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Endpoint): void;
    (e: 'save'): void;
    (e: 'cancel'): void;
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
    { label: 'Array', value: 'array' as ParameterType }
];

const newParameter = ref<{
    name: string;
    type: ParameterType;
    location: ParameterLocation;
    required: boolean;
    description: string;
    defaultValue: string;
}>({
    name: '',
    type: 'string',
    location: 'query',
    required: true,
    description: '',
    defaultValue: ''
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
        defaultValue: ''
    };
};

const isValid = computed(() => {
    return props.modelValue.name && 
           props.modelValue.path && 
           props.modelValue.description;
});

const handleSave = () => {
    if (isValid.value) {
        emit('save');
    }
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
                <Column field="type" header="Type" />
                <Column field="required" header="Required">
                    <template #body="{ data }">
                        <i :class="['pi', data.required ? 'pi-check text-green-500' : 'pi-times text-red-500']" />
                    </template>
                </Column>
                <Column :exportable="false" style="min-width: 8rem">
                    <template #body="{ data, index }">
                        <Button 
                            icon="pi pi-trash" 
                            @click="modelValue.parameters.splice(index, 1)"
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
    </div>
</template>

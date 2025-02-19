<script setup lang="ts">
import { ref } from 'vue';
import type { Endpoint } from '@/types/api';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Chip from 'primevue/chip';

const props = defineProps<{
    modelValue: Endpoint;
    index: number;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Endpoint): void;
    (e: 'remove'): void;
}>();

const httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
const parameterTypes = ['string', 'number', 'boolean', 'object', 'array'];

const addParameter = () => {
    const newEndpoint = { ...props.modelValue };
    newEndpoint.parameters.push({
        name: '',
        type: 'string',
        location: 'query', // Add this
        required: true,
        description: ''
    });
    emit('update:modelValue', newEndpoint);
};

const removeParameter = (index: number) => {
    const newEndpoint = { ...props.modelValue };
    newEndpoint.parameters.splice(index, 1);
    emit('update:modelValue', newEndpoint);
};
</script>

<template>
    <AccordionTab>
        <template #header>
            <div class="flex items-center gap-3">
                <Chip :label="modelValue.method" 
                      :severity="modelValue.method === 'GET' ? 'success' : 
                               modelValue.method === 'POST' ? 'info' :
                               modelValue.method === 'PUT' ? 'warning' :
                               modelValue.method === 'DELETE' ? 'danger' : 'secondary'" 
                />
                <span>{{ modelValue.name || `Endpoint ${index + 1}` }}</span>
            </div>
        </template>

        <div class="space-y-4 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700">Name</label>
                    <InputText 
                        v-model="modelValue.name"
                        class="w-full"
                        placeholder="e.g., Get User Details"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700">Method</label>
                    <Dropdown
                        v-model="modelValue.method"
                        :options="httpMethods"
                        class="w-full"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Path</label>
                <InputText 
                    v-model="modelValue.path"
                    class="w-full"
                    placeholder="/users/{id}"
                />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Description</label>
                <Textarea 
                    v-model="modelValue.description"
                    rows="3"
                    class="w-full"
                    placeholder="Describe what this endpoint does..."
                />
            </div>

            <!-- Parameters Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Parameters</label>
                    <Button 
                        icon="pi pi-plus"
                        severity="secondary"
                        text
                        @click="addParameter"
                    />
                </div>

                <div v-for="(param, idx) in modelValue.parameters" 
                     :key="idx"
                     class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg"
                >
                    <InputText 
                        v-model="param.name"
                        placeholder="Parameter name"
                    />
                    <Dropdown
                        v-model="param.type"
                        :options="parameterTypes"
                        placeholder="Type"
                    />
                    <div class="flex items-center gap-2">
                        <Checkbox 
                            v-model="param.required"
                            :binary="true"
                        />
                        <label>Required</label>
                    </div>
                    <div class="md:col-span-4">
                        <InputText 
                            v-model="param.description"
                            placeholder="Parameter description"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button 
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    @click="$emit('remove')"
                />
            </div>
        </div>
    </AccordionTab>
</template>

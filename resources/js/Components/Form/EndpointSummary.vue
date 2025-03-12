<script setup lang="ts">
import type { Endpoint } from '@/types/api';
import { computed } from 'vue';

const props = defineProps<{
    endpoint: Endpoint;
    baseUrl?: string;
}>();

const emit = defineEmits<{
    (e: 'edit'): void;
    (e: 'delete'): void;
    (e: 'test'): void;
}>();

const getMethodColor = (method: string) => {
    switch (method) {
        case 'GET': return 'success';
        case 'POST': return 'info';
        case 'PUT': return 'warning';
        case 'DELETE': return 'danger';
        default: return 'secondary';
    }
};

const canTest = computed(() => {
    // Check if baseUrl is provided and not empty
    if (!props.baseUrl?.trim()) {
        return false;
    }

    // Check if endpoint has name and path
    if (!props.endpoint.name?.trim() || !props.endpoint.path?.trim()) {
        return false;
    }

    // Check if all required parameters have names
    const hasInvalidRequiredParams = props.endpoint.parameters.some(
        param => param.required && !param.name?.trim()
    );

    return !hasInvalidRequiredParams;
});
</script>

<template>
    <div class="bg-white p-4 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors">
        <div class="flex items-start justify-between">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <Tag :severity="getMethodColor(endpoint.method)">
                        {{ endpoint.method }}
                    </Tag>
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ endpoint.name }}
                    </h3>
                </div>
                <p class="text-gray-500 text-sm">
                    {{ endpoint.path }}
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <Button
                    v-if="canTest"
                    icon="pi pi-play"
                    @click="$emit('test')"
                    text
                    rounded
                    severity="info"
                    v-tooltip.bottom="'Test Endpoint'"
                />
                <Button
                    icon="pi pi-pencil"
                    @click="$emit('edit')"
                    text
                    rounded
                    severity="secondary"
                />
                <Button
                    icon="pi pi-trash"
                    @click="$emit('delete')"
                    text
                    rounded
                    severity="danger"
                />
            </div>
        </div>

        <div class="mt-4">
            <p class="text-sm text-gray-600">
                {{ endpoint.description }}
            </p>
        </div>

        <!-- Parameter Summary -->
        <div v-if="endpoint.parameters.length > 0" class="mt-4">
            <div class="flex gap-2 flex-wrap">
                <Tag 
                    v-for="param in endpoint.parameters" 
                    :key="param.name"
                    :severity="param.required ? 'warning' : 'secondary'"
                    class="text-xs"
                >
                    {{ param.name }}
                    <span class="text-xs opacity-75">{{ param.location }}</span>
                </Tag>
            </div>
        </div>
    </div>
</template>

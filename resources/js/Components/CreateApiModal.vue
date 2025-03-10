<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { Link, router } from '@inertiajs/vue3';
import FileUploadZone from '@/Components/FileUploadZone.vue';
import Message from 'primevue/message';
import OverlayPanel from 'primevue/overlaypanel';
import Tooltip from 'primevue/tooltip';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

const props = defineProps<{
    visible: boolean
}>();

const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void
    (e: 'refresh'): void
}>();

const onHide = () => {
    resetState();
    emit('update:visible', false);
};

const showFileUpload = ref(false);
const uploadedFile = ref<File | null>(null);
const fileContent = ref('');
const validationError = ref('');
const validationStatus = ref<'idle' | 'valid' | 'invalid'>('idle');
const overlayPanel = ref();
const isValidFile = computed(() => uploadedFile.value !== null && validationStatus.value === 'valid');
const toast = useToast();
const isSubmitting = ref(false);

// Reset state when modal visibility changes
watch(() => props.visible, (newValue) => {
    if (!newValue) {
        resetState();
    }
});

const resetState = () => {
    showFileUpload.value = false;
    uploadedFile.value = null;
    fileContent.value = '';
    validationError.value = '';
    validationStatus.value = 'idle';
    isSubmitting.value = false;
};

const validateOpenAPIStructure = (jsonData: any): boolean => {
    try {
        // Check required OpenAPI structure fields
        const isValid = 
            typeof jsonData === 'object' &&
            jsonData.openapi && 
            jsonData.info && 
            jsonData.info.title &&
            jsonData.info.version &&
            jsonData.paths &&
            Object.keys(jsonData.paths).length > 0;

        if (!isValid) {
            validationError.value = 'Invalid OpenAPI format. The file must be a valid OpenAPI 3.0 specification.';
            validationStatus.value = 'invalid';
        } else {
            validationError.value = '';
            validationStatus.value = 'valid';
        }
        
        return isValid;
    } catch (error) {
        validationError.value = 'Error validating OpenAPI structure';
        validationStatus.value = 'invalid';
        return false;
    }
};

const handleFileContent = (data: { file: File, content: string }) => {
    uploadedFile.value = data.file;
    fileContent.value = data.content;
    
    try {
        const jsonData = JSON.parse(data.content);
        validateOpenAPIStructure(jsonData);
    } catch (error) {
        validationError.value = `Error parsing JSON file: ${error instanceof Error ? error.message : 'Invalid JSON format'}`;
        validationStatus.value = 'invalid';
    }
};

const submitImport = async () => {
    if (!isValidFile.value || isSubmitting.value) return;
    
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('file', uploadedFile.value as File);

    try {
        const response = await axios.post(route('api.import'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        const result = response.data;
        
        toast.add({
            severity: 'success',
            summary: 'Import Successful',
            detail: `Successfully imported ${result.data.success.length} endpoints${result.data.failed.length ? ` (${result.data.failed.length} failed)` : ''}`,
            life: 3000
        });

        // Close modal and emit refresh event
        emit('update:visible', false);
        emit('refresh');
        
    } catch (error) {
        const message = axios.isAxiosError(error) 
            ? error.response?.data?.message || 'Failed to import API'
            : 'An unexpected error occurred';
            
        validationError.value = message;
        validationStatus.value = 'invalid';
        
        toast.add({
            severity: 'error',
            summary: 'Import Failed',
            detail: message,
            life: 5000
        });
    } finally {
        isSubmitting.value = false;
    }
};

const showDownloadPanel = (event: Event) => {
    overlayPanel.value.toggle(event);
};
</script>

<template>
    <Dialog :visible="visible" 
        @update:visible="onHide" 
        modal 
        header="Create New API"
        :style="{ width: '42rem' }" 
        :breakpoints="{ '960px': '75vw', '641px': '90vw' }" 
        :pt="{
            root: { class: 'rounded-lg' },
            header: { class: 'text-lg font-semibold text-gray-900' },
        }"
    >
        <div class="space-y-4 p-4">
            <!-- Options Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Manual Creation Option -->
                <Link 
                    :href="route('api.create')"
                    class="group border border-gray-200 hover:border-primary-500 rounded-lg p-6 transition-all duration-200 hover:shadow-md bg-white"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                            <i class="pi pi-pencil text-xl text-primary-700"></i>
                        </div>
                        <i class="pi pi-arrow-right text-gray-400 group-hover:text-primary-600 transition-colors"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Add Manually</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Create a new API by filling out the form with detailed configuration options.
                    </p>
                </Link>

                <!-- JSON Import Option -->
                <div 
                    :class="[
                        'group border rounded-lg p-6 transition-all duration-200 cursor-pointer',
                        showFileUpload 
                            ? 'bg-gray-800 border-gray-700 shadow-md' 
                            : 'bg-white border-gray-200 hover:border-orange-500 hover:shadow-md'
                    ]"
                    @click="showFileUpload = true"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div :class="[
                            'w-12 h-12 rounded-full flex items-center justify-center transition-colors',
                            showFileUpload 
                                ? 'bg-gray-700' 
                                : 'bg-orange-100 group-hover:bg-orange-200'
                        ]">
                            <i class="pi pi-file-import text-xl" :class="showFileUpload ? 'text-orange-300' : 'text-orange-700'"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold mb-2" :class="showFileUpload ? 'text-white' : 'text-gray-900'">
                        Import APIs
                    </h3>
                    <p class="text-sm leading-relaxed" :class="showFileUpload ? 'text-gray-300' : 'text-gray-500'">
                        Import APIs from OpenAPI specification JSON file.
                    </p>
                </div>
            </div>

            <!-- File Upload Row -->
            <div v-if="showFileUpload" 
                class="border border-gray-200 rounded-lg p-6 bg-white mt-4"
            >
                <div class="flex items-center gap-3 mb-4">
                    <i class="pi pi-file-import text-xl text-orange-600"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Import APIs from OpenAPI JSON</h3>
                </div>
                
                <FileUploadZone 
                    v-model="uploadedFile"
                    :config="{
                        multiple: false,
                        maxSize: 1 * 1024 * 1024, // 1MB
                        mimeTypes: ['application/json']
                    }"
                    @file-read="handleFileContent"
                />
                
                <!-- Validation Messages -->
                <div v-if="validationStatus === 'valid'" class="mt-4">
                    <Message severity="success" :closable="false">
                        <div class="flex items-center">
                            <i class="pi pi-check-circle mr-2"></i>
                            <span>Valid OpenAPI specification detected. You can proceed with the import.</span>
                        </div>
                    </Message>
                </div>
                
                <div v-if="validationStatus === 'invalid'" class="mt-4">
                    <Message severity="error" :closable="false">
                        <div class="flex items-center gap-2 justify-between">
                            <div class="flex items-center">
                                <i class="pi pi-times-circle mr-2"></i>
                                <span>{{ validationError }}</span>
                            </div>
                            <button 
                                type="button"
                                class="p-2 hover:bg-red-50 rounded-full transition-colors"
                                @mouseover="showDownloadPanel"
                            >
                                <i class="pi pi-download text-orange-600"></i>
                            </button>
                        </div>
                    </Message>
                </div>
                
                <OverlayPanel ref="overlayPanel" :showCloseIcon="true" :dismissable="true">
                    <div class="p-3 w-72">
                        <h5 class="text-lg font-medium mb-2">OpenAPI Sample</h5>
                        <p class="text-sm text-gray-600 mb-3">
                            Download this sample file to see the required structure for OpenAPI specification.
                        </p>
                        <a href="/sample-openapi.json" download class="flex items-center justify-center gap-2 p-2 bg-orange-100 hover:bg-orange-200 transition-colors text-orange-800 rounded-md">
                            <i class="pi pi-file-export text-lg"></i>
                            <span>sample-openapi.json</span>
                        </a>
                    </div>
                </OverlayPanel>
                
                <div class="flex justify-end mt-4">
                    <Button 
                        label="Import API" 
                        icon="pi pi-check" 
                        :disabled="!isValidFile"
                        :loading="isSubmitting"
                        @click="submitImport"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.group:hover {
    text-decoration: none;
}

:deep(.p-button) {
    background: #F97316;
    border-color: #F97316;
}

:deep(.p-button:hover) {
    background: #EA580C !important;
    border-color: #EA580C !important;
}

:deep(.p-button:disabled) {
    background: #FDA4AF !important;
    border-color: #FDA4AF !important;
    cursor: not-allowed;
    opacity: 0.6;
}

:deep(.p-button-outlined) {
    background: transparent !important;
    color: #475569;
    border-color: #CBD5E1;
}

:deep(.p-button-outlined:hover) {
    background: #F1F5F9 !important;
    color: #475569 !important;
    border-color: #94A3B8 !important;
}

:deep(.p-button-outlined .p-button-icon) {
    color: #F97316;
}

:deep(.p-message-error) {
    border-color: #FEE2E2;
    background-color: #FEF2F2;
}

:deep(.p-message-error .p-message-icon) {
    color: #EF4444;
}

:deep(.p-message-success) {
    border-color: #DCFCE7;
    background-color: #F0FDF4;
}

:deep(.p-message-success .p-message-icon) {
    color: #22C55E;
}
</style>
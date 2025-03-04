<script setup lang="ts">
import { ref } from 'vue';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';
import Button from 'primevue/button';

interface FileConfig {
    multiple?: boolean;
    maxSize?: number;
    mimeTypes?: string[];
}

const props = defineProps<{
    modelValue: any;
    config?: FileConfig;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'file-read', value: { file: File, content: string }): void;
}>();

const fileErrors = ref<string[]>([]);

// Default configuration
const defaultConfig = {
    multiple: false,
    maxSize: 5 * 1024 * 1024, // 5MB
    mimeTypes: [],
};

const config = ref<FileConfig>({
    ...defaultConfig,
    ...props.config,
});

const validateFileSize = (file: File, maxSize: number): boolean => {
    return file.size <= maxSize;
};

const validateMimeType = (file: File, allowedTypes: string[]): boolean => {
    return allowedTypes.length === 0 || allowedTypes.includes(file.type);
};

const readFileAsText = (file: File): Promise<string> => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result as string);
        reader.onerror = () => reject(new Error('Failed to read file'));
        reader.readAsText(file);
    });
};

const handleFileRemove = (event: any) => {
    const removedFile = event.file;

    if (props.config?.multiple) {
        const updatedFiles = Array.isArray(props.modelValue) ?
            props.modelValue.filter((file: File) => file.name !== removedFile.name) :
            [];
        emit('update:modelValue', updatedFiles);
    } else {
        if (props.modelValue?.objectURL) {
            URL.revokeObjectURL(props.modelValue.objectURL);
        }
        emit('update:modelValue', null);
    }
};

const handleFileUpload = async (event: any) => {
    const files = Array.from(event.files) as File[];
    fileErrors.value = [];

    let validFiles = [];

    for (const file of files) {
        let isValid = true;

        // Check file size
        if (!validateFileSize(file, config.value.maxSize || defaultConfig.maxSize)) {
            fileErrors.value.push(`File "${file.name}" exceeds maximum size of ${(config.value.maxSize || defaultConfig.maxSize) / (1024 * 1024)}MB`);
            isValid = false;
        }

        // Check MIME type
        if (!validateMimeType(file, config.value.mimeTypes || [])) {
            fileErrors.value.push(`File "${file.name}" has invalid type. Allowed: ${(config.value.mimeTypes || []).join(', ') || 'All'}`);
            isValid = false;
        }

        // Add preview URL for valid files
        if (isValid) {
            (file as any).objectURL = URL.createObjectURL(file);
            validFiles.push(file);

            // Read file content and emit it
            try {
                const content = await readFileAsText(file);
                emit('file-read', { file, content });
            } catch (error) {
                console.error('Error reading file:', error);
            }
        }
    }

    // Update model value
    if (props.config?.multiple) {
        const currentFiles = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        emit('update:modelValue', [...currentFiles, ...validFiles]);
    } else {
        // Clean up previous objectURL if exists
        if (props.modelValue?.objectURL) {
            URL.revokeObjectURL(props.modelValue.objectURL);
        }
        emit('update:modelValue', validFiles[0] || null);
    }
};

// Clean up objectURLs when component unmounts
const cleanupObjectURLs = () => {
    if (props.config?.multiple && Array.isArray(props.modelValue)) {
        props.modelValue.forEach(file => {
            if (file.objectURL) {
                URL.revokeObjectURL(file.objectURL);
            }
        });
    } else if (props.modelValue?.objectURL) {
        URL.revokeObjectURL(props.modelValue.objectURL);
    }
};

// Cleanup when component unmounts
import { onBeforeUnmount } from 'vue';
onBeforeUnmount(cleanupObjectURLs);

const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};
</script>

<template>
    <div class="file-upload-zone">
        <FileUpload :multiple="config.multiple" :accept="config.mimeTypes?.join(',')" :maxFileSize="config.maxSize"
            @select="handleFileUpload" :auto="true" chooseLabel="Choose Files" class="w-full" :showUploadButton="false"
            :showCancelButton="false" customUpload>
            <template #empty>
                <div class="p-4 border-2 border-dashed border-gray-300 rounded-lg text-center">
                    <div class="text-gray-600 mb-2">
                        <i class="pi pi-upload text-2xl"></i>
                        <p class="mt-2">Drag and drop files here or click to browse</p>
                    </div>
                    <ul class="text-xs text-gray-500 text-left list-disc pl-4 mt-2">
                        <li>Maximum file size: {{ formatFileSize(config.maxSize || defaultConfig.maxSize) }}</li>
                        <li>Allowed types: {{ config.mimeTypes?.length ? config.mimeTypes.join(', ') : 'All' }}</li>
                        <li>Multiple files: {{ config.multiple ? 'Yes' : 'No' }}</li>
                    </ul>
                </div>
            </template>
        </FileUpload>

        <!-- File Previews -->
        <div v-if="modelValue" class="space-y-2 mt-3">
            <div v-if="config.multiple && Array.isArray(modelValue)">
                <div v-for="file in modelValue" :key="file.name"
                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg mb-2">
                    <div v-if="file?.type?.startsWith('image/')" class="w-16 h-16 rounded-lg overflow-hidden">
                        <img :src="file.objectURL" class="w-full h-full object-cover" alt="preview" />
                    </div>
                    <div v-else class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center">
                        <i class="pi pi-file text-2xl text-gray-400"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">{{ file.name }}</div>
                        <div class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</div>
                    </div>
                    <button class="p-2 rounded-full hover:bg-gray-200 transition-colors duration-200 focus:outline-none"
                        @click="handleFileRemove({ file })">
                        <i class="pi pi-times text-gray-500 hover:text-gray-700"></i>
                    </button>
                </div>
            </div>
            <div v-else-if="modelValue && typeof modelValue === 'object'"
                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div v-if="modelValue?.type?.startsWith('image/')" class="w-16 h-16 rounded-lg overflow-hidden">
                    <img :src="modelValue.objectURL" class="w-full h-full object-cover" alt="preview" />
                </div>
                <div v-else class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center">
                    <i class="pi pi-file text-2xl text-gray-400"></i>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium">{{ modelValue.name }}</div>
                    <div class="text-xs text-gray-500">{{ formatFileSize(modelValue.size) }}</div>
                </div>
                <Button icon="pi pi-times" severity="secondary" text @click="handleFileRemove({ file: modelValue })" />
            </div>
        </div>

        <!-- Error Messages -->
        <div v-if="fileErrors.length" class="space-y-1 mt-2">
            <Message v-for="error in fileErrors" :key="error" severity="error" :text="error" class="w-full" />
        </div>
    </div>
</template>

<style scoped>
:deep(.p-fileupload) {
    border: none;
    padding: 0;
}

:deep(.p-fileupload-content) {
    padding: 0;
    border: none;
    background: transparent;
}

:deep(.p-fileupload-row) {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.5rem;
    background: rgb(249, 250, 251);
    margin-bottom: 0.5rem;
}

:deep(.p-fileupload-choose) {
    width: 100%;
}

:deep(.p-button.p-fileupload-choose) {
    background: transparent;
    border: 2px dashed rgb(229, 231, 235);
    color: rgb(107, 114, 128);
    padding: 2rem;
    width: 100%;
    justify-content: center;
}

:deep(.p-button.p-fileupload-choose:hover) {
    background: rgb(249, 250, 251);
    border-color: #F97316;
}

:deep(.p-fileupload-files) {
    margin-top: 1rem;
}

:deep(.p-button.p-button-text:hover) {
    color: #F97316 !important;
    background: transparent !important;
}
</style>
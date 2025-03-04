<script setup>
import { ref, watch } from 'vue';
import JsonInput from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ScrollPanel from 'primevue/scrollpanel';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';
import Button from 'primevue/button';

const props = defineProps({
  endpoint: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update:data']);
const requestData = ref({});
const fileErrors = ref({});

const validateFileSize = (file, maxSize) => {
  return file.size <= maxSize;
};

const validateMimeType = (file, allowedTypes) => {
  return allowedTypes.length === 0 || allowedTypes.includes(file.type);
};

const handleFileRemove = (event, param) => {
  console.log('Remove event:', event);
  const removedFile = event.file;
  console.log('Removing file:', removedFile);

  if (param.fileConfig.multiple) {
    console.log('Current files:', requestData.value[param.name]);
    requestData.value[param.name] = requestData.value[param.name].filter(
      file => file.name !== removedFile.name
    );
    console.log('After removal:', requestData.value[param.name]);
  } else {
    if (requestData.value[param.name]?.objectURL) {
      URL.revokeObjectURL(requestData.value[param.name].objectURL);
    }
    requestData.value[param.name] = null;
  }
  emit('update:data', requestData.value);
};

const handleFileUpload = (event, param) => {
  console.log('File upload event:', event);
  console.log('Files:', event.files);
  
  const files = Array.from(event.files);
  fileErrors.value[param.name] = [];

  // Validate each file
  const validFiles = files.filter(file => {
    console.log('Processing file:', file.name, file.type, file.size);
    let isValid = true;
    
    // Check file size
    if (!validateFileSize(file, param.fileConfig.maxSize)) {
      fileErrors.value[param.name].push(`File "${file.name}" exceeds maximum size of ${param.fileConfig.maxSize / (1024 * 1024)}MB`);
      isValid = false;
    }

    // Check MIME type
    if (!validateMimeType(file, param.fileConfig.mimeTypes)) {
      fileErrors.value[param.name].push(`File "${file.name}" has invalid type. Allowed: ${param.fileConfig.mimeTypes.join(', ')}`);
      isValid = false;
    }

    // Add preview URL for all files
    if (isValid) {
      console.log('Creating object URL for file:', file.name);
      file.objectURL = URL.createObjectURL(file);
      console.log('Created objectURL:', file.objectURL);
    }

    return isValid;
  });

  console.log('Valid files:', validFiles);

  // Update request data
  if (param.fileConfig.multiple) {
    requestData.value[param.name] = [
      ...(requestData.value[param.name] || []),
      ...validFiles
    ];
  } else {
    if (requestData.value[param.name]?.objectURL) {
      URL.revokeObjectURL(requestData.value[param.name].objectURL);
    }
    requestData.value[param.name] = validFiles[0] || null;
  }

  console.log('Updated request data:', requestData.value[param.name]);
  emit('update:data', requestData.value);
};

const initRequestData = () => {
  requestData.value = {};
  fileErrors.value = {};
  if (props.endpoint.parameters) {
    props.endpoint.parameters.forEach(param => {
      if (param.type === 'file') {
        requestData.value[param.name] = param.fileConfig.multiple ? [] : null;
      } else {
        requestData.value[param.name] = param.type === 'json' ? '{}' : '';
      }
    });
  }
  emit('update:data', requestData.value);
};

watch(() => props.endpoint, initRequestData, { immediate: true });
watch(requestData, (value) => emit('update:data', value), { deep: true });

// Debug watcher for file changes
watch(() => requestData.value, (newVal, oldVal) => {
  Object.keys(newVal).forEach(key => {
    if (newVal[key] && (Array.isArray(newVal[key]) || newVal[key].objectURL)) {
      console.log('File data changed for:', key);
      console.log('New value:', newVal[key]);
      if (Array.isArray(newVal[key])) {
        newVal[key].forEach(file => {
          console.log('File in array:', file.name, file.objectURL);
        });
      } else {
        console.log('Single file:', newVal[key].name, newVal[key].objectURL);
      }
    }
  });
}, { deep: true });

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};
</script>

<template>
  <ScrollPanel class="h-full">
    <div class="p-6 space-y-8">
      <!-- Description Card -->
      <div class="bg-blue-50/50 border border-blue-100/50 rounded-xl p-5">
        <h3 class="text-sm font-medium text-blue-800 mb-2">Description</h3>
        <p class="text-blue-700">{{ endpoint.description }}</p>
      </div>

      <!-- Parameters Form -->
      <div v-if="endpoint.parameters?.length" class="space-y-6">
        <h3 class="text-sm font-medium text-gray-700">Request Parameters</h3>
        <div 
          v-for="param in endpoint.parameters" 
          :key="param.name"
          class="bg-white border border-gray-300   shadow-sm rounded-xl p-5 space-y-4"
        >
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ param.name }}</span>
                <Tag 
                  v-if="param.required"
                  severity="danger"
                  value="Required"
                  class="text-xs"
                />
              </div>
              <p class="text-sm text-gray-500 mt-1">{{ param.description }}</p>
            </div>
            <Tag :value="param.type" severity="info" class="text-xs" />
          </div>

          <div class="pt-2">
            <template v-if="param.type === 'file'">
              <div class="space-y-2">
                  <FileUpload
                    ref="fileUpload"
                    :multiple="param.fileConfig.multiple"
                    :accept="param.fileConfig.mimeTypes.join(',')"
                    :maxFileSize="param.fileConfig.maxSize"
                    @select="(e) => handleFileUpload(e, param)"
                    :auto="true"
                    chooseLabel="Choose Files"
                    class="w-full"
                    :showUploadButton="false"
                    :showCancelButton="false"
                    customUpload
                  >
                    <template #empty>
                      <div class="p-4 border-2 border-dashed border-gray-300 rounded-lg text-center">
                        <div class="text-gray-600 mb-2">
                          <i class="pi pi-upload text-2xl"></i>
                          <p class="mt-2">Drag and drop files here or click to browse</p>
                        </div>
                        <ul class="text-xs text-gray-500 text-left list-disc pl-4 mt-2">
                          <li>Maximum file size: {{ formatFileSize(param.fileConfig.maxSize) }}</li>
                          <li>Allowed types: {{ param.fileConfig.mimeTypes.length ? param.fileConfig.mimeTypes.join(', ') : 'All' }}</li>
                          <li>Multiple files: {{ param.fileConfig.multiple ? 'Yes' : 'No' }}</li>
                        </ul>
                      </div>
                    </template>
                  </FileUpload>

                  <!-- File Previews -->
                  <div v-if="requestData[param.name]" class="space-y-2">
                    <div v-if="param.fileConfig.multiple && Array.isArray(requestData[param.name])">
                      <div v-for="file in requestData[param.name]" :key="file.name"
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
                        <Button
                          icon="pi pi-times"
                          severity="secondary"
                          text
                          @click="handleFileRemove({ file }, param)"
                        />
                      </div>
                    </div>
                    <div v-else-if="requestData[param.name] && typeof requestData[param.name] === 'object'"
                         class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                      <div v-if="requestData[param.name]?.type?.startsWith('image/')" class="w-16 h-16 rounded-lg overflow-hidden">
                        <img :src="requestData[param.name].objectURL" class="w-full h-full object-cover" alt="preview" />
                      </div>
                      <div v-else class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center">
                        <i class="pi pi-file text-2xl text-gray-400"></i>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm font-medium">{{ requestData[param.name].name }}</div>
                        <div class="text-xs text-gray-500">{{ formatFileSize(requestData[param.name].size) }}</div>
                      </div>
                      <Button
                        icon="pi pi-times"
                        severity="secondary"
                        text
                        @click="handleFileRemove({ file: requestData[param.name] }, param)"
                      />
                    </div>
                  </div>
                <div v-if="fileErrors[param.name]?.length" class="space-y-1">
                  <Message v-for="error in fileErrors[param.name]"
                          :key="error"
                          severity="error"
                          :text="error"
                          class="w-full" />
                </div>
              </div>
            </template>
            <template v-else-if="param.type === 'json'">
              <JsonInput
                v-model="requestData[param.name]"
                autoResize
                rows="6"
                class="w-full font-mono text-sm"
                :placeholder="'Enter JSON data...'"
              />
            </template>
            <template v-else>
              <InputText
                v-model="requestData[param.name]"
                :placeholder="`Enter ${param.name}...`"
                class="w-full"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </ScrollPanel>
</template>

<style scoped>
:deep(.p-inputtext),
:deep(.p-inputtextarea) {
  border-color: rgb(243, 244, 246);
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

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
  border-color: rgb(209, 213, 219);
}

:deep(.p-fileupload-files) {
  margin-top: 1rem;
}
</style>

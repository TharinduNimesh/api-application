<script setup>
import { ref, watch } from 'vue';
import JsonInput from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ScrollPanel from 'primevue/scrollpanel';

const props = defineProps({
  endpoint: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update:data']);
const requestData = ref({});

const initRequestData = () => {
  requestData.value = {};
  if (props.endpoint.parameters) {
    props.endpoint.parameters.forEach(param => {
      requestData.value[param.name] = param.type === 'json' ? '{}' : '';
    });
  }
  emit('update:data', requestData.value);
};

watch(() => props.endpoint, initRequestData, { immediate: true });
watch(requestData, (value) => emit('update:data', value), { deep: true });
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
            <JsonInput
              v-if="param.type === 'json'"
              v-model="requestData[param.name]"
              autoResize
              rows="6"
              class="w-full font-mono text-sm"
              :placeholder="'Enter JSON data...'"
            />
            <InputText
              v-else
              v-model="requestData[param.name]"
              :placeholder="`Enter ${param.name}...`"
              class="w-full"
            />
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
</style>

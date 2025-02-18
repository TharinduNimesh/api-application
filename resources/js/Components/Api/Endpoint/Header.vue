<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
  endpoint: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);
const copySuccess = ref(false);

const getMethodColor = (method) => {
  const colors = {
    GET: 'success',
    POST: 'info',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'warning'
  };
  return colors[method] || 'info';
};

const copyToClipboard = async () => {
  await navigator.clipboard.writeText(props.endpoint.path);
  copySuccess.value = true;
  setTimeout(() => copySuccess.value = false, 2000);
};
</script>

<template>
  <div class="border-b border-gray-100 bg-white sticky top-0 z-20">
    <div class="px-6 py-4 flex items-center gap-4">
      <Tag 
        :value="endpoint.method" 
        :severity="getMethodColor(endpoint.method)" 
        class="text-base px-3 py-1.5"
      />
      <div class="flex-1">
        <h2 class="text-xl font-semibold text-gray-800">{{ endpoint.name }}</h2>
      </div>
      <Button 
        icon="pi pi-times" 
        text 
        rounded 
        @click="$emit('close')"
        class="!w-10 !h-10"
      />
    </div>

    <div class="px-6 py-3 bg-gray-50/50 flex items-center gap-3">
      <div class="flex-1 bg-white rounded-lg border border-gray-100 shadow-sm px-4 py-2 font-mono text-sm text-slate-600">
        {{ endpoint.path }}
      </div>
      <Button
        icon="pi pi-copy"
        text
        rounded
        v-tooltip.left="copySuccess ? 'Copied!' : 'Copy URL'"
        :class="{ '!text-green-500': copySuccess }"
        @click="copyToClipboard"
      />
    </div>
  </div>
</template>

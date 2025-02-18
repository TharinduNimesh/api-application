<script setup>
import { ref } from 'vue';
import ScrollPanel from 'primevue/scrollpanel';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
  response: {
    type: [Object, Array],
    default: null
  },
  error: {
    type: String,
    default: null
  }
});

const copySuccess = ref(false);

const copyToClipboard = async () => {
  await navigator.clipboard.writeText(JSON.stringify(props.response, null, 2));
  copySuccess.value = true;
  setTimeout(() => copySuccess.value = false, 2000);
};
</script>

<template>
  <ScrollPanel class="h-full">
    <div class="p-6">
      <div v-if="!response && !error" class="text-center py-12">
        <i class="pi pi-send text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Send a request to see the response</p>
      </div>

      <Message v-if="error" severity="error" class="mb-4">
        {{ error }}
      </Message>

      <div v-if="response" class="space-y-4">
        <div class="flex items-center justify-between">
          <Tag severity="success" value="Success" />
          <Button
            icon="pi pi-copy"
            text
            rounded
            :class="{ '!text-green-500': copySuccess }"
            @click="copyToClipboard"
            v-tooltip.left="'Copy Response'"
          />
        </div>
        <div class="bg-gray-900 rounded-xl p-6 shadow-lg border border-gray-800">
          <pre class="text-sm font-mono text-gray-100 whitespace-pre-wrap">{{ JSON.stringify(response, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </ScrollPanel>
</template>

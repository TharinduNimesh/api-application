<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import ScrollPanel from 'primevue/scrollpanel';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useCodeHighlighter } from '@/composables/useCodeHighlighter';

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

const { highlightCode } = useCodeHighlighter();
const copySuccess = ref(false);
const isExpanded = ref(false);
const highlightedCode = ref('');
const MAX_HEIGHT = 300;
const codeHeight = ref(0);

const showExpandButton = computed(() => codeHeight.value > MAX_HEIGHT);

// Add a computed property for the button icon
const expandButtonIcon = computed(() => isExpanded.value ? 'pi pi-chevron-up' : 'pi pi-chevron-down');

const copyToClipboard = async () => {
  if (props.response) {
    try {
      await navigator.clipboard.writeText(JSON.stringify(props.response, null, 2));
      copySuccess.value = true;
      setTimeout(() => {
        copySuccess.value = false;
      }, 2000);
    } catch (error) {
      console.error('Failed to copy to clipboard:', error);
    }
  }
};

const updateHighlightedCode = async () => {
  if (props.response) {
    try {
      const formatted = JSON.stringify(props.response, null, 2);
      highlightedCode.value = await highlightCode(formatted);
      
      // Need to wait for next tick to get correct height
      await nextTick();
      const codeElement = document.querySelector('.code-container pre');
      if (codeElement) {
        codeHeight.value = codeElement.scrollHeight;
      }
    } catch (error) {
      console.error('Failed to highlight code:', error);
    }
  }
};

watch(() => props.response, updateHighlightedCode, { immediate: true });
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
        <div 
          class="bg-gray-50 rounded-xl p-6 shadow-lg border border-gray-200 relative"
          :class="{ 'code-collapsed': !isExpanded && showExpandButton }"
        >
          <div 
            class="code-container"
            :style="{ maxHeight: !isExpanded && showExpandButton ? MAX_HEIGHT + 'px' : 'none' }"
            v-html="highlightedCode"
          />
          <div 
            v-if="showExpandButton"
            class="absolute bottom-0 left-0 right-0 text-center pb-2 pt-16"
            :class="{ 'expand-overlay': !isExpanded }"
          >
            <Button
              :label="isExpanded ? 'Show Less' : 'Show More'"
              :icon="expandButtonIcon"
              :iconPos="isExpanded ? 'right' : 'right'"
              text
              @click="isExpanded = !isExpanded"
              class="bg-white shadow-sm transition-transform"
              :class="{ 'rotate-icon': isExpanded }"
            />
          </div>
        </div>
      </div>
    </div>
  </ScrollPanel>
</template>

<style scoped>
.code-container {
  overflow: hidden;
}

.code-collapsed .code-container {
  mask-image: linear-gradient(to bottom, black 60%, transparent 100%);
}

.expand-overlay {
  background: linear-gradient(to bottom, transparent, white 40%);
}

.rotate-icon :deep(.p-button-icon) {
  transition: transform 0.2s ease;
}

.rotate-icon.p-button:hover :deep(.p-button-icon) {
  transform: translateY(2px);
}
</style>

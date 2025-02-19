<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import ScrollPanel from 'primevue/scrollpanel';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Skeleton from 'primevue/skeleton';
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
const isLoading = ref(false);
const codeContainerRef = ref(null);
const observer = ref(null);

const showExpandButton = computed(() => codeHeight.value > MAX_HEIGHT);

// Add a computed property for the button icon
const expandButtonIcon = computed(() => isExpanded.value ? 'pi pi-chevron-up' : 'pi pi-chevron-down');

// New computed properties to extract response data and status
const responseData = computed(() => {
  if (props.response && typeof props.response === 'object' && props.response.data !== undefined) {
    return props.response.data;
  }
  return props.response;
});

const responseStatus = computed(() => {
  if (props.response && typeof props.response === 'object' && props.response.status !== undefined) {
    return props.response.status;
  }
  return '';
});

// New computed property for status Tag
const statusTag = computed(() => {
  const status = parseInt(responseStatus.value);
  if (isNaN(status)) return { label: 'Unknown', severity: 'info' };
  if (status >= 200 && status < 300) {
    return { label: 'Success', severity: 'success' };
  } else if (status >= 400 && status < 500) {
    return { label: 'Client Error', severity: 'warning' };
  } else if (status >= 500) {
    return { label: 'Server Error', severity: 'danger' };
  }
  return { label: 'Unknown', severity: 'info' };
});

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

const updateCodeHeight = () => {
  if (codeContainerRef.value) {
    const preElement = codeContainerRef.value.querySelector('pre');
    if (preElement) {
      codeHeight.value = preElement.scrollHeight;
    }
  }
};

const initResizeObserver = () => {
  if (observer.value) {
    observer.value.disconnect();
  }

  observer.value = new ResizeObserver(() => {
    updateCodeHeight();
  });

  if (codeContainerRef.value) {
    observer.value.observe(codeContainerRef.value);
  }
};

onMounted(() => {
  initResizeObserver();
});

onBeforeUnmount(() => {
  if (observer.value) {
    observer.value.disconnect();
  }
});

// Update updateHighlightedCode to highlight only the response data
const updateHighlightedCode = async () => {
  if (props.response) {
    try {
      isLoading.value = true;
      const formatted = JSON.stringify(responseData.value, null, 2);
      highlightedCode.value = await highlightCode(formatted);
      
      await nextTick();
      
      // Use a hidden element to measure full content height
      const tempDiv = document.createElement('div');
      tempDiv.style.visibility = 'hidden';
      tempDiv.style.position = 'absolute';
      tempDiv.style.top = '0';
      tempDiv.style.left = '0';
      tempDiv.style.width = codeContainerRef.value ? codeContainerRef.value.offsetWidth + 'px' : 'auto';
      tempDiv.innerHTML = highlightedCode.value;
      document.body.appendChild(tempDiv);
      const preElement = tempDiv.querySelector('pre');
      if (preElement) {
        codeHeight.value = preElement.scrollHeight;
      }
      document.body.removeChild(tempDiv);
      
      // Also update codeContainer height in case ResizeObserver didn't catch it
      updateCodeHeight();
    } catch (error) {
      console.error('Failed to highlight code:', error);
    } finally {
      isLoading.value = false;
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
          <!-- Use dynamic Tag based on the endpoint response status -->
          <Tag v-if="responseStatus" :severity="statusTag.severity" :value="statusTag.label" />
          <Button
            icon="pi pi-copy"
            text
            rounded
            :class="{ '!text-green-500': copySuccess }"
            @click="copyToClipboard"
            v-tooltip.left="'Copy Response'"
          />
        </div>
        <!-- New section to display response status text -->
        <div v-if="responseStatus" class="mb-2">
          <strong>Status:</strong> <span>{{ responseStatus }}</span>
        </div>
        <div 
          class="bg-gray-50 rounded-xl p-6 shadow-lg border border-gray-200 relative"
          :class="{ 'code-collapsed': !isExpanded && showExpandButton }"
        >
          <div v-if="isLoading" class="space-y-2">
            <Skeleton height="20px" class="mb-2" />
            <Skeleton height="20px" width="90%" class="mb-2" />
            <Skeleton height="20px" width="95%" class="mb-2" />
            <Skeleton height="20px" width="85%" class="mb-2" />
            <Skeleton height="20px" width="92%" />
          </div>
          <div 
            v-else
            ref="codeContainerRef"
            class="code-container"
            :style="{ maxHeight: !isExpanded && showExpandButton ? MAX_HEIGHT + 'px' : 'none' }"
            v-html="highlightedCode"
          />
          <div 
            v-if="showExpandButton && !isLoading"
            class="absolute bottom-0 left-0 right-0 text-center pb-2 pt-16"
            :class="{ 'expand-overlay': !isExpanded }"
          >
            <Button
              :label="isExpanded ? 'Show Less' : 'Show More'"
              :icon="expandButtonIcon"
              :iconPos="isExpanded ? 'right' : 'right'"
              text
              @click="isExpanded = !isExpanded"
              class="bg-white shadow-sm transition-transform hover:bg-gray-50"
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
  background: linear-gradient(to bottom, rgba(255,255,255,0), rgb(255,255,255) 40%);
  pointer-events: none;
}

.expand-overlay .p-button {
  pointer-events: auto;
}

.rotate-icon :deep(.p-button-icon) {
  transition: transform 0.2s ease;
}

.rotate-icon.p-button:hover :deep(.p-button-icon) {
  transform: translateY(2px);
}

.p-skeleton {
  background-color: #e9ecef;
}
</style>

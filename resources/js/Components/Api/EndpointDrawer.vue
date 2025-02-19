<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Drawer from 'primevue/drawer';
import Button from 'primevue/button';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ProgressSpinner from 'primevue/progressspinner';
import EndpointHeader from './Endpoint/Header.vue';
import EndpointRequestForm from './Endpoint/RequestForm.vue';
import EndpointResponseView from './Endpoint/ResponseView.vue';

const props = defineProps({
  endpoint: {
    type: Object,
    required: true
  },
  visible: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:visible']);
const requestData = ref({});
const response = ref(null);
const loading = ref(false);
const error = ref(null);
const activeTab = ref(0);

const parseJsonSafely = (value) => {
  try {
    return value ? JSON.parse(value) : null;
  } catch (e) {
    return value;
  }
};

const page = usePage();
const apiId = page.props.api.id; // current API id from the show page

const getErrorMessage = (error) => {
  const status = error.response?.status;
  const message = error.response?.data?.message;

  switch (status) {
    case 429:
      return {
        status,
        message: message || 'Rate limit exceeded. Please try again later.',
        type: 'rate-limit'
      };
    case 403:
      return {
        status,
        message: message || 'This API is only accessible to paid users.',
        type: 'access-denied'
      };
    case 401:
      return {
        status,
        message: message || 'Your session has expired. Please login again.',
        type: 'unauthorized'
      };
    case 422:
      return {
        status,
        message: message || 'Invalid request parameters.',
        type: 'validation'
      };
    case 404:
      return {
        status,
        message: message || 'API endpoint not found.',
        type: 'not-found'
      };
    case 500:
      return {
        status,
        message: 'Internal server error occurred.',
        type: 'server-error'
      };
    default:
      return {
        status: status || 0,
        message: message || 'An unexpected error occurred.',
        type: 'unknown'
      };
  }
};

// Modify sendRequest method to call the backend route instead of the external API directly
const sendRequest = async () => {
  loading.value = true;
  error.value = null;
  response.value = null;

  try {
    const processedData = { ...requestData.value };
    if (props.endpoint.parameters) {
      props.endpoint.parameters.forEach(param => {
        if (param.type === 'json') {
          processedData[param.name] = parseJsonSafely(processedData[param.name]);
        }
      });
    }

    // Call our backend route with API id and endpoint id
    const result = await axios.post(route('api.call-endpoint', apiId), {
      endpoint_id: props.endpoint.id,
      data: processedData
    });

    response.value = result.data;
    activeTab.value = 1; // Switch to response tab
  } catch (err) {
    const errorDetails = getErrorMessage(err);
    
    // Set error response for ResponseView component
    response.value = {
      status: errorDetails.status,
      data: {
        message: errorDetails.message,
        error: errorDetails.type
      }
    };
    
    error.value = errorDetails.message;
    activeTab.value = 1; // Switch to response tab to show error
  } finally {
    loading.value = false;
  }
};

// Reset state when drawer visibility changes
watch(() => props.visible, (newValue) => {
  if (newValue) {
    // Reset when drawer opens
    response.value = null;
    error.value = null;
    activeTab.value = 0; // Select Request tab
  } else {
    // Reset when drawer closes
    requestData.value = {};
  }
});

const handleClose = (closeCallback) => {
  emit('update:visible', false);
  closeCallback?.();
};
</script>

<template>
  <Drawer
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    position="right"
    :modal="true"
    :closeOnEscape="true"
    class="!w-full md:!w-[700px] lg:!w-[900px]"
  >
    <template #container="{ closeCallback }">
      <div class="flex flex-col h-full">
        <!-- Header -->
        <EndpointHeader 
          :endpoint="endpoint" 
          @close="handleClose(closeCallback)" 
          class="shrink-0"
        />

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden border-y border-gray-100">
          <TabView v-model:activeIndex="activeTab" class="h-full">
            <TabPanel header="Request">
              <EndpointRequestForm 
                :endpoint="endpoint" 
                @update:data="requestData = $event" 
              />
            </TabPanel>
            <TabPanel header="Response">
              <EndpointResponseView 
                :response="response"
                :error="error"
              />
            </TabPanel>
          </TabView>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-100 bg-white/80 backdrop-blur-sm shrink-0 px-6 py-4 flex items-center justify-between">
          <div class="flex items-center gap-2 text-sm text-gray-500">
            <i class="pi pi-clock"></i>
            <span>{{ endpoint.type }} API</span>
          </div>
          <div class="flex items-center gap-3">
            <Button
              label="Cancel"
              text
              @click="handleClose(closeCallback)"
              :disabled="loading"
            />
            <Button
              label="Send Request"
              icon="pi pi-send"
              :loading="loading"
              @click="sendRequest"
            />
          </div>
        </div>

        <!-- Loading Overlay -->
        <div 
          v-if="loading" 
          class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-50"
        >
          <ProgressSpinner class="w-16 h-16" />
        </div>
      </div>
    </template>
  </Drawer>
</template>

<style scoped>
:deep(.p-drawer-content) {
  padding: 0;
  display: flex;
  flex-direction: column;
  border-left: 1px solid #f3f4f6;
}

:deep(.p-drawer) {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

:deep(.p-tabview) {
  height: 100%;
  display: flex;
  flex-direction: column;
}

:deep(.p-tabview-panels) {
  flex: 1;
  overflow: hidden;
}

:deep(.p-tabview-panel) {
  height: 100%;
}

:deep(.p-tabview-nav) {
  background-color: white;
  border-bottom: 1px solid #f3f4f6;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

:deep(.p-tabview-nav-link) {
  padding: 1rem 1.25rem;
}

:deep(.p-inputtext),
:deep(.p-inputtextarea) {
  border-color: #f3f4f6;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

:deep(.p-button) {
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

:deep(.p-tag) {
  border-radius: 0.5rem;
}

.transition-all {
  transition-property: all;
  transition-duration: 200ms;
}
</style>

<script setup lang="ts">
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

interface Props {
  apiId: string;
  refreshInterval?: number;
}

const props = withDefaults(defineProps<Props>(), {
  refreshInterval: 30
});

interface EndpointStat {
  endpoint_id: string;
  path: string;
  method: string;
  avgResponseTime: number;
  totalRequests: number;
  successRate: number;
}

const stats = ref({
  overall: {
    successRate: 0,
    avgResponseTime: 0,
    totalRequests: 0,
    previousSuccessRate: 0,
    previousAvgResponseTime: 0,
  },
  endpoints: [] as EndpointStat[]
});

const isLoading = ref(false);

const fetchStats = async () => {
  try {
    isLoading.value = true;
    const timeframe = 3600; // Last hour
    const response = await axios.get(`/api/stats/${props.apiId}`, {
      params: { timeframe }
    });
    
    stats.value.overall.previousSuccessRate = stats.value.overall.successRate;
    stats.value.overall.previousAvgResponseTime = stats.value.overall.avgResponseTime;
    
    stats.value = {
      overall: {
        ...stats.value.overall,
        ...response.data.overall,
      },
      endpoints: response.data.endpoints
    };
  } catch (error) {
    console.error('Failed to fetch API stats:', error);
  } finally {
    isLoading.value = false;
  }
};

const getSuccessRateTrend = () => {
  if (!stats.value.overall.previousSuccessRate) return 0;
  return ((stats.value.overall.successRate - stats.value.overall.previousSuccessRate) / stats.value.overall.previousSuccessRate) * 100;
};

const getResponseTimeTrend = () => {
  if (!stats.value.overall.previousAvgResponseTime) return 0;
  return stats.value.overall.avgResponseTime - stats.value.overall.previousAvgResponseTime;
};

const getTrendColor = (trend: number, isResponseTime = false) => {
  if (isResponseTime) {
    return trend <= 0 ? 'text-emerald-200' : 'text-red-200';
  }
  return trend >= 0 ? 'text-emerald-200' : 'text-red-200';
};

const getTrendArrow = (trend: number, isResponseTime = false) => {
  if (isResponseTime) {
    return trend <= 0 ? '↓' : '↑';
  }
  return trend >= 0 ? '↑' : '↓';
};

let refreshInterval: NodeJS.Timeout;

onMounted(() => {
  fetchStats();
  if (props.refreshInterval > 0) {
    refreshInterval = setInterval(fetchStats, props.refreshInterval * 1000);
  }
});

onBeforeUnmount(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>

<template>
  <Card class="shadow-lg">
    <template #title>
      <div class="flex items-center gap-2 mb-4">
        <i class="pi pi-chart-line text-indigo-500"></i>
        <span class="text-xl font-semibold">API Performance</span>
      </div>
    </template>
    
    <template #content>
      <div class="space-y-6">
        <!-- Overall Stats Grid -->
        <div class="grid grid-cols-1 gap-4">
          <!-- Success Rate Card -->
          <div class="relative bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl text-white overflow-hidden shadow-lg transform hover:scale-[1.02] transition-all duration-200">
            <div class="absolute top-0 left-0 w-3 h-3 bg-blue-300 rounded-full"></div>
            <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-800 rounded-full"></div>
            <div class="flex items-center gap-2 mb-2">
              <i class="pi pi-check-circle text-2xl text-blue-100"></i>
              <h4 class="text-sm font-medium text-blue-50">Success Rate</h4>
            </div>
            <p class="mt-2 text-3xl font-bold flex items-baseline">
              {{ stats.overall.successRate.toFixed(1) }}%
              <span v-if="getSuccessRateTrend() !== 0" 
                    :class="['text-sm ml-2', getTrendColor(getSuccessRateTrend())]">
                {{ getTrendArrow(getSuccessRateTrend()) }} 
                {{ Math.abs(getSuccessRateTrend()).toFixed(1) }}%
              </span>
            </p>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-400/20 rounded-full blur-xl"></div>
          </div>

          <!-- Average Response Time Card -->
          <div class="relative bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 rounded-xl text-white overflow-hidden shadow-lg transform hover:scale-[1.02] transition-all duration-200">
            <div class="absolute top-0 left-0 w-3 h-3 bg-emerald-300 rounded-full"></div>
            <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-800 rounded-full"></div>
            <div class="flex items-center gap-2 mb-2">
              <i class="pi pi-clock text-2xl text-emerald-100"></i>
              <h4 class="text-sm font-medium text-emerald-50">Avg Response Time</h4>
            </div>
            <p class="mt-2 text-3xl font-bold flex items-baseline">
              {{ Math.round(stats.overall.avgResponseTime) }}ms
              <span v-if="getResponseTimeTrend() !== 0" 
                    :class="['text-sm ml-2', getTrendColor(getResponseTimeTrend(), true)]">
                {{ getTrendArrow(getResponseTimeTrend(), true) }} 
                {{ Math.abs(getResponseTimeTrend()).toFixed(1) }}ms
              </span>
            </p>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-400/20 rounded-full blur-xl"></div>
          </div>

          <!-- Total Requests Card -->
          <div class="relative bg-gradient-to-br from-violet-500 to-violet-600 p-6 rounded-xl text-white overflow-hidden shadow-lg transform hover:scale-[1.02] transition-all duration-200">
            <div class="absolute top-0 left-0 w-3 h-3 bg-violet-300 rounded-full"></div>
            <div class="absolute bottom-0 right-0 w-3 h-3 bg-violet-800 rounded-full"></div>
            <div class="flex items-center gap-2 mb-2">
              <i class="pi pi-server text-2xl text-violet-100"></i>
              <h4 class="text-sm font-medium text-violet-50">Total Requests</h4>
            </div>
            <p class="mt-2 text-3xl font-bold flex items-baseline">
              {{ stats.overall.totalRequests.toLocaleString() }}
              <span class="text-sm ml-2 text-violet-200">Last Hour</span>
            </p>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-violet-400/20 rounded-full blur-xl"></div>
          </div>
        </div>

        <!-- Endpoint Stats Table -->
        <div v-if="stats.endpoints.length > 0" class="mt-8">
          <h3 class="text-lg font-semibold mb-4 text-gray-800">Endpoint Performance</h3>
          <DataTable :value="stats.endpoints" 
                    class="p-datatable-sm shadow-sm rounded-lg overflow-hidden border border-gray-200" 
                    responsiveLayout="scroll"
                    :paginator="true" 
                    :rows="5"
                    stripedRows
                    :loading="isLoading">
            <Column field="method" header="Method" :sortable="true">
              <template #body="{ data }">
                <span :class="{
                  'px-2 py-1 rounded text-xs font-medium': true,
                  'bg-green-100 text-green-800': data.method === 'GET',
                  'bg-blue-100 text-blue-800': data.method === 'POST',
                  'bg-yellow-100 text-yellow-800': data.method === 'PUT',
                  'bg-red-100 text-red-800': data.method === 'DELETE',
                  'bg-purple-100 text-purple-800': !['GET', 'POST', 'PUT', 'DELETE'].includes(data.method)
                }">
                  {{ data.method }}
                </span>
              </template>
            </Column>
            <Column field="path" header="Endpoint" :sortable="true">
              <template #body="{ data }">
                <div class="font-mono text-sm text-gray-600">{{ data.path }}</div>
              </template>
            </Column>
            <Column field="successRate" header="Success Rate" :sortable="true">
              <template #body="{ data }">
                <div class="flex items-center">
                  <div class="w-16 text-sm">{{ data.successRate.toFixed(1) }}%</div>
                  <div class="flex-1 h-2 bg-gray-100 rounded-full ml-2">
                    <div class="h-full rounded-full transition-all duration-300" 
                         :style="{ 
                           width: `${data.successRate}%`, 
                           backgroundColor: data.successRate > 90 ? '#22c55e' : data.successRate > 75 ? '#eab308' : '#ef4444' 
                         }">
                    </div>
                  </div>
                </div>
              </template>
            </Column>
            <Column field="avgResponseTime" header="Avg Response" :sortable="true">
              <template #body="{ data }">
                <div class="text-sm">{{ Math.round(data.avgResponseTime) }}ms</div>
              </template>
            </Column>
            <Column field="totalRequests" header="Requests" :sortable="true">
              <template #body="{ data }">
                <div class="text-sm">{{ data.totalRequests.toLocaleString() }}</div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
:deep(.p-card) {
  border-radius: 1rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  border-bottom-width: 2px;
  border-color: #e2e8f0;
  font-weight: 600;
  color: #475569;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 150ms ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:nth-child(even)) {
  background-color: #f8fafc;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f1f5f9;
}

:deep(.p-paginator) {
  background-color: transparent;
  border: none;
  padding: 1rem 0;
}
</style>

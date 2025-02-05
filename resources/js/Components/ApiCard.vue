<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ApiProps {
    name: string;
    description: string;
    type: "FREE" | "PAID";
    status: "ACTIVE" | "INACTIVE";
    endpointCount: number;
    createdAt: string;
    id: string;
    onActivate?: (id: string) => void;
}

const props = defineProps<ApiProps>();
const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

const truncateText = (text: string, length: number = 120) => {
    return text.length > length ? text.substring(0, length) + '...' : text;
};

const statusBadgeClass = computed(() => {
    if (props.status === 'INACTIVE') {
        return 'bg-gray-50 text-gray-600 border border-gray-200';
    }
    return props.type === 'PAID' 
        ? 'bg-amber-50 text-amber-700 border border-amber-200'
        : 'bg-emerald-50 text-emerald-700 border border-emerald-200';
});

const displayStatus = computed(() => 
    props.status === 'INACTIVE' ? 'INACTIVE' : props.type
);

const cardClass = computed(() => ({
    'opacity-75': props.status === 'INACTIVE'
}));

const handleActivate = () => {
    if (props.onActivate) {
        props.onActivate(props.id);
    }
};
</script>

<template>
    <div 
        class="relative flex flex-col min-h-[320px] bg-white border border-gray-100 rounded-xl transition-all duration-200 hover:shadow-lg hover:border-gray-200 hover:-translate-y-1"
        :class="cardClass"
    >
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-50">
            <h3 class="text-xl font-semibold text-gray-900 truncate pr-3 flex-1">
                {{ name }}
            </h3>
            <span 
                :class="[
                    'px-3 py-1 text-sm font-medium rounded-full shrink-0',
                    statusBadgeClass
                ]"
            >
                {{ displayStatus }}
            </span>
        </div>

        <!-- Content -->
        <div class="flex-1 p-6">
            <p class="text-gray-600 leading-relaxed mb-4">
                {{ truncateText(description) }}
            </p>
            <div class="flex justify-between items-center text-sm text-gray-500">
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full">
                    <i class="pi pi-link text-gray-400" />
                    <span class="font-medium">{{ endpointCount }} Endpoints</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="pi pi-calendar text-gray-400" />
                    <span>{{ new Date(createdAt).toLocaleDateString() }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-auto p-6 pt-4 border-t border-gray-50">
            <div class="flex items-center justify-between">
                <!-- Utility buttons -->
                <div class="flex gap-1">
                    <button
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-full transition-colors"
                        v-tooltip.top="'View Documentation'"
                    >
                        <i class="pi pi-info-circle" />
                    </button>
                    <button
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-full transition-colors"
                        v-tooltip.top="'Code Examples'"
                    >
                        <i class="pi pi-code" />
                    </button>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-2">
                    <template v-if="status === 'INACTIVE' && isAdmin">
                        <Button 
                            icon="pi pi-check"
                            label="Activate"
                            severity="success"
                            size="small"
                            @click="handleActivate"
                        />
                    </template>
                    <template v-else>
                        <Button 
                            :link="true"
                            :href="`/api/${id}/use`"
                            severity="primary"
                            size="small"
                            class="gap-2"
                            :disabled="status === 'INACTIVE'"
                        >
                            Use API
                            <i class="pi pi-arrow-right" />
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

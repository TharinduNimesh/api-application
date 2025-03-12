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
    'opacity-75': props.status === 'INACTIVE',
    'border-red-200 hover:border-red-300': props.status === 'INACTIVE',
    'border-emerald-100 hover:border-emerald-300': props.status === 'ACTIVE' && props.type === 'FREE',
    'border-amber-100 hover:border-amber-300': props.status === 'ACTIVE' && props.type === 'PAID',
}));

const handleActivate = () => {
    if (props.onActivate) {
        props.onActivate(props.id);
    }
};

const handleAction = (e: Event) => {
    // Prevent card click when clicking action buttons
    e.stopPropagation();
};

// Get appropriate icon colors for better visual distinction
const getIconColorClass = (iconType: string) => {
    if (props.status === 'INACTIVE') {
        return 'text-gray-400';
    }
    
    const colorMap: Record<string, string> = {
        link: props.type === 'FREE' ? 'text-emerald-500' : 'text-amber-500',
        calendar: 'text-indigo-400',
        info: 'text-blue-500',
        code: 'text-violet-500'
    };
    
    return colorMap[iconType] || 'text-gray-400';
};
</script>

<template>
    <Link 
        :href="route('api.show', props.id)"
        class="relative flex flex-col min-h-[320px] bg-white border rounded-xl transition-all duration-200 hover:shadow-lg hover:-translate-y-1 cursor-pointer"
        :class="cardClass"
        preserve-scroll
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
                    <i class="pi pi-link" :class="getIconColorClass('link')" />
                    <span class="font-medium">{{ endpointCount }} Endpoints</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="pi pi-calendar" :class="getIconColorClass('calendar')" />
                    <span>{{ new Date(createdAt).toLocaleDateString() }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-auto p-6 pt-4 border-t border-gray-50">
            <div class="flex items-center justify-end" @click="handleAction">
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
                        <Link 
                            :href="route('api.show', props.id)"
                            class="p-button p-button-primary p-button-sm gap-2"
                            :class="{ 'p-disabled': status === 'INACTIVE' }"
                            preserve-scroll
                            as="button"
                        >
                            Use API
                            <i class="pi pi-arrow-right" />
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </Link>
</template>

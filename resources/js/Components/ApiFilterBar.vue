<script setup lang="ts">
import { ref } from 'vue';
import ToggleButton from 'primevue/togglebutton';

interface Props {
    modelValue: {
        search: string;
        type: 'ALL' | 'FREE' | 'PAID';
        sort: string;
    };
}

interface Emits {
    (e: 'update:modelValue', value: Props['modelValue']): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({
        search: '',
        type: 'ALL',
        sort: 'name'
    })
});

const emit = defineEmits<Emits>();

const sortOptions = [
    { label: 'Name (A-Z)', value: 'name', icon: 'pi pi-sort-alpha-down' },
    { label: 'Name (Z-A)', value: '-name', icon: 'pi pi-sort-alpha-up' },
    { label: 'Newest First', value: '-createdAt', icon: 'pi pi-calendar' },
    { label: 'Oldest First', value: 'createdAt', icon: 'pi pi-calendar-times' }
];

const updateFilter = (key: keyof Props['modelValue'], value: any) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value
    });
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Search Bar -->
        <div class="p-4 lg:p-6">
            <InputGroup class="w-full">
                <InputGroupAddon>
                    <i class="pi pi-search text-gray-400"></i>
                </InputGroupAddon>
                <InputText 
                    :model-value="modelValue.search"
                    @update:model-value="val => updateFilter('search', val)"
                    placeholder="Search APIs..." 
                    class="w-full"
                />
            </InputGroup>
        </div>

        <!-- Filters -->
        <div class="px-4 lg:px-6 py-3 bg-gray-50/50 flex flex-wrap items-center gap-4">
            <!-- Type Filters -->
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 font-medium">Show:</span>
                <div class="flex gap-2 p-1 bg-gray-100 rounded-lg">
                    <button
                        v-for="type in ['ALL', 'FREE', 'PAID']"
                        :key="type"
                        @click="updateFilter('type', type)"
                        :class="[
                            'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                            modelValue.type === type
                                ? 'bg-white shadow text-gray-800'
                                : 'text-gray-500 hover:text-gray-800'
                        ]"
                    >
                        <span v-if="type === 'ALL'">All APIs</span>
                        <div v-else class="flex items-center gap-1.5">
                            <i :class="[
                                'pi',
                                type === 'PAID' ? 'pi-star-fill text-amber-500' : 'pi-star text-emerald-500'
                            ]" />
                            {{ type }}
                        </div>
                    </button>
                </div>
            </div>

            <div class="flex-1" />

            <!-- Sort Options -->
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 font-medium">Sort by:</span>
                <Dropdown
                    :model-value="modelValue.sort"
                    @update:model-value="val => updateFilter('sort', val)"
                    :options="sortOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-[200px]"
                >
                    <template #option="{ option }">
                        <div class="flex items-center gap-2">
                            <i :class="option.icon" />
                            {{ option.label }}
                        </div>
                    </template>
                    <template #value="{ value }">
                        <div class="flex items-center gap-2">
                            <i :class="sortOptions.find(opt => opt.value === value)?.icon" />
                            {{ sortOptions.find(opt => opt.value === value)?.label }}
                        </div>
                    </template>
                </Dropdown>
            </div>
        </div>
    </div>
</template>

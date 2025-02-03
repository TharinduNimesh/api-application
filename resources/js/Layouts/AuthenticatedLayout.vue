<script setup lang="ts">
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import { Link } from "@inertiajs/vue3";
import AppHeader from '@/Components/Layout/AppHeader.vue';

const showingNavigationDropdown = ref(false);
const menu = ref();
const items = [
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => navigateTo(route('profile.edit'))
    },
    {
        label: 'Logout',
        icon: 'pi pi-power-off',
        command: () => {
            // Implement logout logic
        }
    }
];

const navigateTo = (path: string) => {
    // Implement navigation
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <AppHeader />

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Add any custom styles here */
</style>

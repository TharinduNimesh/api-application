<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, router } from "@inertiajs/vue3";
import Avatar from "primevue/avatar";
import Menu from "primevue/menu";

const menu = ref();
const menuItems = ref([
    {
        label: "Profile",
        icon: "pi pi-user",
        command: () => router.visit(route("profile.edit")),
    },
    {
        separator: true,
    },
    {
        label: "Logout",
        icon: "pi pi-power-off",
        command: () => router.post(route("logout")),
    },
]);

const getUserInitials = (name: string) => {
    return name
        .split(" ")
        .map((word) => word[0])
        .join("")
        .toUpperCase();
};

const isScrolled = ref(false);

// Create a named function for the event listener
const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="fixed top-0 left-0 right-0 z-50 px-4 py-4">
        <nav 
            class="mx-auto max-w-7xl transition-all duration-300"
            :class="[
                isScrolled ? 'mt-2 rounded-xl shadow-lg' : 'mt-4 rounded-2xl',
            ]"
        >
            <div class="relative overflow-hidden rounded-xl">
                <!-- Blur Background -->
                <div 
                    class="absolute inset-0 backdrop-blur-xl bg-white/75"
                    :class="{ 'bg-white/85': isScrolled }"
                ></div>

                <!-- Content -->
                <div class="relative px-4 sm:px-6 lg:px-8">
                    <div class="flex h-14 justify-between items-center">
                        <!-- Logo and App Name -->
                        <Link :href="route('dashboard')" class="flex items-center">
                            <div class="flex items-center gap-3">
                                <ApplicationLogo class="h-8 w-auto" />
                                <span class="text-xl font-semibold text-gray-800">
                                    <span class="text-orange-400 float-left">API</span>
                                    Forge
                                </span>
                            </div>
                        </Link>

                        <!-- Avatar and Menu -->
                        <div class="flex items-center">
                            <Avatar
                                :label="getUserInitials($page.props.auth.user.name)"
                                shape="circle"
                                class="cursor-pointer hover:scale-105 transition-transform"
                                @click="menu.toggle($event)"
                                style="background: #fb923c; color: white;"
                            />
                            <Menu ref="menu" :model="menuItems" :popup="true" />
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Spacer to prevent content from going under fixed header -->
    <div class="h-24"></div>
</template>
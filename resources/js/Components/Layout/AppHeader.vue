<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
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

const navigationItems = computed(() => [
  {
    label: "Dashboard",
    icon: "pi pi-home",
    route: "dashboard",
  },
  {
    label: "Users",
    icon: "pi pi-users",
    route: "users.index",
  },
]);

const isCurrentRoute = (routeName: string) => {
  return route().current(routeName);
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
  <div class="fixed top-0 left-0 right-0 z-50 px-4 py-4">
    <nav
      class="mx-auto max-w-7xl transition-all duration-300"
      :class="[isScrolled ? 'mt-2 rounded-xl shadow-lg' : 'mt-4 rounded-2xl']"
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

            <!-- Custom Navigation Menu -->
            <div
              class="flex-grow mx-8"
              v-if="$page.props.auth.user.role === 'admin'"
            >
              <ul class="flex items-center gap-2">
                <li v-for="item in navigationItems" :key="item.route">
                  <Link
                    :href="route(item.route)"
                    class="flex items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-orange-50 hover:text-orange-500 transition-colors relative"
                    :class="{
                      'text-orange-500 bg-orange-50': isCurrentRoute(
                        item.route
                      ),
                    }"
                  >
                    <i :class="[item.icon, 'mr-2']"></i>
                    {{ item.label }}
                    <div
                      v-if="isCurrentRoute(item.route)"
                      class="absolute bottom-0 left-0 w-full h-0.5 bg-orange-400 rounded-full"
                    ></div>
                  </Link>
                </li>
              </ul>
            </div>

            <!-- Avatar and Menu -->
            <div class="flex items-center">
              <Avatar
                :label="getUserInitials($page.props.auth.user.name)"
                shape="circle"
                class="cursor-pointer hover:scale-105 transition-transform"
                @click="menu.toggle($event)"
                style="background: #fb923c; color: white"
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

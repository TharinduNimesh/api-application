<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import Avatar from "primevue/avatar";
import Menu from "primevue/menu";

const menu = ref();
const sidebarMenu = ref();

// Update menuItems to use label for desktop menu
const menuItems = ref([
  {
    items: [
      {
        label: "Profile Settings",
        icon: "pi pi-user",
        command: () => router.visit(route("profile.edit")),
      },
      {
        separator: true
      },
      {
        label: "Logout",
        icon: "pi pi-power-off",
        class: "text-red-500",
        command: () => router.post(route("logout")),
      }
    ]
  }
]);

// Update the sidebarMenuItems type
interface MenuItem {
  header?: string;
  label?: string;
  icon?: string;
  class?: string;
  command?: () => void;
  separator?: boolean;
}

// Add a separate menuItems for mobile sidebar
const sidebarMenuItems = computed<MenuItem[]>(() => [
  {
    header: "Account Settings",
    class: "text-gray-500 text-xs uppercase font-semibold mb-2",
  },
  {
    label: "Profile Settings",
    icon: "pi pi-user",
    command: () => router.visit(route("profile.edit")),
  },
  {
    label: "Logout",
    icon: "pi pi-power-off",
    class: "text-red-500",
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

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
  if (isMobileMenuOpen.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
};

// Close mobile menu on route change
router.on('navigate', () => {
  isMobileMenuOpen.value = false;
});

onMounted(() => {
  window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll);
  document.body.style.overflow = '';
});
</script>

<template>
  <div class="fixed top-0 left-0 right-0 z-50 px-2 sm:px-4 py-4">
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
        <div class="relative px-3 sm:px-6 lg:px-8">
          <div class="flex h-14 justify-between items-center">
            <!-- Logo and App Name -->
            <Link :href="route('dashboard')" class="flex items-center">
              <div class="flex items-center gap-2 sm:gap-3">
                <ApplicationLogo class="h-6 w-auto sm:h-8" />
                <span class="text-lg sm:text-xl font-semibold text-gray-800">
                  <span class="text-orange-400 float-left">API</span>
                  Forge
                </span>
              </div>
            </Link>

            <!-- Desktop Navigation Menu -->
            <div
              class="hidden md:flex flex-grow mx-8"
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

            <!-- Mobile Menu Button -->
            <button
              v-if="$page.props.auth.user.role === 'admin'"
              class="md:hidden p-2 rounded-lg hover:bg-orange-50"
              @click="toggleMobileMenu"
            >
              <i class="pi pi-bars text-gray-600"></i>
            </button>

            <!-- Avatar and Menu (Desktop Only) -->
            <div class="hidden md:flex items-center">
              <Avatar
                :label="getUserInitials($page.props.auth.user.name)"
                shape="circle"
                class="cursor-pointer hover:scale-105 transition-transform"
                @click="menu.toggle($event)"
                style="background: #fb923c; color: white"
              />
              <Menu 
                ref="menu" 
                :model="menuItems" 
                :popup="true"
                :pt="{
                  root: { class: 'surface-card p-0' },
                  menu: { class: 'border border-gray-200 p-0' },
                  content: { class: 'p-0' },
                  list: { class: 'p-0' }
                }"
              />
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Enhanced Mobile Navigation Overlay -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isMobileMenuOpen"
        class="fixed inset-0 backdrop-blur-sm bg-white/50 z-40 md:hidden"
        @click="toggleMobileMenu"
      >
        <Transition
          enter-active-class="transition ease-out duration-300 transform"
          enter-from-class="translate-x-full"
          enter-to-class="translate-x-0"
          leave-active-class="transition ease-in duration-200 transform"
          leave-from-class="translate-x-0"
          leave-to-class="translate-x-full"
        >
          <div
            v-if="isMobileMenuOpen"
            class="absolute right-0 top-0 h-full w-72 bg-white/95 backdrop-blur-xl shadow-xl"
            @click.stop
          >
            <!-- User Profile Section -->
            <div class="p-6 bg-orange-50 border-b border-orange-100">
              <div class="flex items-center gap-3">
                <Avatar
                  :label="getUserInitials($page.props.auth.user.name)"
                  shape="circle"
                  size="large"
                  style="background: #fb923c; color: white; width: 3rem; height: 3rem; min-width: 3rem"
                  class="flex items-center justify-center text-base"
                />
                <div class="min-w-0">
                  <h3 class="font-semibold text-gray-800 truncate">
                    {{ $page.props.auth.user.name }}
                  </h3>
                  <p class="text-sm text-gray-500 truncate" :title="$page.props.auth.user.email">
                    {{ $page.props.auth.user.email }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Close button -->
            <button
              @click="toggleMobileMenu"
              class="absolute top-4 right-4 p-2 rounded-lg hover:bg-orange-50 text-gray-500"
            >
              <i class="pi pi-times"></i>
            </button>

            <!-- Navigation Links -->
            <div class="p-4">
              <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">
                Navigation
              </h4>
              <ul class="space-y-2">
                <li v-for="item in navigationItems" :key="item.route">
                  <Link
                    :href="route(item.route)"
                    class="flex items-center px-4 py-3 rounded-lg text-gray-600 hover:bg-orange-50 hover:text-orange-500 transition-colors"
                    :class="{
                      'text-orange-500 bg-orange-50': isCurrentRoute(item.route),
                    }"
                  >
                    <i :class="[item.icon, 'mr-3']"></i>
                    {{ item.label }}
                  </Link>
                </li>
              </ul>
            </div>

            <!-- Account Settings in mobile sidebar -->
            <div class="p-4 border-t">
              <template v-for="(item, index) in sidebarMenuItems" :key="index">
                <h4 v-if="item.header" :class="item.class">
                  {{ item.header }}
                </h4>
                <Link
                  v-else-if="!item.separator"
                  :href="item.command ? '#' : ''"
                  @click="item.command"
                  class="flex items-center px-4 py-3 rounded-lg text-gray-600 hover:bg-orange-50 hover:text-orange-500 transition-colors"
                  :class="item.class"
                >
                  <i :class="[item.icon, 'mr-3']"></i>
                  {{ item.label }}
                </Link>
              </template>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </div>

  <!-- Spacer to prevent content from going under fixed header -->
  <div class="h-24"></div>
</template>
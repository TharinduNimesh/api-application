<script setup lang="ts">
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Head, Link } from "@inertiajs/vue3";
import Button from "primevue/button";

defineProps<{
  canLogin: boolean;
  canRegister: boolean;
}>();
</script>

<template>
  <Head title="Welcome to APIForge" />

  <div class="bg-gray-50 min-h-screen relative overflow-hidden">
    <!-- Background Blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <!-- Blob 1 -->
      <div
        class="absolute -top-[20%] -left-[10%] w-[45%] h-[45%] rounded-full bg-gradient-to-br from-orange-200/30 to-orange-500/30 blur-[80px] animate-blob"
      ></div>
      <!-- Blob 2 -->
      <div
        class="absolute top-[30%] -right-[15%] w-[50%] h-[50%] rounded-full bg-gradient-to-bl from-orange-300/20 to-pink-500/20 blur-[90px] animate-blob animation-delay-2000"
      ></div>
      <!-- Blob 3 -->
      <div
        class="absolute -bottom-[10%] left-[20%] w-[40%] h-[40%] rounded-full bg-gradient-to-tr from-blue-200/20 to-orange-300/20 blur-[70px] animate-blob animation-delay-4000"
      ></div>
    </div>

    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-sm shadow-sm sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <!-- Logo -->
          <div class="flex-shrink-0 flex items-center gap-3">
            <ApplicationLogo class="h-8 w-auto" />
            <span class="text-xl font-semibold text-gray-800">
              <span class="text-orange-400 float-left">API</span>
              Forge
            </span>
          </div>

          <!-- Navigation Links -->
          <div v-if="canLogin" class="flex items-center gap-4">
            <Link
              :href="route('login')"
              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              Log in
            </Link>

            <Link v-if="canRegister" :href="route('register')">
              <Button label="Get Started" severity="primary" />
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative pt-20 pb-32">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center relative z-10">
          <div class="mb-8 flex justify-center">
            <div
              class="relative rounded-full px-3 py-1 text-sm leading-6 text-gray-600 ring-1 ring-gray-900/10 hover:ring-gray-900/20"
            >
              <span class="font-semibold text-orange-600">New</span> Access powerful APIs for your applications
            </div>
          </div>
          <h1
            class="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl mb-8"
          >
            Access Powerful APIs
            <span class="relative">
              <span
                class="relative bg-gradient-to-r from-orange-600 to-pink-600 bg-clip-text text-transparent"
                >Made Simple</span
              >
              <span
                class="absolute -bottom-1.5 left-0 w-full h-1 bg-gradient-to-r from-orange-400 to-pink-400 rounded-full blur-sm opacity-70"
              ></span>
            </span>
          </h1>
          <p
            class="mt-3 max-w-md mx-auto text-base text-gray-600 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl"
          >
            Discover and integrate APIs for your applications. Find everything you need to build powerful, connected experiences.
          </p>
          <div class="mt-10 flex justify-center gap-x-6">
            <Link
              :href="route('register')"
              class="transform transition hover:scale-105"
            >
              <Button
                label="Get Started"
                severity="primary"
                size="large"
                class="px-8 py-3 shadow-xl shadow-orange-500/20"
              />
            </Link>
            <Link
              :href="route('login')"
              class="transform transition hover:scale-105"
            >
              <Button
                label="Browse APIs"
                severity="secondary"
                size="large"
                class="px-8 py-3"
                text
              />
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Grid -->
    <div class="bg-white/80 backdrop-blur-sm py-24 relative z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900">
            Everything you need to manage your APIs
          </h2>
          <p class="mt-4 text-lg text-gray-600">
            Comprehensive tools for API management and monitoring
          </p>
        </div>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
          <!-- Feature cards with enhanced styling -->
          <div
            class="bg-white/60 backdrop-blur rounded-xl p-8 shadow-lg shadow-gray-100/50 hover:shadow-xl transition duration-300"
          >
            <div
              class="flex items-center justify-center h-12 w-12 rounded-md bg-orange-500 text-white mb-4"
            >
              <i class="pi pi-shield text-xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              Rate Limiting
            </h3>
            <p class="text-gray-500">
              Protect your APIs with advanced rate limiting and usage
              monitoring.
            </p>
          </div>

          <div
            class="bg-white/60 backdrop-blur rounded-xl p-8 shadow-lg shadow-gray-100/50 hover:shadow-xl transition duration-300"
          >
            <div
              class="flex items-center justify-center h-12 w-12 rounded-md bg-orange-500 text-white mb-4"
            >
              <i class="pi pi-users text-xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              User Management
            </h3>
            <p class="text-gray-500">
              Manage access levels and permissions with role-based
              authentication.
            </p>
          </div>

          <div
            class="bg-white/60 backdrop-blur rounded-xl p-8 shadow-lg shadow-gray-100/50 hover:shadow-xl transition duration-300"
          >
            <div
              class="flex items-center justify-center h-12 w-12 rounded-md bg-orange-500 text-white mb-4"
            >
              <i class="pi pi-chart-line text-xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Analytics</h3>
            <p class="text-gray-500">
              Track API usage and performance with real-time analytics.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with enhanced styling -->
    <footer
      class="bg-white/80 backdrop-blur-sm border-t border-gray-200 relative z-10"
    >
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center text-gray-500 text-sm">
          © {{ new Date().getFullYear() }} APIForge. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}
</style>

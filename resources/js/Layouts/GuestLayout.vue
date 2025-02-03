<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import BlobBackground from '@/Components/Background/BlobBackground.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 relative">
        <BlobBackground />
        
        <div class="relative z-10">
            <Link href="/" class="flex items-center gap-3">
                <ApplicationLogo class="w-20 h-20" />
                <span class="text-2xl font-bold text-gray-800">
                    <span class="text-orange-400">API</span>Forge
                </span>
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/70 backdrop-blur-xl shadow-md overflow-hidden sm:rounded-lg relative z-10">
            <slot />
        </div>
    </div>
</template>

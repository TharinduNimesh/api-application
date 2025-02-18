<script setup lang="ts">
import AppHeader from '@/Components/Layout/AppHeader.vue';
import BlobBackground from '@/Components/Background/BlobBackground.vue';
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 relative">
            <BlobBackground />
            <div class="relative z-10">
                <AppHeader />

                <!-- Page Content -->
                <main class="p-5 sm:p-0">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

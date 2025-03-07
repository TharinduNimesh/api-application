<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';

defineProps<{
    status?: number;
    message?: string;
}>();

const handleSignOut = () => {
    router.post(route('logout'), {}, {
        onSuccess: () => {
            window.location.href = route('login');
        }
    });
};
</script>

<template>
    <Head title="404: Not Found" />

    <main class="min-h-screen bg-gradient-to-br from-orange-50/80 to-white relative overflow-hidden">
        <!-- Background Patterns -->
        <div class="absolute inset-0">
            <!-- Top Pattern -->
            <div class="absolute top-0 left-0 right-0 h-40 bg-[radial-gradient(circle_at_1px_1px,rgba(237,137,54,0.12)_1px,transparent_0)] bg-[size:40px_40px]"></div>
            <!-- Bottom Pattern -->
            <div class="absolute bottom-0 left-0 right-0 h-40 bg-[radial-gradient(circle_at_1px_1px,rgba(237,137,54,0.12)_1px,transparent_0)] bg-[size:40px_40px] rotate-180"></div>
        </div>

        <!-- Decorative Planets -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Large Planet -->
            <div class="absolute -right-20 top-20">
                <img 
                    src="/images/404/planet-1.png" 
                    alt="Planet 1"
                    class="w-[300px] h-[300px] object-contain opacity-30 animate-float-delayed"
                />
            </div>
            <!-- Medium Planet -->
            <div class="absolute -left-10 bottom-40">
                <img 
                    src="/images/404/planet-2.png" 
                    alt="Planet 2"
                    class="w-[200px] h-[200px] object-contain opacity-20 animate-float"
                />
            </div>
            <!-- Small Planet -->
            <div class="absolute right-40 bottom-20">
                <img 
                    src="/images/404/planet-3.png" 
                    alt="Planet 3"
                    class="w-[100px] h-[100px] object-contain opacity-25 animate-float-delayed-2"
                />
            </div>
            <!-- Extra Small Planet -->
            <div class="absolute left-1/4 top-32">
                <img 
                    src="/images/404/planet-4.png" 
                    alt="Planet 4"
                    class="w-[150px] h-[80px] object-contain opacity-20 animate-float"
                />
            </div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 flex min-h-screen items-center justify-center px-6">
            <div class="w-full max-w-4xl">
                <!-- Back Link -->
                <Link 
                    :href="route('dashboard')"
                    class="fixed top-8 left-8 inline-flex items-center gap-2 text-gray-600 hover:text-orange-600 transition-colors"
                >
                    <i class="pi pi-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </Link>

                <div class="flex flex-col items-center text-center">
                    <!-- Main Image -->
                    <div class="relative w-full max-w-2xl">
                        <img 
                            src="/images/404/astronaut.png" 
                            alt="Page Not Found" 
                            class="relative mx-auto h-80 w-auto animate-float mix-blend-multiply"
                        />
                    </div>

                    <!-- Error Content -->
                    <div class="space-y-8">
                        <div class="relative">
                            <span class="absolute -left-4 -top-4 text-[180px] font-bold text-orange-600/5">404</span>
                            <h1 class="relative text-6xl font-bold text-orange-600">404</h1>
                        </div>

                        <div class="space-y-3">
                            <h2 class="text-2xl font-semibold text-gray-900">Page Not Found</h2>
                            <p class="mx-auto max-w-lg text-lg text-gray-600">
                                {{ message || "Sorry, we couldn't find the page you're looking for. It seems to have floated away into space." }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link :href="route('dashboard')">
                                <Button 
                                    label="Back to Dashboard" 
                                    icon="pi pi-home"
                                    severity="warning"
                                    class="px-6"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float 8s ease-in-out 1s infinite;
}

.animate-float-delayed-2 {
    animation: float 7s ease-in-out 2s infinite;
}

.animate-spin-slow {
    animation: spin 20s linear infinite;
}

.animate-spin-slow-reverse {
    animation: spin 25s linear infinite reverse;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.star-small {
    height: 0.25rem;
    width: 0.25rem;
    border-radius: 9999px;
    background-color: white;
    box-shadow: 0 0 2px 1px rgba(255, 255, 255, 0.5);
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

:deep(.p-button.p-button-primary) {
    background: #6366f1;
    border-color: #6366f1;
}

:deep(.p-button.p-button-primary:hover) {
    background: #4f46e5;
    border-color: #4f46e5;
}

:deep(.p-button.p-button-warning) {
    background: #f97316;
    border-color: #f97316;
}

:deep(.p-button.p-button-warning:hover) {
    background: #ea580c;
    border-color: #ea580c;
}

:deep(.p-button.p-button-text) {
    color: #4b5563;
}

:deep(.p-button.p-button-text:hover) {
    color: #f97316;
    background: #fff7ed;
}
</style>
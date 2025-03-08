<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-gray-800">Verify Your Email</h1>
            <p class="text-gray-500 mt-2 text-sm">One more step to get started</p>
        </div>

        <div class="mb-6 text-base leading-relaxed text-gray-600">
            Thanks for signing up! Before getting started, could you verify your
            email address by clicking on the link we just emailed to you? If you
            didn't receive the email, we will gladly send you another.
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-6 p-4 rounded-lg border border-green-100 bg-green-50"
        >
            <p class="text-sm text-green-700 font-medium flex items-center">
                <i class="pi pi-check-circle mr-2"></i>
                A new verification link has been sent to your email address.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="flex flex-col gap-3">
                <Button
                    type="submit"
                    :loading="form.processing"
                    class="w-full"
                    severity="primary"
                    label="Resend Verification Email"
                    icon="pi pi-envelope"
                />

                <Button
                    :href="route('logout')"
                    method="post"
                    severity="secondary"
                    text
                    class="w-full"
                    label="Log Out"
                    icon="pi pi-sign-out"
                />
            </div>
        </form>
    </GuestLayout>
</template>

<style lang="scss" scoped>
:deep(.p-button.p-button-secondary.p-button-text) {
    color: #64748b;
    
    &:hover {
        color: #f97316;
        background: transparent;
    }
}
</style>

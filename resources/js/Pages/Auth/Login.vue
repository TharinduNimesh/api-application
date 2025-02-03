<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Welcome Back</h1>
            <p class="text-gray-600 mt-2">Sign in to continue to APIForge</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="flex flex-col gap-2">
                <label for="email" class="font-medium text-gray-700">Email</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-envelope"></i>
                    </InputGroupAddon>
                    <InputText 
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.email }"
                        placeholder="Enter your email"
                        required
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.email }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="password" class="font-medium text-gray-700">Password</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-lock"></i>
                    </InputGroupAddon>
                    <Password 
                        v-model="form.password"
                        toggleMask
                        :feedback="false"
                        placeholder="Enter your password"
                        :class="{ 'p-invalid': form.errors.password }"
                        inputClass="w-full"
                        required
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.password }}</small>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Checkbox v-model="form.remember" :binary="true" class="mr-2" />
                    <label class="text-sm text-gray-600">Remember me</label>
                </div>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-orange-600 hover:text-orange-500"
                >
                    Forgot password?
                </Link>
            </div>

            <Button 
                type="submit"
                label="Sign In"
                class="w-full"
                :loading="form.processing"
            />

            <div class="text-center">
                <Link
                    :href="route('register')"
                    class="text-sm text-gray-600 hover:text-orange-500"
                >
                    Don't have an account? Sign up
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>

<style lang="scss" scoped>
:deep(.p-inputgroup) {
    .p-inputgroup-addon {
        background: transparent;
        border-right: none;
        color: #64748b;
    }
    
    .p-inputtext, .p-password {
        &:not(.p-invalid) {
            border-left: none;
        }
    }

    .p-password {
        width: 100%;
        
        input {
            border-left: none;
            width: 100%;
        }
    }
}
</style>

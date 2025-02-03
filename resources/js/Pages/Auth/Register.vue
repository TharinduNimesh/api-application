<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Create Account</h1>
            <p class="text-gray-600 mt-2">Get started with APIForge</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="flex flex-col gap-2">
                <label for="name" class="font-medium text-gray-700">Full Name</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-user"></i>
                    </InputGroupAddon>
                    <InputText 
                        id="name"
                        v-model="form.name"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.name }"
                        placeholder="Enter your name"
                        required
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.name }}</small>
            </div>

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
                        placeholder="Create a password"
                        :class="{ 'p-invalid': form.errors.password }"
                        required
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.password }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="password_confirmation" class="font-medium text-gray-700">Confirm Password</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-lock"></i>
                    </InputGroupAddon>
                    <Password 
                        v-model="form.password_confirmation"
                        toggleMask
                        :feedback="false"
                        placeholder="Confirm your password"
                        required
                    />
                </InputGroup>
            </div>

            <Button 
                type="submit"
                label="Create Account"
                class="w-full"
                :loading="form.processing"
            />

            <div class="text-center">
                <Link
                    :href="route('login')"
                    class="text-sm text-gray-600 hover:text-orange-500"
                >
                    Already have an account? Sign in
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

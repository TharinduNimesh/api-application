<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Password from 'primevue/password';
import Button from 'primevue/button';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Password</h2>
            <p class="mt-1 text-sm text-gray-600">Ensure your account is using a long, random password to stay secure.</p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div class="flex flex-col gap-2">
                <label for="current_password" class="font-medium text-gray-700">Current Password</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-lock"></i>
                    </InputGroupAddon>
                    <Password
                        v-model="form.current_password"
                        toggleMask
                        :feedback="false"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.current_password }"
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.current_password }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="password" class="font-medium text-gray-700">New Password</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-lock"></i>
                    </InputGroupAddon>
                    <Password
                        v-model="form.password"
                        toggleMask
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.password }"
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
                        class="w-full"
                    />
                </InputGroup>
            </div>

            <div class="flex items-center gap-4">
                <Button 
                    type="submit"
                    label="Save"
                    :loading="form.processing"
                />

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<style lang="scss" scoped>
:deep(.p-inputgroup) {
    .p-inputgroup-addon {
        background: transparent;
        border-right: none;
        color: #64748b;
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

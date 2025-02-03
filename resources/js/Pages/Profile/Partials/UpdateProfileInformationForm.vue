<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
            <p class="mt-1 text-sm text-gray-600">Update your account's profile information and email address.</p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div class="flex flex-col gap-2">
                <label for="name" class="font-medium text-gray-700">Name</label>
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-user"></i>
                    </InputGroupAddon>
                    <InputText
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.name }"
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
                        required
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.email }}</small>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-orange-600 hover:text-orange-500 underline"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    A new verification link has been sent to your email address.
                </div>
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
    
    .p-inputtext {
        &:not(.p-invalid) {
            border-left: none;
        }
    }
}
</style>

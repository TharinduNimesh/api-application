<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};

const visible = ref(false);
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">Delete Account</h2>
            <p class="mt-1 text-sm text-gray-600">
                Once your account is deleted, all of its resources and data will be permanently deleted.
            </p>
        </header>

        <Button 
            severity="danger"
            label="Delete Account"
            @click="visible = true"
        />

        <Dialog 
            v-model:visible="visible"
            modal
            header="Delete Account"
            :style="{ width: '450px' }"
        >
            <p class="mt-1 text-sm text-gray-600">
                Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will
                be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
            </p>

            <div class="mt-6">
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-lock"></i>
                    </InputGroupAddon>
                    <Password
                        v-model="form.password"
                        toggleMask
                        :feedback="false"
                        placeholder="Password"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.password }"
                        @keyup.enter="deleteUser"
                    />
                </InputGroup>
                <small class="text-red-600">{{ form.errors.password }}</small>
            </div>

            <template #footer>
                <Button 
                    label="Cancel" 
                    @click="closeModal" 
                    class="p-button-text"
                />
                <Button 
                    severity="danger" 
                    label="Delete Account"
                    :loading="form.processing"
                    @click="deleteUser"
                />
            </template>
        </Dialog>
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

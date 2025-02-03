<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head, router } from "@inertiajs/vue3";


defineProps<{
  mustVerifyEmail?: boolean;
  status?: string;
}>();
</script>

<template>
  <Head title="Profile" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-5">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              text
              rounded
              @click="router.visit(route('dashboard'))"
              class="p-0"
              severity="contrast"
            />
            <h2 class="text-2xl font-semibold text-gray-800">
              Profile Settings
            </h2>
          </div>
        </div>

        <div class="bg-white p-6 shadow-sm rounded-xl">
          <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
            class="max-w-xl"
          />
        </div>

        <div class="bg-white p-6 shadow-sm rounded-xl">
          <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="bg-white p-6 shadow-sm rounded-xl">
          <DeleteUserForm class="max-w-xl" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

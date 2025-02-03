<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ApiCard from "@/Components/ApiCard.vue";
import { ref, computed } from "vue";
import ApiFilterBar from "@/Components/ApiFilterBar.vue";
import Paginator from "primevue/paginator";
import { Link, usePage } from "@inertiajs/vue3";

// Add these types at the top
interface ApiFilters {
  search: string;
  type: "ALL" | "FREE" | "PAID";
  sort: string;
}

interface Api {
  id: string;
  name: string;
  description: string;
  type: "FREE" | "PAID";
  endpointCount: number;
  createdAt: string;
  [key: string]: string | number; // Add index signature
}

// Cast apis array to proper type
const apis = ref<Api[]>([
  {
    id: "1",
    name: "User Management API",
    description:
      "Complete user management system with authentication and authorization.",
    type: "PAID" as const,
    endpointCount: 12,
    createdAt: "2023-08-15",
  },
  {
    id: "2",
    name: "Weather API",
    description: "Real-time weather data for locations worldwide.",
    type: "FREE" as const,
    endpointCount: 5,
    createdAt: "2023-08-10",
  },
  {
    id: "3",
    name: "Payment Gateway API",
    description:
      "Secure payment processing with support for multiple payment providers and currencies.",
    type: "PAID" as const,
    endpointCount: 18,
    createdAt: "2023-08-08",
  },
  {
    id: "4",
    name: "Email Service API",
    description: "Comprehensive email sending and template management system.",
    type: "FREE" as const,
    endpointCount: 8,
    createdAt: "2023-08-05",
  },
  {
    id: "5",
    name: "Authentication API",
    description:
      "Complete authentication system with OAuth2 and social login support.",
    type: "PAID" as const,
    endpointCount: 15,
    createdAt: "2023-08-03",
  },
  {
    id: "6",
    name: "File Storage API",
    description:
      "Cloud storage solution with automatic backup and version control.",
    type: "PAID" as const,
    endpointCount: 10,
    createdAt: "2023-08-01",
  },
  {
    id: "7",
    name: "Analytics API",
    description:
      "Real-time analytics and reporting system with customizable dashboards.",
    type: "FREE" as const,
    endpointCount: 12,
    createdAt: "2023-07-28",
  },
  {
    id: "8",
    name: "Notification API",
    description:
      "Multi-channel notification system supporting push, SMS, and email.",
    type: "FREE" as const,
    endpointCount: 6,
    createdAt: "2023-07-25",
  },
]);

const page = usePage();
// Update the filters ref
const filters = ref<ApiFilters>({
  search: "",
  type: "ALL",
  sort: "name",
});

// Pagination state
const pagination = ref({
  rows: 6,
  first: 0,
  totalRecords: 0,
});

// Modified computed for pagination
const filteredApis = computed(() => {
  const filtered = apis.value
    .filter((api) => {
      const matchesSearch = api.name
        .toLowerCase()
        .includes(filters.value.search.toLowerCase());
      const matchesType =
        filters.value.type === "ALL" || api.type === filters.value.type;
      return matchesSearch && matchesType;
    })
    .sort((a, b) => {
      const isDesc = filters.value.sort.startsWith("-");
      const field = isDesc ? filters.value.sort.slice(1) : filters.value.sort;
      const direction = isDesc ? -1 : 1;

      return a[field] > b[field] ? direction : -direction;
    });

  // Update total records for pagination
  pagination.value.totalRecords = filtered.length;

  // Return paginated results
  return filtered.slice(
    pagination.value.first,
    pagination.value.first + pagination.value.rows
  );
});

const onPageChange = (event: { first: number; rows: number; page: number }) => {
  pagination.value.first = event.first;
  pagination.value.rows = event.rows;
};

// Add new ref for local dismissal state
const isDismissed = ref(localStorage.getItem("trialDismissed") === "true");

// Add computed for trial banner visibility
const showTrialBanner = computed(() => {
  try {
    // Only show for normal users
    if (page.props.auth.user.role === "admin") {
      return false;
    }

    // Calculate trial period (15 days)
    const trialPeriod = 15 * 24 * 60 * 60 * 1000; // 15 days in milliseconds
    const accountCreationDate = new Date(page.props.auth.user.created_at).getTime();
    const trialEndDate = accountCreationDate + trialPeriod;
    
    // Check if still within trial period
    const isWithinTrialPeriod = Date.now() < trialEndDate;

    // Use reactive ref instead of directly checking localStorage
    return isWithinTrialPeriod && !isDismissed.value;
  } catch (error) {
    console.error('Error checking trial banner status:', error);
    return false;
  }
});

const dismissTrial = () => {
  localStorage.setItem("trialDismissed", "true");
  isDismissed.value = true;
};
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <!-- Trial Notification Banner -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 -translate-y-1"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 -translate-y-1"
    >
      <div
        v-if="showTrialBanner"
        class="mt-5 max-w-7xl mx-auto relative isolate flex items-center gap-x-6 overflow-hidden bg-gradient-to-r from-orange-50 to-orange-100 px-6 py-2.5 sm:px-3.5 sm:before:flex-1 rounded-lg"
      >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
          <p class="text-sm leading-6 text-gray-700">
            <strong class="font-semibold text-orange-500"
              >Welcome to APIForge!</strong
            >
            <svg
              viewBox="0 0 2 2"
              class="mx-2 inline h-0.5 w-0.5 fill-current"
              aria-hidden="true"
            >
              <circle cx="1" cy="1" r="1" />
            </svg>
            Enjoy full access to all PAID APIs free for 15 days.
          </p>
          <Button
            label="Got it"
            size="small"
            severity="warning"
            text
            @click="dismissTrial"
            class="font-semibold"
          />
        </div>
        <div class="flex flex-1 justify-end">
          <button
            type="button"
            class="-m-3 p-3 focus-visible:outline-offset-[-4px]"
            @click="dismissTrial"
          >
            <span class="sr-only">Dismiss</span>
            <i
              class="pi pi-times text-gray-400 hover:text-gray-600"
              aria-hidden="true"
            ></i>
          </button>
        </div>
      </div>
    </Transition>

    <div class="py-7">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-5">
          <h2
            class="text-2xl font-semibold leading-tight text-gray-800 font-display"
          >
            Available APIs
          </h2>
          <Link
            :href="route('api.create')"
            v-if="$page.props.auth.user.role === 'admin'"
          >
            <Button
              label="Create New API"
              icon="pi pi-plus"
              severity="contrast"
              class="font-medium"
            />
          </Link>
        </div>

        <!-- Replace old filters with new component -->
        <div class="mb-6">
          <ApiFilterBar v-model="filters" />
        </div>

        <!-- Results count -->
        <div class="mb-4 flex items-center justify-between">
          <p class="text-sm text-gray-500">
            Showing
            <span class="font-medium text-gray-900">
              {{ pagination.first + 1 }} -
              {{
                Math.min(
                  pagination.first + pagination.rows,
                  pagination.totalRecords
                )
              }}
            </span>
            of
            <span class="font-medium text-gray-900">
              {{ pagination.totalRecords }}
            </span>
            APIs
          </p>
        </div>

        <!-- API Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
          <ApiCard v-for="api in filteredApis" :key="api.id" v-bind="api" />
        </div>

        <!-- Pagination -->
        <Paginator
          v-if="pagination.totalRecords > pagination.rows"
          v-model:first="pagination.first"
          v-model:rows="pagination.rows"
          :totalRecords="pagination.totalRecords"
          :rowsPerPageOptions="[6, 12, 24, 48]"
          @page="onPageChange"
          class="border border-gray-100 rounded-lg bg-white"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>

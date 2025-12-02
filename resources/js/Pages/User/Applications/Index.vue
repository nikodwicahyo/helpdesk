<template>
    <AppLayout role="user">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ $t('nav.applications') }}
                    </h1>
                    <p class="text-gray-600 mt-1">
                        {{ $t('applications.description') }}
                    </p>
                </div>
                <Link
                    :href="route('user.tickets.create')"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                    {{ $t('nav.createTicket') }}
                </Link>
            </div>
        </template>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div
                class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500"
            >
                <div class="text-gray-500 text-sm font-medium uppercase">
                    {{ $t('applications.stats.totalApplications') }}
                </div>
                <div class="text-3xl font-bold text-gray-900 mt-2">
                    {{ applicationStats.total_applications }}
                </div>
            </div>
            <div
                class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500"
            >
                <div class="text-gray-500 text-sm font-medium uppercase">
                    {{ $t('applications.stats.activeCategories') }}
                </div>
                <div class="text-3xl font-bold text-gray-900 mt-2">
                    {{ applicationStats.total_categories }}
                </div>
            </div>
            <div
                class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500"
            >
                <div class="text-gray-500 text-sm font-medium uppercase">
                    {{ $t('ticket.totalTickets') }}
                </div>
                <div class="text-3xl font-bold text-gray-900 mt-2">
                    {{ applicationStats.total_tickets }}
                </div>
            </div>
            <div
                class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500"
            >
                <div class="text-gray-500 text-sm font-medium uppercase">
                    {{ $t('dashboard.openTickets') }}
                </div>
                <div class="text-3xl font-bold text-gray-900 mt-2">
                    {{ applicationStats.open_tickets }}
                </div>
            </div>
        </div>

        <!-- Quick Access (Recent Tickets) -->
        <div v-if="recentTickets.length > 0" class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                {{ $t('dashboard.recentActivity') }}
            </h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <div
                        v-for="ticket in recentTickets"
                        :key="ticket.id"
                        class="p-4 hover:bg-gray-50 cursor-pointer flex items-center justify-between transition"
                        @click="
                            router.visit(route('user.tickets.show', ticket.id))
                        "
                    >
                        <div class="flex items-center space-x-4">
                            <div
                                :class="`w-2 h-2 rounded-full bg-${ticket.status_badge_color}-500`"
                            ></div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">
                                    {{ ticket.title }}
                                </h4>
                                <p class="text-xs text-gray-500">
                                    {{ ticket.ticket_number }} •
                                    {{ ticket.aplikasi?.name || $t('common.general') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ ticket.formatted_created_at }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input
                            v-model="form.search"
                            type="text"
                            :placeholder="$t('applications.searchPlaceholder')"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            @input="debouncedSearch"
                        />
                        <svg
                            class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                </div>
                <select
                    v-model="form.status"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    @change="submitFilters"
                >
                    <option value="">{{ $t('common.allStatuses') }}</option>
                    <option
                        v-for="status in filterOptions.statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
                <select
                    v-model="form.sort_by"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    @change="submitFilters"
                >
                    <option value="name">{{ $t('applications.sortBy.name') }}</option>
                    <option value="total_tickets">{{ $t('applications.sortBy.mostTickets') }}</option>
                    <option value="created_at">{{ $t('applications.sortBy.newest') }}</option>
                </select>
                <select
                    v-model="form.sort_direction"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    @change="submitFilters"
                >
                    <option value="asc">{{ $t('common.ascending') }}</option>
                    <option value="desc">{{ $t('common.descending') }}</option>
                </select>
            </div>
        </div>

        <!-- Applications Grid -->
        <div
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
        >
            <div
                v-for="application in applications.data"
                :key="application.id"
                class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 cursor-pointer flex flex-col h-full"
                @click="createTicketForApplication(application)"
            >
                <div class="flex items-center mb-4">
                    <div
                        class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 shrink-0"
                    >
                        <span class="text-2xl">💻</span>
                    </div>
                    <div>
                        <h3
                            class="text-lg font-semibold text-gray-900 line-clamp-1"
                            :title="application.name"
                        >
                            {{ application.name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ application.code }}
                        </p>
                    </div>
                </div>

                <p class="text-gray-600 text-sm mb-4 line-clamp-2 flex-grow">
                    {{ application.description || $t('applications.noDescription') }}
                </p>

                <div
                    class="flex items-center justify-between text-xs text-gray-500 mt-auto pt-4 border-t border-gray-100"
                >
                    <span class="flex items-center">
                        <svg
                            class="w-4 h-4 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                            />
                        </svg>
                        {{ application.total_tickets }} {{ $t('common.tickets') }}
                    </span>
                    <span
                        :class="{
                            'px-2 py-1 rounded-full text-xs font-medium': true,
                            'bg-green-100 text-green-800':
                                application.status === 'active',
                            'bg-red-100 text-red-800':
                                application.status === 'inactive',
                            'bg-yellow-100 text-yellow-800':
                                application.status === 'maintenance',
                        }"
                    >
                        {{ $t(`status.${application.status}`) }}
                    </span>
                </div>

                <div class="mt-4">
                    <button
                        @click.stop="createTicketForApplication(application)"
                        class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm font-medium hover:bg-indigo-700 transition"
                    >
                        {{ $t('nav.createTicket') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="applications.data.length === 0" class="text-center py-12">
            <svg
                class="mx-auto h-12 w-12 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                {{ $t('applications.noApplicationsFound') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ $t('applications.adjustFilters') }}
            </p>
        </div>

        <!-- Pagination -->
        <div
            v-if="applications.data.length > 0"
            class="mt-8 flex justify-center"
        >
            <nav
                class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                aria-label="Pagination"
            >
                <Link
                    v-for="(link, key) in applications.links"
                    :key="key"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                        link.active
                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                        !link.url
                            ? 'opacity-50 cursor-not-allowed'
                            : 'cursor-pointer',
                        key === 0 ? 'rounded-l-md' : '',
                        key === applications.links.length - 1
                            ? 'rounded-r-md'
                            : '',
                    ]"
                    :preserve-scroll="true"
                    :preserve-state="true"
                    @click.prevent="
                        link.url &&
                            router.visit(link.url, {
                                preserveState: true,
                                preserveScroll: true,
                                data: form,
                            })
                    "
                />
            </nav>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { debounce } from "lodash";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    applications: Object,
    filters: Object,
    filterOptions: Object,
    recentTickets: Array,
    popularApplications: Array,
    applicationStats: Object,
});

const form = ref({
    search: props.filters.search || "",
    status: props.filters.status || "",
    sort_by: props.filters.sort_by || "name",
    sort_direction: props.filters.sort_direction || "asc",
});

const submitFilters = () => {
    router.get(route("user.applications.index"), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedSearch = debounce(() => {
    submitFilters();
}, 300);

const createTicketForApplication = (application) => {
    router.get(route("user.tickets.create"), {
        application_id: application.id,
    });
};
</script>

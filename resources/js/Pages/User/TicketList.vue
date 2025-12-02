<template>
    <AppLayout role="user">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ t('nav.myTickets') }}</h1>
                    <p class="text-gray-600 mt-1">{{ t('ticket.viewTicket') }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('user.tickets.create')"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        {{ t('nav.createTicket') }}
                    </Link>
                </div>
            </div>
        </template>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <StatCard
                :title="t('dashboard.totalTickets')"
                :value="stats.total_tickets"
                icon="📋"
                color="blue"
            />
            <StatCard
                :title="t('dashboard.openTickets')"
                :value="stats.open_tickets"
                icon="🔓"
                color="yellow"
            />
            <StatCard
                :title="t('status.inProgress')"
                :value="stats.in_progress_tickets"
                icon="⚙️"
                color="indigo"
            />
            <StatCard
                :title="t('dashboard.resolvedTickets')"
                :value="stats.resolved_tickets"
                icon="✅"
                color="green"
            />
        </div>

        <!-- Advanced Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">{{ t('activityLog.filters') }}</h2>
                <button
                    @click="toggleFilters"
                    class="text-indigo-600 hover:text-indigo-800 font-medium text-sm"
                >
                    {{ showFilters ? t('common.hide') : t('common.show') }} {{ t('activityLog.filters') }}
                </button>
            </div>

            <div v-show="showFilters" class="space-y-4">
                <!-- Search Bar -->
                <div class="relative">
                    <input
                        v-model="filters.search"
                        @input="debouncedSearch"
                        type="text"
                        :placeholder="t('search.searchTickets')"
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                    <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.status') }}</label>
                        <select
                            v-model="filters.status"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ t('activityLog.allEntities') }}</option>
                            <option value="open">{{ t('status.open') }}</option>
                            <option value="assigned">{{ t('status.assigned') }}</option>
                            <option value="in_progress">{{ t('status.inProgress') }}</option>
                            <option value="waiting_response">{{ t('status.waiting') }}</option>
                            <option value="resolved">{{ t('status.resolved') }}</option>
                            <option value="closed">{{ t('status.closed') }}</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.priority') }}</label>
                        <select
                            v-model="filters.priority"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ t('activityLog.allEntities') }}</option>
                            <option value="low">{{ t('priority.low') }}</option>
                            <option value="medium">{{ t('priority.medium') }}</option>
                            <option value="high">{{ t('priority.high') }}</option>
                            <option value="urgent">{{ t('priority.urgent') }}</option>
                        </select>
                    </div>

                    <!-- Application Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.application') }}</label>
                        <select
                            v-model="filters.aplikasi_id"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ t('activityLog.allEntities') }}</option>
                            <option v-for="app in applications" :key="app.id" :value="app.id">
                                {{ app.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('common.dateRange') }}</label>
                        <select
                            v-model="filters.date_range"
                            @change="applyDateRange"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ t('common.allTime') }}</option>
                            <option value="today">{{ t('time.today') }}</option>
                            <option value="yesterday">{{ t('time.yesterday') }}</option>
                            <option value="this_week">{{ t('time.thisWeek') }}</option>
                            <option value="last_week">{{ t('time.lastWeek') }}</option>
                            <option value="this_month">{{ t('time.thisMonth') }}</option>
                            <option value="last_month">{{ t('time.lastMonth') }}</option>
                            <option value="this_year">{{ t('time.thisYear') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Additional Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.category') }}</label>
                        <select
                            v-model="filters.kategori_id"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ t('common.allCategories') }}</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Assigned Teknisi Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.assignedTo') }}</label>
                        <select
                            v-model="filters.teknisi_id"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ t('common.anyone') }}</option>
                            <option value="unassigned">{{ t('common.unassigned') }}</option>
                            <option v-for="teknisi in teknisis" :key="teknisi.id" :value="teknisi.id">
                                {{ teknisi.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('common.sortBy') }}</label>
                        <select
                            v-model="filters.sort_by"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="created_at">{{ t('time.createdDate') }}</option>
                            <option value="updated_at">{{ t('common.lastUpdated') }}</option>
                            <option value="priority">{{ t('ticket.priority') }}</option>
                            <option value="status">{{ t('ticket.status') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            {{ t('common.showingOf', { from: tickets.from || 0, to: tickets.to || 0, total: tickets.total || 0 }) }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button
                            @click="clearFilters"
                            class="text-gray-600 hover:text-gray-800 font-medium text-sm"
                        >
                            {{ t('action.clearAll') }}
                        </button>
                        <button
                            @click="exportTickets"
                            :disabled="exportLoading"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition text-sm flex items-center',
                                exportLoading
                                    ? 'bg-gray-400 text-gray-200 cursor-not-allowed'
                                    : 'bg-green-600 text-white hover:bg-green-700'
                            ]"
                        >
                            <svg
                                v-if="exportLoading"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            {{ exportLoading ? t('common.exporting') : t('common.export') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Toggle and Results -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ t('ticket.yourTickets') }}
                    </h2>
                    <div class="flex items-center space-x-2 bg-gray-100 rounded-lg p-1">
                        <button
                            @click="viewMode = 'grid'"
                            :class="[
                                'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                                viewMode === 'grid' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            {{ t('common.gridView') }}
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="[
                                'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                                viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            {{ t('common.listView') }}
                        </button>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <select
                        v-model="perPage"
                        @change="changePerPage"
                        class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option :value="10">{{ t('common.perPage', { count: 10 }) }}</option>
                        <option :value="25">{{ t('common.perPage', { count: 25 }) }}</option>
                        <option :value="50">{{ t('common.perPage', { count: 50 }) }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-gray-500">{{ t('common.loading') }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="tickets.data.length === 0" class="bg-white rounded-lg shadow-md p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('common.noData') }}</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ hasActiveFilters ? t('ticket.adjustFilters') : t('ticket.createFirstTicket') }}
            </p>
            <div class="mt-6">
                <Link
                    v-if="!hasActiveFilters"
                    :href="route('user.tickets.create')"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition"
                >
                    {{ t('nav.createTicket') }}
                </Link>
                <button
                    v-else
                    @click="clearFilters"
                    class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition"
                >
                    {{ t('common.clearFilters') }}
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="ticket in tickets.data"
                :key="ticket.id"
                class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                @click="viewTicket(ticket.id)"
            >
                <div class="p-6">
                    <!-- Ticket Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-mono text-gray-500">{{ ticket.ticket_number }}</span>
                            <div v-if="ticket.is_overdue" class="w-2 h-2 bg-red-500 rounded-full" :title="t('ticket.overdue')"></div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(ticket.priority)]">
                            {{ ticket.priority_label }}
                        </span>
                    </div>

                    <!-- Ticket Title -->
                    <h3 class="text-lg font-medium text-gray-900 mb-2 line-clamp-2">
                        {{ ticket.title }}
                    </h3>

                    <!-- Ticket Description -->
                    <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                        {{ ticket.description }}
                    </p>

                    <!-- Ticket Meta -->
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">{{ t('ticket.status') }}:</span>
                            <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(ticket.status)]">
                                {{ ticket.status_label }}
                            </span>
                        </div>

                        <div v-if="ticket.aplikasi" class="flex items-center justify-between">
                            <span class="text-gray-500">{{ t('ticket.application') }}:</span>
                            <span class="text-gray-900">{{ ticket.aplikasi.name }}</span>
                        </div>

                        <div v-if="ticket.assigned_teknisi" class="flex items-center justify-between">
                            <span class="text-gray-500">{{ t('ticket.assignedTo') }}:</span>
                            <span class="text-gray-900">{{ ticket.assigned_teknisi.name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">{{ t('common.created') }}:</span>
                            <span class="text-gray-900">{{ ticket.formatted_created_at }}</span>
                        </div>

                        <div v-if="ticket.time_elapsed" class="flex items-center justify-between">
                            <span class="text-gray-500">{{ t('ticket.age') }}:</span>
                            <span class="text-gray-900">{{ ticket.time_elapsed }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button
                                v-if="ticket.status === 'resolved'"
                                @click.stop="rateTicket(ticket.id)"
                                class="text-yellow-600 hover:text-yellow-800 text-sm font-medium"
                            >
                                {{ t('ticket.rateTicket') }}
                            </button>
                            <button
                                v-if="['open', 'assigned', 'in_progress'].includes(ticket.status)"
                                @click.stop="addComment(ticket.id)"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                {{ t('common.addComment') }}
                            </button>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-else class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.ticketNumber') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.ticketTitle') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.application') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.status') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.priority') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.assignedTo') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('ticket.createdAt') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('action.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-mono text-gray-900">{{ ticket.ticket_number }}</span>
                                    <div v-if="ticket.is_overdue" class="w-2 h-2 bg-red-500 rounded-full" :title="t('ticket.overdue')"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ ticket.title }}</p>
                                    <p class="text-xs text-gray-500 truncate mt-1">{{ ticket.description }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">{{ ticket.aplikasi?.name || '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(ticket.status)]">
                                    {{ ticket.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(ticket.priority)]">
                                    {{ ticket.priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="ticket.assigned_teknisi" class="text-sm text-gray-900">
                                    {{ ticket.assigned_teknisi.name }}
                                </span>
                                <span v-else class="text-sm text-gray-400">{{ t('common.unassigned') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <p>{{ ticket.formatted_created_at }}</p>
                                    <p v-if="ticket.time_elapsed" class="text-xs text-gray-500">{{ ticket.time_elapsed }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="viewTicket(ticket.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                        :title="t('common.viewDetails')"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="ticket.status === 'resolved'"
                                        @click="rateTicket(ticket.id)"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        :title="t('ticket.rateTicket')"
                                    >
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                <SimplePagination
                    :data="tickets"
                    :label="t('common.tickets')"
                    @page-changed="handlePageChange"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n'
import { route } from 'ziggy-js';
import { debounce } from 'lodash';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import SimplePagination from '@/Components/Common/SimplePagination.vue';

const { t } = useI18n()

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    applications: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    teknisis: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const loading = ref(false);
const exportLoading = ref(false);
const showFilters = ref(true);
const viewMode = ref('grid');
const perPage = ref(props.tickets.per_page || 10);

const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    aplikasi_id: props.filters.aplikasi_id || '',
    kategori_id: props.filters.kategori_id || '',
    teknisi_id: props.filters.teknisi_id || '',
    date_range: props.filters.date_range || '',
    sort_by: props.filters.sort_by || 'created_at',
});

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const applyFilters = () => {
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    params.set('per_page', perPage.value);

    router.get(route('user.tickets.index'), params.toString(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyDateRange = () => {
    const today = new Date();
    let fromDate = new Date();
    let toDate = new Date();

    switch (filters.value.date_range) {
        case 'today':
            fromDate = today;
            toDate = today;
            break;
        case 'yesterday':
            fromDate = new Date(today);
            fromDate.setDate(today.getDate() - 1);
            toDate = fromDate;
            break;
        case 'this_week':
            fromDate = new Date(today);
            fromDate.setDate(today.getDate() - today.getDay());
            toDate = today;
            break;
        case 'last_week':
            fromDate = new Date(today);
            fromDate.setDate(today.getDate() - today.getDay() - 7);
            toDate = new Date(today);
            toDate.setDate(today.getDate() - today.getDay() - 1);
            break;
        case 'this_month':
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            toDate = today;
            break;
        case 'last_month':
            fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            toDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'this_year':
            fromDate = new Date(today.getFullYear(), 0, 1);
            toDate = today;
            break;
    }

    // Apply date range to filters
    if (filters.value.date_range) {
        filters.value.date_from = fromDate.toISOString().split('T')[0];
        filters.value.date_to = toDate.toISOString().split('T')[0];
    } else {
        filters.value.date_from = '';
        filters.value.date_to = '';
    }

    applyFilters();
};

const clearFilters = () => {
    filters.value = {
        search: '',
        status: '',
        priority: '',
        aplikasi_id: '',
        kategori_id: '',
        teknisi_id: '',
        date_range: '',
        date_from: '',
        date_to: '',
        sort_by: 'created_at',
    };
    applyFilters();
};

const changePerPage = () => {
    applyFilters();
};

const viewTicket = (ticketId) => {
    router.visit(route('user.tickets.show', ticketId));
};

const rateTicket = (ticketId) => {
    router.visit(route('user.tickets.show', ticketId), {
        data: { focus: 'rating' }
    });
};

const addComment = (ticketId) => {
    router.visit(route('user.tickets.show', ticketId), {
        data: { focus: 'comment' }
    });
};

const exportTickets = async () => {
    if (exportLoading.value) return;

    try {
        exportLoading.value = true;

        const params = new URLSearchParams();

        Object.entries(filters.value).forEach(([key, value]) => {
            if (value) {
                params.set(key, value);
            }
        });

        // Calculate approximate ticket count for console logging
        const ticketCount = props.tickets.total || 0;
        console.log(`Exporting ${ticketCount} tickets matching your filters...`);

        // Open export URL in new window
        const exportUrl = route('user.tickets.export') + '?' + params.toString();
        window.open(exportUrl, '_blank');

        // Show success message in console after a short delay
        setTimeout(() => {
            console.log('Export ready! Download should start automatically.');
        }, 1000);

    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export tickets. Please try again.');
    } finally {
        exportLoading.value = false;
    }
};

const handlePageChange = (page) => {
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    params.set('page', page);
    params.set('per_page', perPage.value);

    router.get(route('user.tickets.index'), params.toString(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-yellow-100 text-yellow-800',
        assigned: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-indigo-100 text-indigo-800',
        waiting_response: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
};

// Initialize filters on mount
onMounted(() => {
    if (props.filters.date_range) {
        applyDateRange();
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

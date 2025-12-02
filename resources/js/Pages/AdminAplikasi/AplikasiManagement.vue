<template>
    <AppLayout role="admin-aplikasi">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Application Management</h1>
                    <p class="text-gray-600 mt-1">Manage applications and their categories</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="showCreateModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Application
                    </button>
                    <button
                        @click="exportApplications"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </template>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <StatCard
                title="Total Applications"
                :value="stats.total_applications"
                icon="📱"
                color="blue"
            />
            <StatCard
                title="Active Applications"
                :value="stats.active_applications"
                icon="✅"
                color="green"
            />
            <StatCard
                title="Total Categories"
                :value="stats.total_categories"
                icon="🏷️"
                color="purple"
            />
            <StatCard
                title="Total Tickets"
                :value="stats.total_tickets"
                icon="🎫"
                color="yellow"
            />
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Applications</label>
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            @input="debouncedSearch"
                            type="text"
                            placeholder="Search by name or description..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select
                        v-model="filters.sort_by"
                        @change="applyFilters"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="name">Name</option>
                        <option value="created_at">Created Date</option>
                        <option value="ticket_count">Ticket Count</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Applications Grid/List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Applications ({{ applications.total }})
                    </h2>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2 bg-gray-100 rounded-lg p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                                    viewMode === 'grid' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                Grid View
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                                    viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                List View
                            </button>
                        </div>
                        <select
                            v-model="perPage"
                            @change="changePerPage"
                            class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option :value="12">12 per page</option>
                            <option :value="24">24 per page</option>
                            <option :value="48">48 per page</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="p-12 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-500">Loading applications...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="applications.data.length === 0" class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No applications found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating your first application' }}
                </p>
                <div class="mt-6">
                    <button
                        v-if="!hasActiveFilters"
                        @click="showCreateModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition"
                    >
                        Create New Application
                    </button>
                    <button
                        v-else
                        @click="clearFilters"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="app in applications.data"
                        :key="app.id"
                        class="bg-gray-50 rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer border border-gray-200"
                        @click="viewApplication(app.id)"
                    >
                        <!-- Application Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ app.name }}</h3>
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(app.status)]">
                                        {{ app.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Application Description -->
                        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ app.description }}</p>

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ app.total_tickets || app.ticket_count || 0 }}</p>
                                <p class="text-xs text-gray-500">Total Tickets</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ app.total_categories || app.category_count || 0 }}</p>
                                <p class="text-xs text-gray-500">Categories</p>
                            </div>
                        </div>

                        <!-- Assigned Teknisi -->
                        <div v-if="app.assigned_teknisis && app.assigned_teknisis.length > 0" class="mb-4">
                            <p class="text-xs text-gray-500 mb-2">Assigned Teknisi:</p>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="teknisi in app.assigned_teknisis.slice(0, 3)"
                                    :key="teknisi.id"
                                    class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
                                >
                                    {{ teknisi.name }}
                                </span>
                                <span
                                    v-if="app.assigned_teknisis.length > 3"
                                    class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
                                >
                                    +{{ app.assigned_teknisis.length - 3 }} more
                                </span>
                            </div>
                        </div>

                        <!-- Recent Categories -->
                        <div v-if="app.recent_categories && app.recent_categories.length > 0" class="mb-4">
                            <p class="text-xs text-gray-500 mb-2">Recent Categories:</p>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="category in app.recent_categories.slice(0, 3)"
                                    :key="category.id"
                                    class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full"
                                >
                                    {{ category.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="flex items-center space-x-2">
                                <button
                                    @click.stop="manageCategories(app.id)"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                >
                                    Categories
                                </button>
                                <button
                                    @click.stop="assignTeknisi(app.id)"
                                    class="text-green-600 hover:text-green-800 text-sm font-medium"
                                >
                                    Assign Teknisi
                                </button>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click.stop="editApplication(app)"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="Edit"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click.stop="deleteApplication(app)"
                                    class="text-red-600 hover:text-red-900"
                                    title="Delete"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Application
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Categories
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Assigned Teknisi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tickets
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="app in applications.data"
                            :key="app.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ app.name }}</div>
                                        <div class="text-sm text-gray-500">{{ app.version || 'v1.0' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p class="text-sm text-gray-900 truncate">{{ app.description }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(app.status)]">
                                    {{ app.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <p>{{ app.total_categories || app.category_count || 0 }} categories</p>
                                    <p v-if="app.active_categories > 0" class="text-xs text-green-600">
                                        {{ app.active_categories }} active
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="app.assigned_teknisis && app.assigned_teknisis.length > 0" class="text-sm">
                                    <p class="text-gray-900">{{ app.assigned_teknisis.length }} assigned</p>
                                    <p class="text-xs text-gray-500">
                                        {{ app.assigned_teknisis.slice(0, 2).map(t => t.name).join(', ') }}
                                        <span v-if="app.assigned_teknisis.length > 2">...</span>
                                    </p>
                                </div>
                                <span v-else class="text-sm text-gray-400">Unassigned</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <p>{{ app.total_tickets || app.ticket_count || 0 }} total</p>
                                    <p v-if="app.open_tickets > 0" class="text-xs text-yellow-600">
                                        {{ app.open_tickets }} open
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ formatDate(app.created_at) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="viewApplication(app.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                        title="View Details"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="editApplication(app)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Edit"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="manageCategories(app.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="Manage Categories"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteApplication(app)"
                                        class="text-red-600 hover:text-red-900"
                                        title="Delete"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <SimplePagination
                :data="applications"
                label="aplikasi"
                @page-changed="handlePageChange"
            />
        </div>

        <!-- Create/Edit Application Modal -->
        <AdminAplikasiApplicationModal
            v-if="showCreateModal || showEditModal"
            :application="editingApplication"
            :mode="showCreateModal ? 'create' : 'edit'"
            @close="closeApplicationModal"
            @saved="onApplicationSaved"
        />

        <!-- Assign Teknisi Modal -->
        <AssignTeknisiModal
            v-if="showAssignModal"
            :application="selectedApplication"
            :teknisis="allTeknisis"
            @close="closeAssignModal"
            @assigned="onTeknisiAssigned"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { debounce } from 'lodash';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import SimplePagination from '@/Components/Common/SimplePagination.vue';
import AdminAplikasiApplicationModal from '@/Components/Modals/AdminAplikasiApplicationModal.vue';
import AssignTeknisiModal from '@/Components/Modals/AssignTeknisiModal.vue';

const props = defineProps({
    applications: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
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
const viewMode = ref('grid');
const perPage = ref(props.applications.per_page || 12);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showAssignModal = ref(false);
const editingApplication = ref(null);
const selectedApplication = ref(null);
const selectedApplications = ref([]);
const bulkAction = ref('');

const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    sort_by: props.filters.sort_by || 'name',
});

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const applyFilters = () => {
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    params.set('per_page', perPage.value);

    router.get(route('admin-aplikasi.applications.index'), params.toString(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = {
        search: '',
        status: '',
        sort_by: 'name',
    };
    applyFilters();
};

const changePerPage = () => {
    applyFilters();
};

const viewApplication = (appId) => {
    router.visit(route('admin-aplikasi.applications.show', appId));
};

const editApplication = (app) => {
    editingApplication.value = app;
    showEditModal.value = true;
};

const manageCategories = (appId) => {
    router.visit(route('admin-aplikasi.categories.index', { aplikasi_id: appId }));
};

const assignTeknisi = (app) => {
    selectedApplication.value = app;
    showAssignModal.value = true;
};

const deleteApplication = (app) => {
    if (confirm(`Are you sure you want to delete "${app.name}"? This action cannot be undone.`)) {
        router.delete(route('admin-aplikasi.applications.destroy', app.id), {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

const exportApplications = () => {
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    window.open(route('admin-aplikasi.applications.export') + '?' + params.toString(), '_blank');
};

const closeApplicationModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingApplication.value = null;
};

const closeAssignModal = () => {
    showAssignModal.value = false;
    selectedApplication.value = null;
};

const onApplicationSaved = () => {
    closeApplicationModal();
    router.reload();
};

const onTeknisiAssigned = () => {
    closeAssignModal();
    router.reload();
};

const handlePageChange = (page) => {
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    params.set('page', page);

    router.get(route('admin-aplikasi.applications.index'), params.toString(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        maintenance: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Computed property for all teknisi (combining from props)
const allTeknisis = computed(() => {
    return props.teknisis || [];
});

onMounted(() => {
    // Initialize any component-specific logic here
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
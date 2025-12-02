<template>
    <AppLayout role="admin">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ t('nav.applications') }}
                    </h1>
                    <p class="text-gray-600 mt-1">
                        {{ t('activityLog.description') }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="openCreateModal"
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
                        {{ t('common.create') }} {{ t('nav.applications') }}
                    </button>
                    <button
                        @click="exportApplications"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ t('common.export') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <StatCard
                :title="t('activityLog.totalActivities')"
                :value="stats.total_applications"
                icon="💻"
                color="blue"
            />
            <StatCard
                :title="t('status.active')"
                :value="stats.active_applications"
                icon="✅"
                color="green"
            />
            <StatCard
                :title="t('status.inactive')"
                :value="stats.inactive_applications"
                icon="⏸️"
                color="yellow"
            />
            <StatCard
                :title="t('activityLog.resolutionRate')"
                :value="`${stats.resolution_rate}%`"
                icon="📈"
                color="purple"
            />
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >{{ t('common.search') }}</label
                    >
                    <input
                        v-model="filters.search"
                        @input="applyFilters"
                        type="text"
                        :placeholder="t('search.searchTickets')"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >{{ t('ticket.status') }}</label
                    >
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">{{ t('activityLog.allEntities') }}</option>
                        <option
                            v-for="status in filterOptions.statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >{{ t('modal.applicationModal.adminAplikasi') }}</label
                    >
                    <select
                        v-model="filters.admin_aplikasi_nip"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">{{ t('activityLog.allUsers') }}</option>
                        <option
                            v-for="admin in filterOptions.admin_aplikasis"
                            :key="admin.value"
                            :value="admin.value"
                        >
                            {{ admin.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >{{ t('common.sortBy') }}</label
                    >
                    <div class="flex space-x-2">
                        <select
                            v-model="filters.sort_by"
                            @change="applyFilters"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="name">{{ t('common.name') }}</option>
                            <option value="code">{{ t('modal.applicationModal.applicationCode') }}</option>
                            <option value="created_at">{{ t('time.createdDate') }}</option>
                            <option value="total_tickets">{{ t('ticket.totalTickets') }}</option>
                            <option value="status">{{ t('ticket.status') }}</option>
                        </select>
                        <button
                            @click="toggleSortDirection"
                            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    v-if="filters.sort_direction === 'asc'"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 15l7-7 7 7"
                                />
                                <path
                                    v-else
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ t('activityLog.title') }}
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('nav.applications') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('modal.applicationModal.adminAplikasi') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.status') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('nav.tickets') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('nav.categories') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('nav.teknisi') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('common.created') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('action.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="applications.data.length === 0">
                            <td
                                colspan="8"
                                class="px-6 py-12 text-center text-gray-500"
                            >
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
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <p class="mt-2">{{ t('common.noData') }}</p>
                            </td>
                        </tr>
                        <tr
                            v-for="app in applications.data"
                            :key="app.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4">
                                <div>
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ app.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ app.code }}
                                    </div>
                                    <div
                                        v-if="app.description"
                                        class="text-xs text-gray-400 mt-1 truncate max-w-xs"
                                    >
                                        {{ app.description }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="app.admin_aplikasi">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ app.admin_aplikasi.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ app.admin_aplikasi.nip }}
                                    </div>
                                </div>
                                <span v-else class="text-sm text-gray-400"
                                    >Not assigned</span
                                >
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusBadgeClass(app.status)">
                                    {{ app.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <div class="font-medium">
                                        {{ app.total_tickets }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <span class="text-yellow-600"
                                            >{{ app.open_tickets }} open</span
                                        >
                                        /
                                        <span class="text-green-600"
                                            >{{
                                                app.resolved_tickets
                                            }}
                                            resolved</span
                                        >
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">{{
                                    app.total_categories
                                }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">{{
                                    app.assigned_teknisi_count
                                }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    {{ app.formatted_created_at }}
                                </div>
                                <div
                                    v-if="app.last_ticket_activity"
                                    class="text-xs text-gray-500"
                                >
                                    Active: {{ app.last_ticket_activity }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button
                                        @click="editApplication(app)"
                                        class="text-blue-600 hover:text-blue-800"
                                        title="Edit Application"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="viewApplication(app.id)"
                                        class="text-indigo-600 hover:text-indigo-800"
                                        title="View Details"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="toggleStatus(app)"
                                        :class="
                                            app.status === 'active'
                                                ? 'text-yellow-600 hover:text-yellow-800'
                                                : 'text-green-600 hover:text-green-800'
                                        "
                                        :title="
                                            app.status === 'active'
                                                ? 'Deactivate'
                                                : 'Activate'
                                        "
                                    >
                                        <svg
                                            v-if="app.status === 'active'"
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
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
                    :data="applications"
                    label="application"
                    @page-changed="handlePageChange"
                />
            </div>
        </div>

        <!-- Application Details Modal -->
        <Modal
            v-model:show="showApplicationModal"
            title="Application Details"
            size="xl"
            @close="closeApplicationModal"
        >
            <ApplicationDetails
                v-if="selectedApplication"
                :application="selectedApplication"
                :ticket-stats="selectedTicketStats"
            />
        </Modal>

        <!-- Application Create/Edit Modal -->
        <ApplicationModal
            v-if="showCreateModal || showEditModal"
            :mode="showCreateModal ? 'create' : 'edit'"
            :application="editingApplication"
            :applications="filterOptions.admin_aplikasis"
            @close="closeModal"
            @saved="handleApplicationSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useI18n } from 'vue-i18n'
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import SimplePagination from "@/Components/Common/SimplePagination.vue";
import ApplicationDetails from "@/Components/Application/ApplicationDetails.vue";
import Modal from "@/Components/Common/Modal.vue";
import ApplicationModal from "@/Components/Modals/ApplicationModal.vue";

const { t } = useI18n()

const props = defineProps({
    applications: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    application: {
        type: Object,
        default: null,
    },
    ticketStats: {
        type: Object,
        default: null,
    },
});

const filters = ref({ ...props.filters });
const showApplicationModal = ref(false);
const selectedApplication = ref(null);
const selectedTicketStats = ref(null);

// Modal state management
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingApplication = ref(null);

const getStatusBadgeClass = (status) => {
    const classes = {
        active: "px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800",
        inactive:
            "px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800",
        maintenance:
            "px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800",
        deprecated:
            "px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800",
    };
    return classes[status] || classes.inactive;
};

const applyFilters = () => {
    router.get(route("admin.applications.index"), filters.value, {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleSortDirection = () => {
    filters.value.sort_direction =
        filters.value.sort_direction === "asc" ? "desc" : "asc";
    applyFilters();
};

const viewApplication = (appId) => {
    router.get(
        route("admin.applications.show", appId),
        {},
        {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.application) {
                    selectedApplication.value = page.props.application;
                    selectedTicketStats.value = page.props.ticketStats;
                    showApplicationModal.value = true;
                }
            },
        }
    );
};

const toggleStatus = async (application) => {
    const newStatus = application.status === "active" ? "inactive" : "active";
    const action = newStatus === "active" ? "activate" : "deactivate";

    if (!confirm(`Are you sure you want to ${action} this application?`)) {
        return;
    }

    try {
        const response = await fetch(
            route("admin.applications.toggle-status", application.id),
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({}),
            }
        );

        const data = await response.json();

        if (data.success) {
            router.reload({ preserveScroll: true });
        } else {
            alert(
                "Failed to update application status: " + data.errors.join(", ")
            );
        }
    } catch (error) {
        alert("Error updating application status: " + error.message);
    }
};

const handlePageChange = (page) => {
    filters.value.page = page;
    applyFilters();
};

// Modal methods
const openCreateModal = () => {
    showCreateModal.value = true;
    showEditModal.value = false;
    editingApplication.value = null;
};

const editApplication = (app) => {
    editingApplication.value = { ...app };
    showEditModal.value = true;
    showCreateModal.value = false;
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingApplication.value = null;
};

const handleApplicationSaved = () => {
    closeModal();
    // Reload the applications list
    router.reload({ preserveScroll: true });
};

const closeApplicationModal = () => {
    showApplicationModal.value = false;
    selectedApplication.value = null;
    selectedTicketStats.value = null;
};

const exportApplications = async () => {
    try {
        // Build export parameters with all current filters
        const exportParams = { ...filters.value };

        // Remove pagination-related params from export
        delete exportParams.page;

        const params = new URLSearchParams(exportParams);
        const exportUrl = route('admin.applications.export') + '?' + params.toString();

        // Create download link directly (more reliable for CSV downloads)
        const link = document.createElement('a');
        link.href = exportUrl;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();

        // Clean up after a short delay
        setTimeout(() => {
            document.body.removeChild(link);
        }, 100);

        // Show success notification
        const successDiv = document.createElement('div');
        successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
        successDiv.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Export started! Download will begin shortly.
        `;
        document.body.appendChild(successDiv);

        setTimeout(() => {
            if (document.body.contains(successDiv)) {
                document.body.removeChild(successDiv);
            }
        }, 3000);

    } catch (error) {
        console.error('Export failed:', error);

        // Show error notification
        const errorMessage = error.message || 'Unknown error occurred during export';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
        errorDiv.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Export failed: ${errorMessage}
        `;
        document.body.appendChild(errorDiv);

        setTimeout(() => {
            if (document.body.contains(errorDiv)) {
                document.body.removeChild(errorDiv);
            }
        }, 5000);
    }
};

onMounted(() => {
    if (props.application) {
        selectedApplication.value = props.application;
        selectedTicketStats.value = props.ticketStats;
        showApplicationModal.value = true;
    }
});
</script>

<template>
    <AppLayout role="admin-aplikasi">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        href="/admin-aplikasi/applications"
                        class="text-gray-600 hover:text-gray-900 transition"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ application.name }}</h1>
                        <p class="text-gray-600 mt-1">{{ application.description }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="performHealthCheck"
                        :disabled="healthCheckLoading"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" :class="healthCheckLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Health Check
                    </button>
                    <button
                        @click="editApplication"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                </div>
            </div>
        </template>

        <!-- Application Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <StatCard
                title="Status"
                :value="application.status_label"
                :icon="getStatusIcon(application.status)"
                :color="getStatusColor(application.status)"
            />
            <StatCard
                title="Version"
                :value="application.version || application.current_version || 'N/A'"
                icon="🔢"
                color="blue"
            />
            <StatCard
                title="Total Tickets"
                :value="ticketStats.total"
                icon="🎫"
                color="purple"
            />
            <StatCard
                title="Health Score"
                :value="healthMetrics.health_status || 'Unknown'"
                :icon="getHealthIcon(healthMetrics.health_status)"
                :color="getHealthColor(healthMetrics.health_status)"
            />
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="px-6 py-3 text-sm font-medium transition-colors"
                        :class="activeTab === tab.id 
                            ? 'border-b-2 border-indigo-600 text-indigo-600' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Application Details -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold mb-4">Application Details</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-600">Code</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ application.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Category</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ application.category_label }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Criticality</dt>
                                    <dd>
                                        <span :class="getCriticalityBadge(application.criticality)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ application.criticality }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Created</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatDate(application.created_at) }}</dd>
                                </div>
                                <div v-if="application.last_updated">
                                    <dt class="text-sm text-gray-600">Last Updated</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatDate(application.last_updated) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Health Metrics -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold mb-4">Health Metrics</h3>
                            <dl class="space-y-3">
                                <div v-if="healthMetrics.uptime_percentage">
                                    <dt class="text-sm text-gray-600">Uptime</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ healthMetrics.uptime_percentage }}%</dd>
                                </div>
                                <div v-if="healthMetrics.response_time_avg">
                                    <dt class="text-sm text-gray-600">Avg Response Time</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ healthMetrics.response_time_avg }}ms</dd>
                                </div>
                                <div v-if="healthMetrics.error_rate">
                                    <dt class="text-sm text-gray-600">Error Rate</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ (healthMetrics.error_rate * 100).toFixed(2) }}%</dd>
                                </div>
                                <div v-if="application.current_users !== null">
                                    <dt class="text-sm text-gray-600">Current Users</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ application.current_users }} / {{ application.max_users || 'Unlimited' }}
                                    </dd>
                                </div>
                                <div v-if="healthMetrics.last_health_check">
                                    <dt class="text-sm text-gray-600">Last Health Check</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatDateTime(healthMetrics.last_health_check) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Tickets Tab -->
                <div v-if="activeTab === 'tickets'">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Ticket Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ ticketStats.total }}</div>
                                <div class="text-sm text-gray-600">Total</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-yellow-600">{{ ticketStats.open }}</div>
                                <div class="text-sm text-gray-600">Open</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ ticketStats.in_progress }}</div>
                                <div class="text-sm text-gray-600">In Progress</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-green-600">{{ ticketStats.resolved }}</div>
                                <div class="text-sm text-gray-600">Resolved</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-gray-600">{{ ticketStats.closed }}</div>
                                <div class="text-sm text-gray-600">Closed</div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <h3 class="text-lg font-semibold mb-4">Recent Tickets</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="ticket in recentTickets" :key="ticket.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ticket.ticket_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.title }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="getStatusBadge(ticket.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ ticket.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="getPriorityBadge(ticket.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ ticket.priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.user?.name || 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ ticket.created_at }}</td>
                                </tr>
                                <tr v-if="recentTickets.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        No tickets found for this application
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Categories Tab -->
                <div v-if="activeTab === 'categories'">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Problem Categories ({{ categories.length }})</h3>
                        <Link
                            :href="`/admin-aplikasi/categories?aplikasi_id=${application.id}`"
                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                        >
                            Manage Categories →
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-semibold text-gray-900">{{ category.name }}</h4>
                                <span :class="getStatusBadge(category.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ category.status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ category.description }}</p>
                            <div class="text-xs text-gray-500">
                                {{ category.tickets_count }} tickets
                            </div>
                        </div>
                        <div v-if="categories.length === 0" class="col-span-full text-center py-8 text-gray-500">
                            No categories defined for this application
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Application Modal -->
        <AdminAplikasiApplicationModal
            v-if="showEditModal"
            :application="applicationWithStats"
            mode="edit"
            @close="showEditModal = false"
            @saved="onApplicationUpdated"
        />

    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import Modal from '@/Components/Common/Modal.vue';
import AdminAplikasiApplicationModal from '@/Components/Modals/AdminAplikasiApplicationModal.vue';
import { useToasts } from '@/composables/useToasts';

const page = usePage();
const { success, error, info, warning } = useToasts();

const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
    ticketStats: {
        type: Object,
        default: () => ({
            total: 0,
            open: 0,
            in_progress: 0,
            resolved: 0,
            closed: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    recentTickets: {
        type: Array,
        default: () => [],
    },
    healthMetrics: {
        type: Object,
        default: () => ({}),
    },
});

// Handle flash messages
onMounted(() => {
    const flash = page.props.flash;
    if (flash) {
        if (flash.success) {
            success({ title: 'Success', message: flash.success });
        }
        if (flash.error) {
            error({ title: 'Error', message: flash.error });
        }
        if (flash.healthCheck) {
            const hc = flash.healthCheck;
            if (hc.status === 'healthy') {
                success({ 
                    title: 'Health Check Complete', 
                    message: `Application is ${hc.status}` 
                });
            } else {
                warning({ 
                    title: 'Health Check Complete', 
                    message: `Application status: ${hc.status}. ${hc.issues.length} issues found.` 
                });
            }
        }
    }
});

const activeTab = ref('overview');
const showEditModal = ref(false);
const healthCheckLoading = ref(false);

// Computed property to merge application with stats for the modal
const applicationWithStats = computed(() => ({
    ...props.application,
    total_tickets: props.ticketStats?.total || 0,
    ticket_count: props.ticketStats?.total || 0,
    open_tickets: props.ticketStats?.open || 0,
    total_categories: props.categories?.length || 0,
    category_count: props.categories?.length || 0,
}));

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'tickets', label: 'Tickets' },
    { id: 'categories', label: 'Categories' },
];

const editApplication = () => {
    showEditModal.value = true;
};

const performHealthCheck = () => {
    healthCheckLoading.value = true;
    router.post(`/admin-aplikasi/applications/${props.application.id}/health-check`, {}, {
        onFinish: () => {
            healthCheckLoading.value = false;
        },
    });
};

const onApplicationUpdated = () => {
    showEditModal.value = false;
    router.reload();
};

const getStatusIcon = (status) => {
    const icons = {
        active: '✅',
        inactive: '⭕',
        maintenance: '🔧',
        deprecated: '⚠️',
    };
    return icons[status] || '❓';
};

const getStatusColor = (status) => {
    const colors = {
        active: 'green',
        inactive: 'gray',
        maintenance: 'yellow',
        deprecated: 'red',
    };
    return colors[status] || 'gray';
};

const getHealthIcon = (healthStatus) => {
    const icons = {
        excellent: '💚',
        good: '💛',
        fair: '🧡',
        poor: '❤️',
    };
    return icons[healthStatus] || '❓';
};

const getHealthColor = (healthStatus) => {
    const colors = {
        excellent: 'green',
        good: 'blue',
        fair: 'yellow',
        poor: 'red',
    };
    return colors[healthStatus] || 'gray';
};

const getCriticalityBadge = (criticality) => {
    const badges = {
        low: 'bg-green-100 text-green-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        critical: 'bg-red-100 text-red-800',
    };
    return badges[criticality] || 'bg-gray-100 text-gray-800';
};

const getStatusBadge = (status) => {
    const badges = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        open: 'bg-yellow-100 text-yellow-800',
        assigned: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-indigo-100 text-indigo-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getPriorityBadge = (priority) => {
    const badges = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    };
    return badges[priority] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout role="admin-aplikasi">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="`/admin-aplikasi/categories?aplikasi_id=${category.aplikasi_id}`"
                        class="text-gray-600 hover:text-gray-900 transition"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ category.name }}</h1>
                        <p class="text-gray-600 mt-1">{{ category.description }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="toggleStatus"
                        class="px-4 py-2 rounded-lg transition flex items-center"
                        :class="category.status === 'active' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        {{ category.status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button
                        @click="editCategory"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                </div>
            </div>
        </template>

        <!-- Category Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <StatCard
                title="Status"
                :value="category.status_label"
                :icon="category.status === 'active' ? '✅' : '⭕'"
                :color="category.status === 'active' ? 'green' : 'gray'"
            />
            <StatCard
                title="Total Tickets"
                :value="performance.total_tickets"
                icon="🎫"
                color="blue"
            />
            <StatCard
                title="Resolution Rate"
                :value="performance.resolution_rate.toFixed(1) + '%'"
                icon="📊"
                color="green"
            />
            <StatCard
                title="Avg Resolution Time"
                :value="formatResolutionTime(performance.avg_resolution_time)"
                icon="⏱️"
                color="yellow"
            />
            <StatCard
                title="Health Score"
                :value="(performance.health_score || 0).toFixed(0) + '/100'"
                :icon="getHealthIcon(performance.health_score)"
                :color="getHealthColor(performance.health_score)"
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
                            ? 'border-b-2 border-purple-600 text-purple-600' 
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
                        <!-- Category Details -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold mb-4">Category Details</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-600">Application</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        <Link 
                                            :href="`/admin-aplikasi/applications/${category.aplikasi.id}`"
                                            class="text-indigo-600 hover:text-indigo-800"
                                        >
                                            {{ category.aplikasi.name }}
                                        </Link>
                                    </dd>
                                </div>
                                <div v-if="category.code">
                                    <dt class="text-sm text-gray-600">Category Code</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ category.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Default Priority</dt>
                                    <dd>
                                        <span :class="getPriorityBadge(category.default_priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ category.default_priority }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="category.estimated_resolution_time">
                                    <dt class="text-sm text-gray-600">Estimated Resolution Time</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatResolutionTime(category.estimated_resolution_time) }}</dd>
                                </div>
                                <div v-if="category.sla_hours">
                                    <dt class="text-sm text-gray-600">SLA Target</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ category.sla_hours }} hours</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Created</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatDate(category.created_at) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Performance Metrics -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold mb-4">Performance Metrics</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-600">Total Tickets</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ performance.total_tickets }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Resolved Tickets</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ performance.resolved_tickets }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Resolution Rate</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ performance.resolution_rate.toFixed(1) }}%</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Average Resolution Time</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatResolutionTime(performance.avg_resolution_time) }}</dd>
                                </div>
                                <div v-if="performance.sla_compliance">
                                    <dt class="text-sm text-gray-600">SLA Compliance</dt>
                                    <dd class="text-sm font-medium" :class="performance.sla_compliance >= 90 ? 'text-green-600' : 'text-red-600'">
                                        {{ performance.sla_compliance.toFixed(1) }}%
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600">Health Score</dt>
                                    <dd>
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div 
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :class="getHealthBarColor(performance.health_score)"
                                                    :style="`width: ${performance.health_score || 0}%`"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ (performance.health_score || 0).toFixed(0) }}</span>
                                        </div>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div v-if="recommendations && recommendations.length > 0" class="mt-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-blue-900 mb-4">💡 Recommendations</h3>
                            <ul class="space-y-2">
                                <li v-for="(rec, index) in recommendations" :key="index" class="flex items-start">
                                    <span class="text-blue-600 mr-2">•</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-blue-900">{{ rec.message }}</p>
                                        <p class="text-xs text-blue-700 mt-1">{{ rec.action }}</p>
                                    </div>
                                    <span :class="getPriorityBadge(rec.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ rec.priority }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tickets Tab -->
                <div v-if="activeTab === 'tickets'">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Ticket Statistics by Status</h3>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teknisi</th>
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
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.teknisi?.name || 'Unassigned' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ ticket.created_at }}</td>
                                </tr>
                                <tr v-if="recentTickets.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                        No tickets found for this category
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Expert Teknisi Tab -->
                <div v-if="activeTab === 'experts'">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Expert Teknisi ({{ expertTeknisi.length }})</h3>
                        <button
                            @click="manageExperts"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                        >
                            Manage Experts
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="teknisi in expertTeknisi"
                            :key="teknisi.nip"
                            class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-purple-600 font-bold text-sm">
                                            {{ teknisi.name.split(' ').map(n => n[0]).join('').substring(0, 2) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ teknisi.name }}</h4>
                                        <p class="text-xs text-gray-500">{{ teknisi.nip }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Expertise Level:</span>
                                    <span class="font-medium text-gray-900">{{ teknisi.expertise_level || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Success Rate:</span>
                                    <span class="font-medium text-green-600">{{ (teknisi.success_rate || 0).toFixed(1) }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Avg Resolution:</span>
                                    <span class="font-medium text-blue-600">{{ formatResolutionTime(teknisi.avg_resolution_time) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="expertTeknisi.length === 0" class="col-span-full text-center py-8 text-gray-500">
                            No expert teknisi assigned to this category yet
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <AdminAplikasiCategoryModal
            v-if="showEditModal"
            :category="category"
            :applications="[category.aplikasi]"
            :application-id="category.aplikasi_id"
            mode="edit"
            @close="showEditModal = false"
            @saved="onCategoryUpdated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import AdminAplikasiCategoryModal from '@/Components/Modals/AdminAplikasiCategoryModal.vue';
import { useToasts } from '@/composables/useToasts';

const page = usePage();
const { success, error } = useToasts();

const props = defineProps({
    category: {
        type: Object,
        required: true,
    },
    performance: {
        type: Object,
        default: () => ({
            total_tickets: 0,
            resolved_tickets: 0,
            resolution_rate: 0,
            avg_resolution_time: 0,
            sla_compliance: 0,
            health_score: 0,
        }),
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
    recentTickets: {
        type: Array,
        default: () => [],
    },
    expertTeknisi: {
        type: Array,
        default: () => [],
    },
    recommendations: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('overview');
const showEditModal = ref(false);

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'tickets', label: 'Tickets' },
    { id: 'experts', label: 'Expert Teknisi' },
];

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
    }
});

const editCategory = () => {
    showEditModal.value = true;
};

const toggleStatus = () => {
    const newStatus = props.category.status === 'active' ? 'inactive' : 'active';
    if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this category?`)) {
        router.post(`/admin-aplikasi/categories/${props.category.id}/update-status`, {
            status: newStatus,
        });
    }
};

const manageExperts = () => {
    // TODO: Implement expert teknisi management modal
    alert('Expert teknisi management coming soon!');
};

const onCategoryUpdated = () => {
    showEditModal.value = false;
    router.reload();
};

const getHealthIcon = (score) => {
    if (score >= 80) return '💚';
    if (score >= 60) return '💛';
    if (score >= 40) return '🧡';
    return '❤️';
};

const getHealthColor = (score) => {
    if (score >= 80) return 'green';
    if (score >= 60) return 'yellow';
    if (score >= 40) return 'orange';
    return 'red';
};

const getHealthBarColor = (score) => {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    if (score >= 40) return 'bg-orange-500';
    return 'bg-red-500';
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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatResolutionTime = (minutes) => {
    if (!minutes) return 'N/A';
    const hours = Math.floor(minutes / 60);
    const mins = Math.floor(minutes % 60);
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};
</script>

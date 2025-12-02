<template>
    <AppLayout :role="role">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 100-8 0 8s12a2 2 0 100-8 0 12A10 10 0 100-8 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7.5a2.5 2.5 0 01.586.138a2.5 2.5 0 01.586-1.414 1.414-1.414z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
                        <p class="text-gray-600">System performance and ticket analysis</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="exportData('json')"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 4l4 4v8h8l-4-4V6a2 2 0 01-2 2v6a2 2 0 002-2z"/>
                        </svg>
                        Export JSON
                    </button>
                    <button
                        @click="exportData('csv')"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 4l4 4v8h8l-4-4V6a2 2 0 01-2 2v6a2 2 0 002-2z"/>
                        </svg>
                        Export CSV
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 rounded-lg">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6l-3.9-3.9a2 2 0 100-8 0 8s12a2 2 0 100-8 0 12A10 10 0 100-8 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">{{ analytics.overview.total_tickets.toLocaleString() }}</h3>
                        <p class="text-gray-600 mt-1">Total Tickets</p>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-indigo-600 h-2 rounded-full" 
                                    :style="{ width: '100%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3-1.732 1.732a4 4 0 01.464 1.464 1.464-1.464l-1.732 1.732H4a2 2 0 002-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-yellow-600">{{ analytics.overview.open_tickets.toLocaleString() }}</h3>
                        <p class="text-gray-600 mt-1">Open Tickets</p>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-yellow-500 h-2 rounded-full" 
                                    :style="{ width: calculatePercentage(analytics.overview.open_tickets, analytics.overview.total_tickets) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L8.7 1.3a1 1 0 100-8l-5.29 5.29a1 1 0 01.464 1.464-1.464z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-600">{{ analytics.overview.in_progress_tickets.toLocaleString() }}</h3>
                        <p class="text-gray-600 mt-1">In Progress</p>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-blue-500 h-2 rounded-full" 
                                    :style="{ width: calculatePercentage(analytics.overview.in_progress_tickets, analytics.overview.total_tickets) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2-2-2v4l2-2v2a2 2 0 002-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-600">{{ analytics.overview.resolved_today.toLocaleString() }}</h3>
                        <p class="text-gray-600 mt-1">Resolved Today</p>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-green-500 h-2 rounded-full" 
                                    :style="{ width: calculatePercentage(analytics.overview.resolved_today, analytics.overview.total_tickets) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Status Distribution Chart -->
                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ticket Status Distribution</h3>
                    <div class="h-64 flex items-center justify-center" v-if="!hasStatusData">
                        <p class="text-gray-500 italic">No status data available</p>
                    </div>
                    <div class="h-64" v-else>
                        <canvas ref="statusChart"></canvas>
                    </div>
                </div>

                <!-- Priority Distribution Chart -->
                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority Distribution</h3>
                    <div class="h-64 flex items-center justify-center" v-if="!hasPriorityData">
                        <p class="text-gray-500 italic">No priority data available</p>
                    </div>
                    <div class="h-64" v-else>
                        <canvas ref="priorityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Application Breakdown -->
            <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Applications by Tickets</h3>
                <div class="space-y-3" v-if="analytics.trends.application_breakdown.length > 0">
                    <div
                        v-for="item in analytics.trends.application_breakdown"
                        :key="item.name"
                        class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0"
                    >
                        <div class="w-1/3">
                            <span class="font-medium text-gray-900 truncate">{{ item.name }}</span>
                        </div>
                        <div class="w-2/3 flex items-center">
                            <div class="text-sm text-gray-600 w-16">{{ item.count }} tickets</div>
                            <div class="flex-1 ml-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                         :style="`width: ${(item.count / (analytics.trends.application_breakdown[0]?.count || 1)) * 100}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500 italic">
                    No application breakdown data available
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Top Users -->
                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Users by Tickets</h3>
                    <div class="overflow-x-auto" v-if="Object.keys(analytics.performance.top_users).length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(count, name) in analytics.performance.top_users" :key="name">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 max-w-xs truncate">{{ name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 italic">
                        No user data available
                    </div>
                </div>

                <!-- Top Teknisi -->
                <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Teknisi Performance</h3>
                    <div class="overflow-x-auto" v-if="analytics.performance.top_teknisi.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teknisi</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolved</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="teknisi in analytics.performance.top_teknisi" :key="teknisi.name">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 max-w-xs truncate">{{ teknisi.name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ teknisi.total_assigned }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ teknisi.resolved }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 font-medium">{{ Math.round(teknisi.resolution_rate) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 italic">
                        No teknisi performance data available
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        required: true,
    },
    role: {
        type: String,
        required: true,
    },
});

const statusChart = ref(null);
const priorityChart = ref(null);

// Check if charts have data
const hasStatusData = computed(() => {
    return props.analytics?.trends?.status_distribution && 
           Object.keys(props.analytics.trends.status_distribution).length > 0;
});

const hasPriorityData = computed(() => {
    return props.analytics?.trends?.priority_distribution && 
           Object.keys(props.analytics.trends.priority_distribution).length > 0;
});

// Calculate percentage helper
const calculatePercentage = (part, total) => {
    if (!total || total === 0) return 0;
    return Math.round((part / total) * 100);
};

const exportData = async (format) => {
    try {
        const response = await router.post('/analytics/export', { format });

        // Create download link
        const blob = new Blob([JSON.stringify(response.data)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `analytics-${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export failed:', error);
    }
};

const createStatusChart = () => {
    if (!statusChart.value || !hasStatusData.value) return;

    // Clean up any existing chart instance
    if (window.statusChartInstance) {
        window.statusChartInstance.destroy();
    }

    const ctx = statusChart.value.getContext('2d');
    const data = props.analytics.trends.status_distribution;

    // Define color mapping based on status
    const statusColors = {
        'open': '#3B82F6',      // blue
        'assigned': '#8B5CF6',  // purple
        'in_progress': '#F59E0B', // amber
        'waiting_user': '#F97316', // orange
        'waiting_admin': '#6366F1', // indigo
        'resolved': '#10B981',   // green
        'closed': '#6B7280',     // gray
        'cancelled': '#EF4444',  // red
        'pending': '#A8A29E'     // stone
    };

    window.statusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data).map(status => status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')),
            datasets: [{
                data: Object.values(data),
                backgroundColor: Object.keys(data).map(status => statusColors[status.toLowerCase()] || '#9CA3AF'),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
};

const createPriorityChart = () => {
    if (!priorityChart.value || !hasPriorityData.value) return;

    // Clean up any existing chart instance
    if (window.priorityChartInstance) {
        window.priorityChartInstance.destroy();
    }

    const ctx = priorityChart.value.getContext('2d');
    const data = props.analytics.trends.priority_distribution;

    // Define color mapping based on priority
    const priorityColors = {
        'low': '#10B981',        // green
        'medium': '#3B82F6',     // blue
        'high': '#F59E0B',       // amber
        'urgent': '#EF4444',     // red
    };

    window.priorityChartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: Object.keys(data).map(priority => priority.charAt(0).toUpperCase() + priority.slice(1)),
            datasets: [{
                data: Object.values(data),
                backgroundColor: Object.keys(data).map(priority => priorityColors[priority.toLowerCase()] || '#9CA3AF'),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
};

onMounted(() => {
    nextTick(() => {
        createStatusChart();
        createPriorityChart();
    });
});

// Update charts when data changes
defineExpose({
    updateCharts: () => {
        nextTick(() => {
            createStatusChart();
            createPriorityChart();
        });
    }
});
</script>
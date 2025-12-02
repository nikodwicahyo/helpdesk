<template>
    <AppLayout role="admin">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
                    <p class="text-gray-600 mt-1">Comprehensive system analytics and insights</p>
                </div>
                <div class="flex items-center space-x-4">
                    <select
                        v-model.number="selectedPeriod"
                        @change="refreshAnalytics"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option :value="7">Last 7 Days</option>
                        <option :value="30">Last 30 Days</option>
                        <option :value="90">Last 90 Days</option>
                        <option :value="365">Last Year</option>
                    </select>
                    <button
                        @click="refreshAnalytics"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                    <button
                        @click="exportAnalytics"
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

        <!-- Key Performance Indicators -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <StatCard
                title="Total Tickets"
                :value="analytics.total_tickets"
                :trend="analytics.tickets_trend"
                icon="📋"
                color="blue"
            />
            <StatCard
                title="Resolution Rate"
                :value="`${analytics.resolution_rate}%`"
                :trend="analytics.resolution_trend"
                icon="✅"
                color="green"
            />
            <StatCard
                title="Avg Response Time"
                :value="`${analytics.avg_response_time}h`"
                :trend="analytics.response_time_trend"
                icon="⚡"
                color="yellow"
            />
            <StatCard
                title="SLA Compliance"
                :value="`${analytics.sla_compliance}%`"
                :trend="analytics.sla_trend"
                icon="🎯"
                color="purple"
            />
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Ticket Trends Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Ticket Trends</h2>
                    <div class="flex items-center space-x-2">
                        <button
                            v-for="period in trendPeriods"
                            :key="period.value"
                            @click="trendPeriod = period.value"
                            :class="[
                                'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                                trendPeriod === period.value ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            {{ period.label }}
                        </button>
                    </div>
                </div>
                <div class="w-full overflow-hidden">
                    <LineChart
                        :data="analytics.ticket_trends"
                        :height="300"
                        :options="lineChartOptions"
                    />
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white rounded-lg shadow-md p-6 overflow-hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Status Distribution</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="overflow-hidden">
                        <PieChart
                            :data="analytics.status_distribution"
                            :height="250"
                            :options="pieChartOptions"
                        />
                    </div>
                    <div class="space-y-3">
                        <div v-for="item in analytics.status_breakdown" :key="item.status" class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div :class="['w-3 h-3 rounded-full', getStatusColor(item.status)]"></div>
                                <span class="text-sm font-medium text-gray-900">{{ item.status_label }}</span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900">{{ item.count }}</p>
                                <p class="text-xs text-gray-500">{{ item.percentage }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Priority Analysis -->
            <div class="bg-white rounded-lg shadow-md p-6 overflow-hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Priority Analysis</h2>
                <div class="w-full overflow-hidden">
                    <BarChart
                        :data="analytics.priority_analysis"
                        :height="300"
                        :options="barChartOptions"
                    />
                </div>
            </div>

            <!-- Application Usage -->
            <div class="bg-white rounded-lg shadow-md p-6 overflow-hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Application Usage</h2>
                <div class="space-y-3">
                    <div v-for="app in analytics.application_usage.slice(0, 6)" :key="app.id" class="flex items-center">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-900">{{ app.name }}</span>
                                <span class="text-sm text-gray-500">{{ app.ticket_count }} tickets</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${app.percentage}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teknisi Performance -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Teknisi Performance</h2>
                <div class="flex items-center space-x-4">
                    <select
                        v-model="performanceMetric"
                        @change="updatePerformanceChart"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="tickets_resolved">Tickets Resolved</option>
                        <option value="avg_resolution_time">Avg Resolution Time</option>
                        <option value="satisfaction_rate">Satisfaction Rate</option>
                        <option value="sla_compliance">SLA Compliance</option>
                    </select>
                </div>
            </div>
            <div class="w-full overflow-hidden">
                <BarChart
                    :data="analytics.teknisi_performance"
                    :height="350"
                    :options="performanceChartOptions"
                />
            </div>
        </div>

        <!-- SLA Compliance Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Overall SLA Compliance -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">SLA Compliance</h2>
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-32 h-32">
                            <circle
                                cx="64"
                                cy="64"
                                r="56"
                                stroke="#e5e7eb"
                                stroke-width="12"
                                fill="none"
                            />
                            <circle
                                cx="64"
                                cy="64"
                                r="56"
                                :stroke="getSLAColor(analytics.sla_compliance)"
                                stroke-width="12"
                                fill="none"
                                :stroke-dasharray="351.86"
                                :stroke-dashoffset="351.86 - (351.86 * analytics.sla_compliance / 100)"
                                stroke-linecap="round"
                                transform="rotate(-90 64 64)"
                            />
                        </svg>
                        <div class="absolute">
                            <p class="text-2xl font-bold text-gray-900">{{ analytics.sla_compliance }}%</p>
                            <p class="text-xs text-gray-500">Compliance</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">On Time</span>
                            <span class="font-medium text-green-600">{{ analytics.sla_on_time }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Breached</span>
                            <span class="font-medium text-red-600">{{ analytics.sla_breached }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">At Risk</span>
                            <span class="font-medium text-yellow-600">{{ analytics.sla_at_risk }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Time Analysis -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Response Times</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">First Response</p>
                            <p class="text-xs text-gray-500">Average time to first response</p>
                        </div>
                        <p class="text-lg font-bold text-indigo-600">{{ analytics.first_response_time }}h</p>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Resolution Time</p>
                            <p class="text-xs text-gray-500">Average time to resolution</p>
                        </div>
                        <p class="text-lg font-bold text-green-600">{{ analytics.resolution_time_avg }}h</p>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Handle Time</p>
                            <p class="text-xs text-gray-500">Average active handling time</p>
                        </div>
                        <p class="text-lg font-bold text-blue-600">{{ analytics.handle_time_avg }}h</p>
                    </div>
                </div>
            </div>

            <!-- Satisfaction Metrics -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">User Satisfaction</h2>
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="flex items-center justify-center space-x-1 mb-2">
                            <span v-for="star in 5" :key="star" class="text-2xl">
                                <svg v-if="star <= Math.round(analytics.avg_satisfaction)" class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg v-else class="w-6 h-6 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-gray-900">{{ analytics.avg_satisfaction }}/5.0</p>
                        <p class="text-sm text-gray-500">Average Rating</p>
                    </div>
                    <div class="space-y-2">
                        <div v-for="rating in analytics.satisfaction_breakdown" :key="rating.stars" class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600 w-8">{{ rating.stars }}★</span>
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-yellow-400 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${rating.percentage}%` }"
                                ></div>
                            </div>
                            <span class="text-sm text-gray-500 w-12 text-right">{{ rating.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Metrics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Current Metrics</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <StatCard
                    title="Active Tickets"
                    :value="realTimeMetrics.active_tickets || 0"
                    icon="🎫"
                    color="blue"
                />
                <StatCard
                    title="Queue Length"
                    :value="realTimeMetrics.queue_length || 0"
                    icon="⏳"
                    color="yellow"
                />
                <StatCard
                    title="Available Teknisi"
                    :value="realTimeMetrics.available_teknisi || 0"
                    icon="👨‍💼"
                    color="green"
                />
                <StatCard
                    title="Avg Wait Time"
                    :value="`${realTimeMetrics.avg_wait_time || 0}m`"
                    icon="⏰"
                    color="purple"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    realTimeMetrics: {
        type: Object,
        default: () => ({}),
    },
});

const selectedPeriod = ref(30);
const trendPeriod = ref('monthly');
const performanceMetric = ref('tickets_resolved');
let refreshInterval = null;

const trendPeriods = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
];

// Watch for filter changes and reload data
watch(trendPeriod, () => {
    refreshAnalytics();
});

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
    interaction: {
        mode: 'index',
        intersect: false,
    },
};

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

const performanceChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};

const refreshAnalytics = () => {
    router.get(route('admin.analytics.index'), {
        period: selectedPeriod.value,
        trend_period: trendPeriod.value,
        performance_metric: performanceMetric.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['analytics', 'realTimeMetrics'],
    });
};

const updatePerformanceChart = () => {
    router.get(route('admin.analytics.index'), {
        period: selectedPeriod.value,
        trend_period: trendPeriod.value,
        performance_metric: performanceMetric.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['analytics', 'realTimeMetrics'],
    });
};

const exportAnalytics = () => {
    const params = new URLSearchParams({
        period: selectedPeriod.value,
        trend_period: trendPeriod.value,
        performance_metric: performanceMetric.value,
    });

    // Use direct link for CSV download
    const link = document.createElement('a');
    link.href = route('admin.analytics.export') + '?' + params.toString();
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    setTimeout(() => {
        document.body.removeChild(link);
    }, 100);
};

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-yellow-400',
        assigned: 'bg-blue-400',
        in_progress: 'bg-indigo-400',
        waiting_response: 'bg-orange-400',
        resolved: 'bg-green-400',
        closed: 'bg-gray-400',
    };
    return colors[status] || 'bg-gray-400';
};

const getSLAColor = (percentage) => {
    if (percentage >= 95) return '#10b981';
    if (percentage >= 85) return '#f59e0b';
    return '#ef4444';
};

// Auto-refresh functionality has been removed
onMounted(() => {
    // Initialization code if needed
});

onUnmounted(() => {
    // Cleanup code if needed
});
</script>
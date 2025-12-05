<template>
    <AppLayout role="admin-aplikasi">
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $t('nav.analytics') }}</h1>
                            <p class="text-gray-600 text-sm sm:text-base">{{ $t('dashboard.adminAplikasi.analyticsDescription') }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <select v-model="selectedPeriod" @change="applyFilters" class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="7">{{ $t('adminAplikasi.analytics.periods.last7') }}</option>
                        <option value="30">{{ $t('adminAplikasi.analytics.periods.last30') }}</option>
                        <option value="90">{{ $t('adminAplikasi.analytics.periods.last90') }}</option>
                    </select>
                    <select v-model="selectedAppId" @change="applyFilters" class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">{{ $t('adminAplikasi.analytics.allApplications') }}</option>
                        <option v-for="app in applications" :key="app.id" :value="app.id">{{ app.name }}</option>
                    </select>
                    <button @click="exportData" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ $t('common.export') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div v-if="analytics.tickets_trend !== 0" class="flex items-center">
                        <svg class="w-4 h-4 mr-1" :class="analytics.tickets_trend > 0 ? 'text-green-300' : 'text-red-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="analytics.tickets_trend > 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'" />
                        </svg>
                        <span class="text-sm font-medium" :class="analytics.tickets_trend > 0 ? 'text-green-300' : 'text-red-300'">{{ Math.abs(analytics.tickets_trend) }}%</span>
                    </div>
                </div>
                <h3 class="text-blue-100 text-sm font-medium mb-2">{{ $t('dashboard.totalTickets') }}</h3>
                <p class="text-3xl font-bold">{{ analytics.total_tickets || 0 }}</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-green-100 text-sm font-medium mb-2">{{ $t('status.resolved') }}</h3>
                <p class="text-3xl font-bold">{{ analytics.resolved_tickets || 0 }}</p>
            </div>

            <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-yellow-100 text-sm font-medium mb-2">{{ $t('dashboard.openTickets') }}</h3>
                <p class="text-3xl font-bold">{{ analytics.open_tickets || 0 }}</p>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-purple-100 text-sm font-medium mb-2">{{ $t('dashboard.admin.resolutionRate') || $t('dashboard.user.resolutionRate') }}</h3>
                <p class="text-3xl font-bold">{{ analytics.resolution_rate || 0 }}%</p>
            </div>

            <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-indigo-100 text-sm font-medium mb-2">{{ $t('dashboard.admin.avgResolutionTime') }}</h3>
                <p class="text-3xl font-bold">{{ analytics.avg_resolution_time || 0 }}h</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Ticket Trends -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('adminAplikasi.analytics.ticketTrends') }}</h3>
                <div class="h-64">
                    <LineChart v-if="analytics.ticket_trends && analytics.ticket_trends.labels" :data="analytics.ticket_trends" />
                    <div v-else class="h-full flex items-center justify-center text-gray-400">{{ $t('common.noData') }}</div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('adminAplikasi.analytics.statusDistribution') }}</h3>
                <div class="h-64">
                    <PieChart v-if="analytics.status_distribution && analytics.status_distribution.labels" :data="analytics.status_distribution" />
                    <div v-else class="h-full flex items-center justify-center text-gray-400">{{ $t('common.noData') }}</div>
                </div>
            </div>
        </div>

        <!-- Priority & Application Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Priority Distribution -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('adminAplikasi.analytics.priorityDistribution') }}</h3>
                <div class="h-64">
                    <BarChart v-if="analytics.priority_distribution && analytics.priority_distribution.labels" :data="analytics.priority_distribution" />
                    <div v-else class="h-full flex items-center justify-center text-gray-400">{{ $t('common.noData') }}</div>
                </div>
            </div>

            <!-- Application Performance -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('adminAplikasi.analytics.applicationPerformance') }}</h3>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <div v-for="app in analytics.application_stats" :key="app.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ app.code?.substring(0, 2) || 'AP' }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ app.name }}</p>
                                <p class="text-xs text-gray-500">{{ $t('adminAplikasi.analytics.ticketsCount', { count: app.total_tickets }) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">{{ app.resolution_rate }}%</p>
                            <p class="text-xs text-gray-500">{{ $t('adminAplikasi.analytics.resolution') }}</p>
                        </div>
                    </div>
                    <div v-if="!analytics.application_stats || analytics.application_stats.length === 0" class="text-center py-8 text-gray-400">
                        {{ $t('adminAplikasi.analytics.noApplicationData') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Category & Teknisi Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Category Statistics -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('adminAplikasi.analytics.topCategories') }}</h3>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <div v-for="(cat, index) in analytics.category_stats" :key="cat.id" class="flex items-center justify-between p-3 bg-purple-50 rounded-xl">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-xs">{{ index + 1 }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ cat.name }}</p>
                                <p class="text-xs text-gray-500">{{ cat.aplikasi }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-purple-600">{{ cat.total_tickets }}</p>
                            <p class="text-xs text-gray-500">{{ $t('common.tickets') }}</p>
                        </div>
                    </div>
                    <div v-if="!analytics.category_stats || analytics.category_stats.length === 0" class="text-center py-8 text-gray-400">
                        {{ $t('adminAplikasi.analytics.noCategoryData') }}
                    </div>
                </div>
            </div>

            <!-- Teknisi Performance -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('adminAplikasi.analytics.teknisiPerformance') }}</h3>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <div v-for="teknisi in analytics.teknisi_performance" :key="teknisi.nip" class="flex items-center justify-between p-3 bg-indigo-50 rounded-xl">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ teknisi.name?.split(' ').map(n => n[0]).join('').substring(0, 2) || 'T' }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ teknisi.name }}</p>
                                <p class="text-xs text-gray-500">{{ $t('adminAplikasi.analytics.resolvedThisPeriod', { count: teknisi.period_resolved }) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-indigo-600">{{ teknisi.resolution_rate }}%</p>
                            <p class="text-xs text-gray-500">
                                <span v-if="teknisi.rating > 0" class="flex items-center justify-end">
                                    <svg class="w-3 h-3 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    {{ teknisi.rating }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div v-if="!analytics.teknisi_performance || analytics.teknisi_performance.length === 0" class="text-center py-8 text-gray-400">
                        {{ $t('adminAplikasi.analytics.noTeknisiData') }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    analytics: {
        type: Object,
        default: () => ({
            total_tickets: 0,
            resolved_tickets: 0,
            open_tickets: 0,
            resolution_rate: 0,
            avg_resolution_time: 0,
            tickets_trend: 0,
            ticket_trends: { labels: [], datasets: [] },
            status_distribution: { labels: [], datasets: [] },
            priority_distribution: { labels: [], datasets: [] },
            application_stats: [],
            category_stats: [],
            teknisi_performance: [],
        }),
    },
    applications: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            period: 30,
            aplikasi_id: null,
        }),
    },
    period: {
        type: Number,
        default: 30,
    },
});

// Safe computed analytics with null checks
const analytics = computed(() => ({
    total_tickets: props.analytics?.total_tickets ?? 0,
    resolved_tickets: props.analytics?.resolved_tickets ?? 0,
    open_tickets: props.analytics?.open_tickets ?? 0,
    resolution_rate: props.analytics?.resolution_rate ?? 0,
    avg_resolution_time: props.analytics?.avg_resolution_time ?? 0,
    tickets_trend: props.analytics?.tickets_trend ?? 0,
    resolution_trend: props.analytics?.resolution_trend ?? 0,
    ticket_trends: props.analytics?.ticket_trends && props.analytics.ticket_trends.labels?.length > 0 
        ? props.analytics.ticket_trends 
        : null,
    status_distribution: props.analytics?.status_distribution && props.analytics.status_distribution.labels?.length > 0 
        ? props.analytics.status_distribution 
        : null,
    priority_distribution: props.analytics?.priority_distribution && props.analytics.priority_distribution.labels?.length > 0 
        ? props.analytics.priority_distribution 
        : null,
    application_stats: props.analytics?.application_stats ?? [],
    category_stats: props.analytics?.category_stats ?? [],
    teknisi_performance: props.analytics?.teknisi_performance ?? [],
}));

const selectedPeriod = ref(props.filters.period || props.period || 30);
const selectedAppId = ref(props.filters.aplikasi_id || '');

const applyFilters = () => {
    const params = {
        period: selectedPeriod.value,
    };
    
    if (selectedAppId.value) {
        params.aplikasi_id = selectedAppId.value;
    }

    router.get(route('admin-aplikasi.analytics.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportData = () => {
    const params = new URLSearchParams();
    params.set('period', selectedPeriod.value);
    
    if (selectedAppId.value) {
        params.set('aplikasi_id', selectedAppId.value);
    }

    window.open(route('admin-aplikasi.analytics.export') + '?' + params.toString(), '_blank');
};
</script>

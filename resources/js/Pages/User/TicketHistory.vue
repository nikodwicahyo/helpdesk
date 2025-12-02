<template>
    <AppLayout role="user">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $t('nav.history') }}</h1>
                    <p class="text-gray-600 mt-1">{{ $t('history.viewTicketHistory') }}</p>
                </div>
                <button
                    @click="exportHistory"
                    :disabled="exportLoading"
                    :class="[
                        'px-4 py-2 rounded-lg font-medium transition flex items-center',
                        exportLoading
                            ? 'bg-gray-400 text-gray-200 cursor-not-allowed'
                            : 'bg-green-600 text-white hover:bg-green-700'
                    ]"
                >
                    <svg
                        v-if="exportLoading"
                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ exportLoading ? $t('common.exporting') : $t('action.export') }}
                </button>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">{{ $t('activityLog.filters') }}</h2>
                <button
                    @click="toggleFilters"
                    class="text-indigo-600 hover:text-indigo-800 font-medium text-sm"
                >
                    {{ showFilters ? $t('common.hide') : $t('common.show') }} {{ $t('activityLog.filters') }}
                </button>
            </div>

            <div v-show="showFilters" class="space-y-4">
                <!-- Search Bar -->
                <div class="relative">
                    <input
                        v-model="filters.search"
                        @input="debouncedSearch"
                        type="text"
                        :placeholder="$t('search.searchHistory')"
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                    <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Ticket Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('nav.tickets') }}</label>
                        <select
                            v-model="filters.ticket_id"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ $t('activityLog.allEntities') }}</option>
                            <option v-for="ticket in userTickets" :key="ticket.id" :value="ticket.id">
                                {{ ticket.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Action Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('activityLog.action') }}</label>
                        <select
                            v-model="filters.action_type"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">{{ $t('activityLog.allEntities') }}</option>
                            <option v-for="action in actionTypes" :key="action.value" :value="action.value">
                                {{ action.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('common.from') }}</label>
                        <input
                            v-model="filters.date_from"
                            @change="applyFilters"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('common.to') }}</label>
                        <input
                            v-model="filters.date_to"
                            @change="applyFilters"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <span class="text-sm text-gray-600">
                        {{ $t('common.showing') }} {{ history.from || 0 }}-{{ history.to || 0 }} {{ $t('common.of') }} {{ history.total || 0 }} {{ $t('history.entries') }}
                    </span>
                    <button
                        @click="clearFilters"
                        class="text-gray-600 hover:text-gray-800 font-medium text-sm"
                    >
                        {{ $t('action.clearAll') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- History Timeline -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div v-if="loading" class="p-12 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-500">{{ $t('common.loading') }}</p>
            </div>

            <div v-else-if="history.data.length === 0" class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('history.noHistory') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $t('history.noHistoryDescription') }}</p>
            </div>

            <div v-else class="divide-y divide-gray-200">
                <div
                    v-for="item in history.data"
                    :key="item.id"
                    class="p-6 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-start space-x-4">
                        <!-- Icon -->
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                            getActionColor(item.action)
                        ]">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActionIcon(item.action)"/>
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ item.action_label }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <Link
                                            :href="route('user.tickets.show', item.ticket.id)"
                                            class="text-indigo-600 hover:text-indigo-800 font-medium"
                                        >
                                            {{ item.ticket.ticket_number }}
                                        </Link>
                                        - {{ item.ticket.title }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">{{ item.time_ago }}</p>
                                    <p class="text-xs text-gray-400">{{ item.formatted_created_at }}</p>
                                </div>
                            </div>

                            <!-- Description -->
                            <p v-if="item.description" class="text-sm text-gray-600 mt-2">
                                {{ item.description }}
                            </p>

                            <!-- Value Changes -->
                            <div v-if="item.old_value || item.new_value" class="mt-3 flex items-center space-x-2 text-sm">
                                <span v-if="item.old_value" class="px-2 py-1 bg-red-100 text-red-800 rounded">
                                    {{ item.old_value }}
                                </span>
                                <svg v-if="item.old_value && item.new_value" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                <span v-if="item.new_value" class="px-2 py-1 bg-green-100 text-green-800 rounded">
                                    {{ item.new_value }}
                                </span>
                            </div>

                            <!-- Actor Info -->
                            <div v-if="item.actor" class="mt-2 flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $t('history.by') }} {{ item.actor.name }} ({{ item.actor.type }})
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="history.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <SimplePagination
                    :data="history"
                    :label="$t('history.entries')"
                    @page-changed="handlePageChange"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { route } from 'ziggy-js';
import { debounce } from 'lodash';
import AppLayout from '@/Layouts/AppLayout.vue';
import SimplePagination from '@/Components/Common/SimplePagination.vue';

const { t } = useI18n();

const props = defineProps({
    history: {
        type: Object,
        required: true,
    },
    userTickets: {
        type: Array,
        default: () => [],
    },
    actionTypes: {
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

const filters = ref({
    search: props.filters.search || '',
    ticket_id: props.filters.ticket_id || '',
    action_type: props.filters.action_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const applyFilters = () => {
    router.get(route('user.history.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = {
        search: '',
        ticket_id: '',
        action_type: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

const exportHistory = async () => {
    if (exportLoading.value) return;

    try {
        exportLoading.value = true;
        const params = new URLSearchParams(filters.value);
        const exportUrl = route('user.history.export') + '?' + params.toString();
        window.open(exportUrl, '_blank');
    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export history. Please try again.');
    } finally {
        exportLoading.value = false;
    }
};

const handlePageChange = (page) => {
    router.get(route('user.history.index'), { ...filters.value, page }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getActionColor = (action) => {
    const colors = {
        created: 'bg-blue-500',
        status_changed: 'bg-indigo-500',
        priority_changed: 'bg-orange-500',
        assigned: 'bg-purple-500',
        comment_added: 'bg-green-500',
        resolved: 'bg-emerald-500',
        closed: 'bg-gray-500',
        reopened: 'bg-yellow-500',
    };
    return colors[action] || 'bg-gray-500';
};

const getActionIcon = (action) => {
    const icons = {
        created: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        status_changed: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        priority_changed: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        assigned: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        comment_added: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        resolved: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        closed: 'M5 13l4 4L19 7',
        reopened: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    };
    return icons[action] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
};
</script>

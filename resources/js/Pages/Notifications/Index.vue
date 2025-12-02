<template>
    <AppLayout :role="role">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.586-1.586a2 2 0 01-2.828 0L15 17zm0 0v-2.586a2 2 0 00-.586-1.414l-1.586-1.586A2 2 0 0112 11.586V6a3 3 0 10-6 0v5.586a2 2 0 01-.586 1.414l-1.586 1.586A2 2 0 003 15.586V17h12zm0 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                        <p class="text-gray-600">Manage and view your system notifications</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="markAllAsRead"
                        :disabled="processing"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mark All as Read
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-xxl mx-auto sm:px-2 lg:px-2">
            <!-- Statistics for Admin Users -->
            <div v-if="statistics && Object.keys(statistics).length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Notifications</p>
                            <p class="text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center relative">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.586-1.586a2 2 0 01-2.828 0L15 17zm0 0v-2.586a2 2 0 00-.586-1.414l-1.586-1.586A2 2 0 0112 11.586V6a3 3 0 10-6 0v5.586a2 2 0 01-.586 1.414l-1.586 1.586A2 2 0 003 15.586V17h12zm0 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Unread</p>
                            <p class="text-2xl font-bold text-red-600">{{ statistics.unread }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Read</p>
                            <p class="text-2xl font-bold text-green-600">{{ statistics.read }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Today</p>
                            <p class="text-2xl font-bold text-purple-600">{{ statistics.today }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow-lg rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filter Notifications</h3>
                    <div class="flex flex-wrap gap-4">
                        <select
                            v-model="filters.type"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">All Types</option>
                            <option value="ticket_created">Ticket Created</option>
                            <option value="ticket_assigned">Ticket Assigned</option>
                            <option value="ticket_updated">Ticket Updated</option>
                            <option value="ticket_resolved">Ticket Resolved</option>
                            <option value="system">System</option>
                        </select>

                        <select
                            v-model="filters.read"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">All Status</option>
                            <option value="unread">Unread Only</option>
                            <option value="read">Read Only</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white shadow-lg rounded-lg">
                <div v-if="notifications.data.length === 0" class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13.5L18.5 15m0 0l-1.5 1.5m1.5-1.5l1.5 1.5m-1.5-1.5l-1.5-1.5M15 17h5l-1.586-1.586a2 2 0 01-2.828 0L15 17zm0 0v-2.586a2 2 0 00-.586-1.414l-1.586-1.586A2 2 0 0112 11.586V6a3 3 0 10-6 0v5.586a2 2 0 01-.586 1.414l-1.586 1.586A2 2 0 003 15.586V17h12zm0 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">No notifications found</h3>
                    <p class="text-gray-600">You're all caught up! Check back later for new updates.</p>
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        class="p-6 hover:bg-gray-50 transition-colors duration-200 cursor-pointer"
                        :class="{ 'bg-blue-50': !notification.read_at, 'opacity-75': notification.read_at }"
                        @click="viewNotification(notification.id)"
                    >
                        <div class="flex items-start space-x-4">
                            <!-- Notification Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <div 
                                    class="w-10 h-10 rounded-full flex items-center justify-center"
                                    :class="getNotificationIconBgClass(notification.type)"
                                >
                                    <svg 
                                        class="w-6 h-6" 
                                        :class="getNotificationIconColorClass(notification.type)"
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getNotificationIconPath(notification.type)"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Notification Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        {{ notification.title }}
                                    </h4>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getNotificationTypeClass(notification.type)"
                                        >
                                            {{ getNotificationTypeLabel(notification.type) }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ formatTimeAgo(notification.created_at) }}
                                        </span>
                                    </div>
                                    <!-- Delete Button -->
                                    <button
                                        @click.stop="deleteNotification(notification.id)"
                                        class="text-red-600 hover:text-red-800 transition-colors duration-200"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 2.467A2 2 0 0116.138 12H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                </div>

                                <!-- Notification Message -->
                                <p class="text-sm text-gray-700 mt-2">
                                    {{ notification.message }}
                                </p>

                                <!-- Action Button (if applicable) -->
                                <div v-if="notification.action_url" class="mt-3">
                                    <button
                                        type="button"
                                        @click.stop="viewNotification(notification.id)"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        View Details
                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links.length > 3" class="mt-6">
                    <SimplePagination
                        :data="notifications"
                        label="notifikasi"
                        @page-changed="handlePageChange"
                    />
                </div>
            </div>

        <!-- Notification Detail Modal -->
        <NotificationDetailModal
            :show="showNotificationModal"
            :notification-id="selectedNotificationId"
            :role="role"
            @close="closeNotificationModal"
            @notification-updated="handleNotificationUpdated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SimplePagination from '@/Components/Common/SimplePagination.vue';
import NotificationDetailModal from '@/Components/Notifications/NotificationDetailModal.vue';

const props = defineProps({
    notifications: {
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
    unreadCount: {
        type: Number,
        required: true,
    },
    statistics: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    }
});

const processing = ref(false);
const filters = ref({
    type: props.filters?.type || '',
    read: props.filters?.read || '',
});

const applyFilters = () => {
    const routePrefix = getRoutePrefix();
    const params = {
        page: 1, // Reset to first page on filter change
        type: filters.value.type,
    };

    // Only add is_read parameter if a specific status is selected
    if (filters.value.read === 'read') {
        params.is_read = 1;
    } else if (filters.value.read === 'unread') {
        params.is_read = 0;
    }

    router.visit(`${routePrefix}/notifications`, {
        data: params,
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch filters for changes
watch(filters, () => {
    applyFilters();
}, { deep: true });

// Modal state
const showNotificationModal = ref(false);
const selectedNotificationId = ref(null);

const isCurrentPage = (url) => {
    return props.notifications.current_page_url === url;
};

const handlePageChange = (page) => {
    const routePrefix = getRoutePrefix();
    router.visit(`${routePrefix}/notifications?page=${page}`, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getNotificationTypeLabel = (type) => {
    const labels = {
        'ticket_created': 'Ticket Created',
        'ticket_assigned': 'Ticket Assigned',
        'ticket_updated': 'Ticket Updated',
        'ticket_resolved': 'Ticket Resolved',
        'ticket_comment': 'Ticket Comment',
        'ticket_escalated': 'Ticket Escalated',
        'ticket_response_required': 'Response Required',
        'urgent_ticket': 'Urgent Ticket',
        'system_maintenance': 'System Maintenance',
        'system_backup': 'System Backup',
        'daily_report': 'Daily Report',
        'system': 'System',
    };
    return labels[type] || type;
};

const getNotificationIconPath = (type) => {
    const icons = {
        // Ticket Created - Plus icon in circle
        'ticket_created': 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
        // Ticket Assigned - User with checkmark
        'ticket_assigned': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        // Ticket Updated - Refresh/sync icon
        'ticket_updated': 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        // Ticket Resolved - Checkmark in circle
        'ticket_resolved': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        // Ticket Comment - Chat bubble
        'ticket_comment': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        // Ticket Escalated - Arrow up in circle
        'ticket_escalated': 'M5 10l7-7m0 0l7 7m-7-7v18',
        // Response Required - Exclamation in circle
        'ticket_response_required': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        // Urgent Ticket - Lightning bolt
        'urgent_ticket': 'M13 10V3L4 14h7v7l9-11h-7z',
        // System Maintenance - Wrench/tool icon
        'system_maintenance': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        // System Backup - Database icon
        'system_backup': 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
        // Daily Report - Document with chart
        'daily_report': 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        // System/Default - Bell icon
        'system': 'M15 17h5l-1.586-1.586a2 2 0 01-2.828 0L15 17zm0 0v-2.586a2 2 0 00-.586-1.414l-1.586-1.586A2 2 0 0112 11.586V6a3 3 0 10-6 0v5.586a2 2 0 01-.586 1.414l-1.586 1.586A2 2 0 003 15.586V17h12zm0 0v1a3 3 0 11-6 0v-1m6 0H9',
    };
    return icons[type] || icons['system'];
};

const getNotificationIconBgClass = (type) => {
    const classes = {
        'ticket_created': 'bg-blue-100',
        'ticket_assigned': 'bg-yellow-100',
        'ticket_updated': 'bg-indigo-100',
        'ticket_resolved': 'bg-green-100',
        'ticket_comment': 'bg-purple-100',
        'ticket_escalated': 'bg-orange-100',
        'ticket_response_required': 'bg-pink-100',
        'urgent_ticket': 'bg-red-100',
        'system_maintenance': 'bg-gray-100',
        'system_backup': 'bg-teal-100',
        'daily_report': 'bg-cyan-100',
        'system': 'bg-indigo-100',
    };
    return classes[type] || 'bg-indigo-100';
};

const getNotificationIconColorClass = (type) => {
    const classes = {
        'ticket_created': 'text-blue-600',
        'ticket_assigned': 'text-yellow-600',
        'ticket_updated': 'text-indigo-600',
        'ticket_resolved': 'text-green-600',
        'ticket_comment': 'text-purple-600',
        'ticket_escalated': 'text-orange-600',
        'ticket_response_required': 'text-pink-600',
        'urgent_ticket': 'text-red-600',
        'system_maintenance': 'text-gray-600',
        'system_backup': 'text-teal-600',
        'daily_report': 'text-cyan-600',
        'system': 'text-indigo-600',
    };
    return classes[type] || 'text-indigo-600';
};

const getNotificationTypeClass = (type) => {
    const classes = {
        'ticket_created': 'bg-blue-100 text-blue-800',
        'ticket_assigned': 'bg-yellow-100 text-yellow-800',
        'ticket_updated': 'bg-indigo-100 text-indigo-800',
        'ticket_resolved': 'bg-green-100 text-green-800',
        'ticket_comment': 'bg-purple-100 text-purple-800',
        'ticket_escalated': 'bg-orange-100 text-orange-800',
        'ticket_response_required': 'bg-pink-100 text-pink-800',
        'urgent_ticket': 'bg-red-100 text-red-800',
        'system_maintenance': 'bg-gray-100 text-gray-800',
        'system_backup': 'bg-teal-100 text-teal-800',
        'daily_report': 'bg-cyan-100 text-cyan-800',
        'system': 'bg-gray-100 text-gray-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const formatTimeAgo = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffTime = Math.floor((now - date) / 1000); // Convert to seconds

    if (diffTime < 60) {
        return `${diffTime} seconds ago`;
    } else if (diffTime < 3600) {
        const minutes = Math.floor(diffTime / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diffTime < 86400) {
        const hours = Math.floor(diffTime / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else {
        const days = Math.floor(diffTime / 86400);
        return `${days} day${days > 1 ? 's' : ''} ago`;
    }
};

const viewNotification = (id) => {
    selectedNotificationId.value = id;
    showNotificationModal.value = true;
};

const getRoutePrefix = () => {
    if (props.role === 'admin_helpdesk') {
        return '/admin';
    } else if (props.role === 'admin_aplikasi') {
        return '/admin-aplikasi';
    } else if (props.role === 'teknisi') {
        return '/teknisi';
    } else if (props.role === 'user') {
        return '/user';
    }
    return '';
};

const markAsRead = async (id) => {
    try {
        const routePrefix = getRoutePrefix();
        await router.post(`${routePrefix}/notifications/${id}/mark-read`);
        // Update the notification in the local state
        const notification = props.notifications.data.find(n => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    processing.value = true;
    try {
        const routePrefix = getRoutePrefix();
        await router.post(`${routePrefix}/notifications/mark-all-read`);
        // Update all notifications in local state
        props.notifications.data.forEach(notification => {
            notification.read_at = new Date().toISOString();
        });
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    } finally {
        processing.value = false;
    }
};

const deleteNotification = async (id) => {
    if (confirm('Are you sure you want to delete this notification?')) {
        try {
            const routePrefix = getRoutePrefix();
            await router.delete(`${routePrefix}/notifications/${id}`);
            // Remove the notification from the local state
            const index = props.notifications.data.findIndex(n => n.id === id);
            if (index > -1) {
                props.notifications.data.splice(index, 1);
            }
        } catch (error) {
            console.error('Failed to delete notification:', error);
        }
    }
};

const handleNotificationUpdated = (updatedData) => {
    // Handle notification update from modal
    if (updatedData.deleted) {
        // Remove deleted notification from list
        const index = props.notifications.data.findIndex(n => n.id === updatedData.id);
        if (index > -1) {
            props.notifications.data.splice(index, 1);
        }
    } else if (updatedData.loadNotificationId) {
        // Load different notification in modal
        selectedNotificationId.value = updatedData.loadNotificationId;
    } else {
        // Update notification data (mark as read, etc.)
        const index = props.notifications.data.findIndex(n => n.id === updatedData.id);
        if (index > -1) {
            // Update the notification data
            Object.assign(props.notifications.data[index], updatedData);
        }
    }

    // Emit global event to update notification bell
    const event = new CustomEvent('notifications-updated', { detail: { updatedData } });
    window.dispatchEvent(event);
};

const closeNotificationModal = () => {
    showNotificationModal.value = false;
    selectedNotificationId.value = null;
};

// Listen for global notification click events from navbar
const handleGlobalNotificationClick = (event) => {
    const { notificationId } = event.detail;
    selectedNotificationId.value = notificationId;
    showNotificationModal.value = true;
};

onMounted(() => {
    window.addEventListener('open-notification-modal', handleGlobalNotificationClick);
});

onUnmounted(() => {
    window.removeEventListener('open-notification-modal', handleGlobalNotificationClick);
});
</script>
<template>
    <Modal
        :show="show"
        @close="close"
        title="Notification Details"
        size="lg"
        :close-on-backdrop="true"
        :close-on-escape="true"
    >
        <template v-if="notification">
            <!-- Notification Header -->
            <div
                class="bg-gradient-to-r from-slate-700 to-slate-900 -mx-6 -mt-6 px-6 pt-6 pb-4 mb-6 rounded-t-lg"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <h3
                                class="text-xl font-bold text-white drop-shadow-sm"
                            >
                                {{ notification.title }}
                            </h3>
                            <div class="flex items-center flex-wrap gap-2 mt-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-90 text-slate-800 border border-white border-opacity-30"
                                >
                                    {{
                                        getNotificationTypeLabel(
                                            notification.type
                                        )
                                    }}
                                </span>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="
                                        getPriorityClass(notification.priority)
                                    "
                                >
                                    {{
                                        notification.priority?.toUpperCase() ||
                                        "NORMAL"
                                    }}
                                </span>
                                <span
                                    v-if="!notification.read_at"
                                    class="bg-amber-500 text-white px-2 py-1 rounded text-xs font-medium shadow-sm"
                                >
                                    UNREAD
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Content -->
            <div class="space-y-6">
                <!-- Message -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">
                        Message
                    </h4>
                    <p class="text-gray-700 leading-relaxed">
                        {{ notification.message }}
                    </p>
                </div>

                <!-- Additional Data -->
                <div
                    v-if="
                        notification.data &&
                        Object.keys(notification.data).length > 0
                    "
                >
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">
                        Additional Information
                    </h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 gap-3">
                            <div
                                v-for="(value, key) in notification.data"
                                :key="key"
                                class="flex items-start space-x-2"
                            >
                                <span
                                    class="text-sm font-medium text-gray-600 min-w-0 flex-shrink-0"
                                    >{{ formatKey(key) }}:</span
                                >
                                <span
                                    class="text-sm text-gray-900 break-words"
                                    >{{ formatValue(value) }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Information -->
                <div
                    v-if="notification.ticket_id"
                    class="bg-blue-50 border border-blue-200 rounded-lg p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h4
                                class="text-sm font-semibold text-blue-900 mb-1"
                            >
                                Related Ticket
                            </h4>
                            <p class="text-sm text-blue-700">
                                Ticket ID: #{{ notification.ticket_id }}
                            </p>
                            <p
                                v-if="notification.data?.ticket_number"
                                class="text-sm text-blue-700"
                            >
                                Ticket Number:
                                {{ notification.data.ticket_number }}
                            </p>
                        </div>
                        <a
                            v-if="notification.ticket_id"
                            :href="getTicketUrl(notification.ticket_id)"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                            View Ticket
                            <svg
                                class="w-4 h-4 ml-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">
                        Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Created:</span>
                            <p class="font-medium text-gray-900">
                                {{ formatDateTime(notification.created_at) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-600">Sent:</span>
                            <p class="font-medium text-gray-900">
                                {{ formatDateTime(notification.sent_at) }}
                            </p>
                        </div>
                        <div v-if="notification.read_at">
                            <span class="text-gray-600">Read:</span>
                            <p class="font-medium text-gray-900">
                                {{ formatDateTime(notification.read_at) }}
                            </p>
                        </div>
                        <div v-else>
                            <span class="text-gray-600">Status:</span>
                            <p class="font-medium text-blue-600">Unread</p>
                        </div>
                    </div>
                    <div class="mt-4 text-sm" v-if="notification.triggered_by || notification.triggered_by_nip">
                        <span class="text-gray-600">Triggered by:</span>
                        <p class="font-medium text-gray-900">
                            {{
                                notification.triggered_by?.name ||
                                notification.triggered_by_nip
                            }}
                            <span v-if="notification.triggered_by?.type || notification.triggered_by_type">
                                ({{
                                    notification.triggered_by?.type ||
                                    notification.triggered_by_type
                                }})
                            </span>
                        </p>
                    </div>
                    <div class="mt-2 text-sm">
                        <span class="text-gray-600">Recipient:</span>
                        <p class="font-medium text-gray-900">
                            {{ getRecipientInfo(notification) }}
                        </p>
                    </div>
                </div>

                <!-- Related Notifications -->
                <div
                    v-if="
                        relatedNotifications && relatedNotifications.length > 0
                    "
                >
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">
                        Related Notifications
                    </h4>
                    <div class="space-y-3">
                        <div
                            v-for="related in relatedNotifications"
                            :key="related.id"
                            class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors duration-200 cursor-pointer"
                            @click="viewRelatedNotification(related.id)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h5
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ related.title }}
                                    </h5>
                                    <p
                                        class="text-sm text-gray-600 mt-1 line-clamp-2"
                                    >
                                        {{ related.message }}
                                    </p>
                                    <div
                                        class="flex items-center space-x-2 mt-2"
                                    >
                                        <span class="text-xs text-gray-500">{{
                                            formatDateTime(related.created_at)
                                        }}</span>
                                        <span
                                            v-if="!related.read_at"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                                        >
                                            Unread
                                        </span>
                                    </div>
                                </div>
                                <svg
                                    class="w-5 h-5 text-gray-400 ml-3 mt-1 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Loading State -->
        <div v-else-if="loading" class="flex items-center justify-center py-12">
            <div
                class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
            ></div>
            <span class="ml-3 text-gray-600"
                >Loading notification details...</span
            >
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
            <div class="text-red-600 mb-4">
                <svg
                    class="w-12 h-12 mx-auto"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                Failed to Load Notification
            </h3>
            <p class="text-gray-600 mb-4">{{ error }}</p>
            <button
                @click="fetchNotificationDetails"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
            >
                Try Again
            </button>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
                    <button
                        v-if="notification && !notification.read_at"
                        @click="markAsRead"
                        :disabled="processing"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!processing">Mark as Read</span>
                        <span v-else>Processing...</span>
                    </button>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        v-if="notification"
                        @click="deleteNotification"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        Delete
                    </button>
                    <button
                        @click="close"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Modal from "@/Components/Common/Modal.vue";
import axios from "axios";
import { useDateFormatter } from "@/composables/useDateFormatter";

// Use centralized date formatter
const { formatDate: formatDateUtil, formatToLocal } = useDateFormatter();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    notificationId: {
        type: Number,
        default: null,
    },
    role: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["close", "notification-updated"]);

const notification = ref(null);
const relatedNotifications = ref([]);
const loading = ref(false);
const error = ref("");
const processing = ref(false);

const fetchNotificationDetails = async () => {
    if (!props.notificationId) return;

    loading.value = true;
    error.value = "";

    try {
        // Use the dedicated API endpoint for details
        const response = await axios.get(`/api/notifications/${props.notificationId}/details`);

        // The API response wraps data in 'data', and our controller wraps it in another layer or directly
        // ApiController returns: successResponse(['notification' => ..., 'relatedNotifications' => ...])
        // successResponse usually puts data in 'data' field.
        // So response.data is the axios body, response.data.data is the payload.
        
        const payload = response.data.data || response.data;
        
        notification.value = payload.notification;
        relatedNotifications.value = payload.relatedNotifications || [];

        // Emit notification updated event to refresh the list
        emit("notification-updated", payload.notification);
    } catch (err) {
        console.error("Error fetching notification details:", err);
        error.value = "Unable to load notification details. Please try again.";
    } finally {
        loading.value = false;
    }
};

const close = () => {
    emit("close");
};

const markAsRead = async () => {
    if (!notification.value || notification.value.read_at) return;

    processing.value = true;
    try {
        await axios.post(`/api/notifications/${notification.value.id}/mark-read`);
        
        notification.value.read_at = new Date().toISOString();
        notification.value.is_read = true;
        
        emit("notification-updated", notification.value);
    } catch (err) {
        console.error("Error marking notification as read:", err);
    } finally {
        processing.value = false;
    }
};

const deleteNotification = async () => {
    if (!notification.value) return;

    if (!confirm("Are you sure you want to delete this notification?")) return;

    try {
        await axios.delete(`/api/notifications/${notification.value.id}`);
        
        emit("notification-updated", {
            id: notification.value.id,
            deleted: true,
        });
        close();
    } catch (err) {
        console.error("Error deleting notification:", err);
        alert("Failed to delete notification. Please try again.");
    }
};

const viewRelatedNotification = (id) => {
    emit("close");
    // Emit event to parent to load different notification
    setTimeout(() => {
        emit("notification-updated", { loadNotificationId: id });
    }, 300);
};

const getNotificationTypeLabel = (type) => {
    const labels = {
        ticket_created: "Ticket Created",
        ticket_assigned: "Ticket Assigned",
        ticket_updated: "Ticket Updated",
        ticket_resolved: "Ticket Resolved",
        ticket_comment: "Ticket Comment",
        ticket_escalated: "Ticket Escalated",
        ticket_response_required: "Response Required",
        urgent_ticket: "Urgent Ticket",
        system_maintenance: "System Maintenance",
        system_backup: "System Backup",
        daily_report: "Daily Report",
        system: "System",
    };
    return labels[type] || type;
};

const getPriorityClass = (priority) => {
    const classes = {
        urgent: "bg-red-600 text-white shadow-sm",
        high: "bg-orange-600 text-white shadow-sm",
        medium: "bg-amber-600 text-white shadow-sm",
        low: "bg-green-600 text-white shadow-sm",
    };
    return classes[priority] || "bg-slate-600 text-white shadow-sm";
};

const formatKey = (key) => {
    if (!key || typeof key !== 'string') {
        return String(key || '');
    }
    return key
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const formatValue = (value) => {
    if (typeof value === "object") {
        return JSON.stringify(value, null, 2);
    }
    
    // Check if value looks like an ISO date string
    if (typeof value === "string" && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value)) {
        return formatDateUtil(value);
    }
    
    return value;
};

const formatDateTime = (timestamp) => {
    if (!timestamp) return "N/A";
    return formatToLocal(timestamp);
};

const getRecipientInfo = (notification) => {
    const typeMap = {
        "App\\Models\\User": "User",
        "App\\Models\\AdminHelpdesk": "Admin Helpdesk",
        "App\\Models\\AdminAplikasi": "Admin Aplikasi",
        "App\\Models\\Teknisi": "Teknisi",
    };
    const type =
        typeMap[notification.notifiable_type] || notification.notifiable_type;
    return `${type} (ID: ${notification.notifiable_id})`;
};

const getTicketUrl = (ticketId) => {
    const prefix = props.role === 'admin_helpdesk' || props.role === 'admin_aplikasi' ? 'admin' : props.role;
    return `/${prefix}/tickets/${ticketId}`;
};

// Watch for notificationId changes
watch(
    () => props.notificationId,
    (newId) => {
        if (newId && props.show) {
            fetchNotificationDetails();
        }
    }
);

// Watch for show changes
watch(
    () => props.show,
    (newShow) => {
        if (newShow && props.notificationId) {
            fetchNotificationDetails();
        } else if (!newShow) {
            // Reset state when modal closes
            notification.value = null;
            relatedNotifications.value = [];
            error.value = "";
            loading.value = false;
            processing.value = false;
        }
    }
);
</script>

<style scoped>
.line-clamp-2 {
    line-clamp: 2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

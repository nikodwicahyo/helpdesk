<template>
    <div
        class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition-all duration-200 hover:border-indigo-300"
        :class="{ 'ring-2 ring-indigo-500': isDragging }"
        draggable="true"
        @dragstart="$emit('dragstart', $event)"
        @dragend="isDragging = false"
    >
        <!-- Ticket Header -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-mono text-gray-500">{{ ticket.ticket_number }}</span>
                <div v-if="ticket.is_overdue" class="w-2 h-2 bg-red-500 rounded-full" title="Overdue"></div>
            </div>
            <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(ticket.priority)]">
                {{ ticket.priority_label }}
            </span>
        </div>

        <!-- Ticket Title -->
        <h4 class="font-medium text-gray-900 text-sm mb-2 line-clamp-2">
            {{ ticket.title }}
        </h4>

        <!-- Ticket Description -->
        <p class="text-xs text-gray-600 mb-3 line-clamp-3">
            {{ ticket.description }}
        </p>

        <!-- Application & Category -->
        <div v-if="ticket.aplikasi || ticket.kategori_masalah" class="mb-3 space-y-1">
            <div v-if="ticket.aplikasi" class="flex items-center text-xs text-gray-500">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                {{ ticket.aplikasi.name }}
            </div>
            <div v-if="ticket.kategori_masalah" class="flex items-center text-xs text-gray-500">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                {{ ticket.kategori_masalah.name }}
            </div>
        </div>

        <!-- User Info -->
        <div class="flex items-center mb-3">
            <div class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center mr-2">
                <span class="text-xs font-medium text-gray-600">
                    {{ getUserInitials(ticket.user) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-900 truncate">{{ ticket.user?.nama_lengkap }}</p>
                <p class="text-xs text-gray-500 truncate">{{ ticket.user?.email }}</p>
            </div>
        </div>

        <!-- Time Information -->
        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
            <span>Created: {{ ticket.formatted_created_at }}</span>
            <span v-if="ticket.time_elapsed" class="font-medium">{{ ticket.time_elapsed }}</span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <div class="flex items-center space-x-2">
                <!-- Status change buttons based on current status -->
                <button
                    v-if="ticket.status === 'open' || ticket.status === 'assigned'"
                    @click.stop="$emit('status-change', ticket.id, 'in_progress')"
                    class="text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 transition"
                >
                    Start Working
                </button>
                <button
                    v-if="ticket.status === 'in_progress'"
                    @click.stop="$emit('status-change', ticket.id, 'waiting_user')"
                    class="text-xs bg-yellow-600 text-white px-2 py-1 rounded hover:bg-yellow-700 transition"
                >
                    Pause
                </button>
                <button
                    v-if="ticket.status === 'waiting_user' || ticket.status === 'waiting_response'"
                    @click.stop="$emit('status-change', ticket.id, 'in_progress')"
                    class="text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 transition"
                >
                    Resume
                </button>
                <button
                    v-if="['in_progress', 'waiting_user', 'waiting_response'].includes(ticket.status)"
                    @click.stop="$emit('resolve', ticket)"
                    class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 transition"
                >
                    Resolve
                </button>
                <button
                    v-if="['open', 'assigned', 'in_progress', 'waiting_user', 'waiting_response'].includes(ticket.status)"
                    @click.stop="$emit('reassign', ticket)"
                    class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded hover:bg-yellow-200 transition"
                    title="Request Reassignment"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </button>
            </div>
            <div class="flex items-center space-x-1">
                <!-- Attachments indicator -->
                <div v-if="ticket.attachments_count > 0" class="relative" title="Has attachments">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-3 h-3 flex items-center justify-center">
                        {{ ticket.attachments_count }}
                    </span>
                </div>
                <!-- Comments indicator -->
                <div v-if="ticket.comments_count > 0" class="relative" title="Has comments">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full w-3 h-3 flex items-center justify-center">
                        {{ ticket.comments_count }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Progress indicator for time-based tracking -->
        <div v-if="ticket.status === 'in_progress' && ticket.started_at" class="mt-3 pt-3 border-t border-gray-100">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>Time in progress</span>
                <span>{{ getInProgressTime(ticket.started_at) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div
                    class="bg-indigo-600 h-1.5 rounded-full transition-all duration-1000"
                    :style="{ width: getTimeProgress(ticket.started_at) + '%' }"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    draggable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['dragstart', 'status-change', 'resolve', 'reassign']);

const isDragging = ref(false);

const getUserInitials = (user) => {
    if (!user) return '?';
    const name = user.nama_lengkap || user.name || '';
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase();
};

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
};

const getInProgressTime = (startedAt) => {
    if (!startedAt) return '0m';

    const start = new Date(startedAt);
    const now = new Date();
    const diff = now - start;

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
};

const getTimeProgress = (startedAt) => {
    if (!startedAt) return 0;

    const start = new Date(startedAt);
    const now = new Date();
    const diff = now - start;

    // Consider 8 hours as full progress (can be adjusted)
    const fullProgress = 8 * 60 * 60 * 1000; // 8 hours in milliseconds
    return Math.min((diff / fullProgress) * 100, 100);
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
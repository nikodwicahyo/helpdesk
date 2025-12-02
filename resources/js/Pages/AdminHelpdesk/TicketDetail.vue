<template>
    <AppLayout role="admin">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button
                        @click="goBack"
                        class="text-gray-600 hover:text-gray-900 flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Tickets
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Ticket #{{ ticket.ticket_number }}</h1>
                        <p class="text-gray-600 mt-1">{{ ticket.title }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="showAssignmentModal = true"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition"
                    >
                        Assign to Teknisi
                    </button>
                    <button
                        @click="showPriorityModal = true"
                        class="bg-orange-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-orange-700 transition"
                    >
                        Update Priority
                    </button>
                    <button
                        @click="showStatusModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition"
                    >
                        Update Status
                    </button>
                    <button
                        v-if="ticket.status !== 'closed'"
                        @click="showCloseModal = true"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition"
                    >
                        Close Ticket
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ticket Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Ticket Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span :class="['px-2 py-1 text-xs font-medium rounded-full mt-1 inline-block', getStatusColor(ticket.status)]">
                                {{ ticket.status_label }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <span :class="['px-2 py-1 text-xs font-medium rounded-full mt-1 inline-block', getPriorityColor(ticket.priority)]">
                                {{ ticket.priority_label }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created</label>
                            <p class="text-gray-900">{{ ticket.formatted_created_at }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                            <p class="text-gray-900">{{ ticket.formatted_updated_at }}</p>
                        </div>
                        <div v-if="ticket.resolved_at">
                            <label class="block text-sm font-medium text-gray-700">Resolved At</label>
                            <p class="text-gray-900">{{ ticket.formatted_resolved_at }}</p>
                        </div>
                        <div v-if="ticket.resolution_time_minutes">
                            <label class="block text-sm font-medium text-gray-700">Resolution Time</label>
                            <p class="text-gray-900">{{ Math.round(ticket.resolution_time_minutes / 60) }} hours</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <div class="mt-2 p-4 bg-gray-50 rounded-lg text-gray-900 whitespace-pre-wrap">
                            {{ ticket.description }}
                        </div>
                    </div>

                    <div v-if="ticket.attachments && ticket.attachments.length > 0" class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>
                        <div class="space-y-2">
                            <div v-for="attachment in ticket.attachments" :key="attachment.id" class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <a :href="attachment.download_url" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    {{ attachment.original_name }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Comments</h2>

                    <div v-if="comments.length === 0" class="text-center py-8 text-gray-500">
                        No comments yet
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="comment in comments" :key="comment.id" class="border-b border-gray-200 pb-4 last:border-b-0">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-indigo-600">
                                            {{ comment.user?.name?.charAt(0) || 'A' }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ comment.user?.name }}</p>
                                        <p class="text-sm text-gray-500">{{ comment.user?.role }} • {{ comment.formatted_created_at }}</p>
                                    </div>
                                </div>
                                <span v-if="comment.is_internal" class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                    Internal
                                </span>
                            </div>
                            <div class="mt-2 text-gray-900 whitespace-pre-wrap">{{ comment.comment }}</div>
                        </div>
                    </div>

                    <!-- Add Comment Form -->
                    <div class="mt-6">
                        <form @submit.prevent="addComment">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Add Comment</label>
                            <textarea
                                v-model="newComment"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Enter your comment..."
                            ></textarea>
                            <div class="mt-3 flex items-center space-x-3">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="isInternalComment"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                    <span class="ml-2 text-sm text-gray-700">Internal comment</span>
                                </label>
                                <button
                                    type="submit"
                                    :disabled="!newComment.trim()"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Add Comment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- User Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">User Information</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <p class="text-gray-900">{{ ticket.user?.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP</label>
                            <p class="text-gray-900">{{ ticket.user?.nip }}</p>
                        </div>
                        <div v-if="ticket.user?.department">
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <p class="text-gray-900">{{ ticket.user?.department }}</p>
                        </div>
                        <div v-if="ticket.user?.email">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="text-gray-900">{{ ticket.user?.email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Assignment Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Assignment Information</h3>
                    <div v-if="ticket.assigned_teknisi" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assigned To</label>
                            <p class="text-gray-900">{{ ticket.assigned_teknisi.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP</label>
                            <p class="text-gray-900">{{ ticket.assigned_teknisi.nip }}</p>
                        </div>
                        <div v-if="ticket.assigned_teknisi.department">
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <p class="text-gray-900">{{ ticket.assigned_teknisi.department }}</p>
                        </div>
                    </div>
                    <div v-else class="text-gray-500">
                        Not assigned yet
                    </div>
                </div>

                <!-- Application Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Information</h3>
                    <div class="space-y-3">
                        <div v-if="ticket.aplikasi">
                            <label class="block text-sm font-medium text-gray-700">Application</label>
                            <p class="text-gray-900">{{ ticket.aplikasi.name }}</p>
                        </div>
                        <div v-if="ticket.kategori_masalah">
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <p class="text-gray-900">{{ ticket.kategori_masalah.name }}</p>
                        </div>
                        <div v-if="ticket.location">
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <p class="text-gray-900">{{ ticket.location }}</p>
                        </div>
                        <div v-if="ticket.ip_address">
                            <label class="block text-sm font-medium text-gray-700">IP Address</label>
                            <p class="text-gray-900">{{ ticket.ip_address }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Modal -->
        <AssignmentModal
            :show="showAssignmentModal"
            :ticket="ticket"
            :teknisis="availableTeknisi"
            @close="showAssignmentModal = false"
            @assigned="onTicketAssigned"
        />

        <!-- Priority Modal -->
        <PriorityModal
            :show="showPriorityModal"
            :ticket="ticket"
            @close="showPriorityModal = false"
            @updated="onPriorityUpdated"
        />

        <!-- Status Modal -->
        <StatusModal
            :show="showStatusModal"
            :ticket="ticket"
            @close="showStatusModal = false"
            @updated="onStatusUpdated"
        />

        <!-- Close Modal -->
        <CloseModal
            :show="showCloseModal"
            :ticket="ticket"
            @close="showCloseModal = false"
            @closed="onTicketClosed"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import AssignmentModal from '@/Components/Modals/AssignmentModal.vue';
import PriorityModal from '@/Components/Modals/PriorityModal.vue';
import StatusModal from '@/Components/Modals/StatusModal.vue';
import CloseModal from '@/Components/Modals/CloseModal.vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    comments: {
        type: Array,
        default: () => [],
    },
    history: {
        type: Array,
        default: () => [],
    },
    availableTeknisi: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const showAssignmentModal = ref(false);
const showPriorityModal = ref(false);
const showStatusModal = ref(false);
const showCloseModal = ref(false);
const newComment = ref('');
const isInternalComment = ref(false);

const goBack = () => {
    router.visit(route('admin.tickets-management.index'));
};

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-yellow-100 text-yellow-800',
        assigned: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-indigo-100 text-indigo-800',
        waiting_user: 'bg-orange-100 text-orange-800',
        waiting_admin: 'bg-purple-100 text-purple-800',
        waiting_response: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
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

const addComment = () => {
    if (!newComment.value.trim()) return;

    router.post(route('admin.tickets-management.comments.store', props.ticket.id), {
        comment: newComment.value,
        is_internal: isInternalComment.value,
    }, {
        onSuccess: () => {
            newComment.value = '';
            isInternalComment.value = false;
            router.reload({ only: ['comments'] });
        },
    });
};

const onTicketAssigned = () => {
    showAssignmentModal.value = false;
    router.reload({ only: ['ticket'] });
};

const onPriorityUpdated = () => {
    showPriorityModal.value = false;
    router.reload({ only: ['ticket'] });
};

const onStatusUpdated = () => {
    showStatusModal.value = false;
    router.reload({ only: ['ticket'] });
};

const onTicketClosed = () => {
    showCloseModal.value = false;
    router.reload({ only: ['ticket'] });
};

// Lifecycle hooks
onMounted(() => {
    // Ensure all modals are closed on component mount
    showAssignmentModal.value = false;
    showPriorityModal.value = false;
    showStatusModal.value = false;
    showCloseModal.value = false;
});
</script>
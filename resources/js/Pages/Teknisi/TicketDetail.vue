<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResolveTicketModal from '@/Components/Modals/ResolveTicketModal.vue';
import ReassignmentRequestModal from '@/Components/Modals/ReassignmentRequestModal.vue';
import axios from 'axios';

const props = defineProps({
    ticket: Object,
    comments: Array,
    timeline: Array,
    canUpdateStatus: Boolean,
    canAddComment: Boolean,
    canResolve: Boolean,
});

// State
const activeTab = ref('details');
const newComment = ref('');
const isInternalComment = ref(false);
const commentType = ref('comment');
const isSubmittingComment = ref(false);
const localComments = ref([...props.comments]);
const showResolveModal = ref(false);
const showReassignmentModal = ref(false);

// Computed
const statusColor = computed(() => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        assigned: 'bg-purple-100 text-purple-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        waiting_user: 'bg-orange-100 text-orange-800',
        waiting_response: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    };
    return colors[props.ticket.status] || 'bg-gray-100 text-gray-800';
});

const priorityColor = computed(() => {
    const colors = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    };
    return colors[props.ticket.priority] || 'bg-gray-100 text-gray-800';
});

// Methods
const goBack = () => {
    router.visit(route('teknisi.dashboard'));
};

const submitComment = async () => {
    if (!newComment.value.trim()) {
        alert('Please enter a comment');
        return;
    }

    isSubmittingComment.value = true;

    try {
        const response = await axios.post(
            route('teknisi.tickets.comments.store', props.ticket.id),
            {
                comment: newComment.value,
                is_internal: isInternalComment.value,
                type: commentType.value,
            }
        );

        if (response.data.success) {
            localComments.value.push(response.data.comment);
            newComment.value = '';
            isInternalComment.value = false;
            commentType.value = 'comment';
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        alert('Failed to add comment');
    } finally {
        isSubmittingComment.value = false;
    }
};

const updateStatus = async (newStatus) => {
    if (!confirm('Are you sure you want to update the ticket status?')) {
        return;
    }

    try {
        const response = await axios.post(
            route('teknisi.tickets.update-status', props.ticket.id),
            { status: newStatus }
        );

        if (response.data.success) {
            // Use Inertia visit instead of reload to avoid potential issues
            router.visit(route('teknisi.tickets.show', props.ticket.id), {
                preserveScroll: true,
            });
        } else {
            const errorMsg = response.data.errors?.join(', ') || 'Failed to update status';
            alert(errorMsg);
        }
    } catch (error) {
        console.error('Error updating status:', error);
        
        // Extract error message from response if available
        let errorMsg = 'Failed to update status';
        if (error.response?.data?.errors) {
            errorMsg = error.response.data.errors.join(', ');
        } else if (error.response?.data?.message) {
            errorMsg = error.response.data.message;
        } else if (error.message) {
            errorMsg = error.message;
        }
        
        alert(errorMsg);
    }
};

const openResolveModal = () => {
    showResolveModal.value = true;
};

const closeResolveModal = () => {
    showResolveModal.value = false;
};

const handleTicketResolved = (resolvedTicket) => {
    showResolveModal.value = false;
    router.visit(route('teknisi.tickets.show', props.ticket.id), {
        preserveScroll: true,
    });
};

const openReassignmentModal = () => {
    showReassignmentModal.value = true;
};

const closeReassignmentModal = () => {
    showReassignmentModal.value = false;
};

const handleReassignmentSubmitted = (data) => {
    showReassignmentModal.value = false;
    // Show success message or refresh the page
    router.visit(route('teknisi.tickets.show', props.ticket.id), {
        preserveScroll: true,
    });
};

const getRoleBadgeColor = (role) => {
    const colors = {
        user: 'bg-blue-100 text-blue-800',
        teknisi: 'bg-purple-100 text-purple-800',
        admin_helpdesk: 'bg-green-100 text-green-800',
        admin_aplikasi: 'bg-yellow-100 text-yellow-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};

const getTimelineColor = (color) => {
    const colors = {
        blue: 'bg-blue-500',
        yellow: 'bg-yellow-500',
        purple: 'bg-purple-500',
        gray: 'bg-gray-500',
        green: 'bg-green-500',
        red: 'bg-red-500',
        orange: 'bg-orange-500',
    };
    return colors[color] || 'bg-gray-500';
};
</script>

<template>
    <AppLayout title="Ticket Detail">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <button
                        @click="goBack"
                        class="text-gray-600 hover:text-gray-900 flex items-center mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Dashboard
                    </button>

                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ ticket.title }}
                            </h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Ticket #{{ ticket.ticket_number }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <span :class="statusColor" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ ticket.status_label }}
                            </span>
                            <span :class="priorityColor" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ ticket.priority_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mb-6 flex gap-2">
                    <button
                        v-if="ticket.status === 'open' || ticket.status === 'assigned'"
                        @click="updateStatus('in_progress')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Start Working
                    </button>
                    <button
                        v-if="ticket.status === 'in_progress'"
                        @click="updateStatus('waiting_user')"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                    >
                        Mark as Waiting Response
                    </button>
                    <button
                        v-if="ticket.status === 'waiting_user' || ticket.status === 'waiting_response'"
                        @click="updateStatus('in_progress')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Resume Working
                    </button>
                    <button
                        @click="openReassignmentModal"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                    >
                        Request Reassignment
                    </button>
                    <button
                        v-if="canResolve"
                        @click="openResolveModal"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Resolve Ticket
                    </button>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="activeTab = 'details'"
                            :class="[
                                activeTab === 'details'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                            ]"
                        >
                            Details
                        </button>
                        <button
                            @click="activeTab = 'comments'"
                            :class="[
                                activeTab === 'comments'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                            ]"
                        >
                            Comments ({{ localComments.length }})
                        </button>
                        <button
                            @click="activeTab = 'timeline'"
                            :class="[
                                activeTab === 'timeline'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                            ]"
                        >
                            Timeline
                        </button>
                    </nav>
                </div>

                <!-- Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        <!-- Details Tab -->
                        <div v-if="activeTab === 'details'" class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-xl font-semibold mb-4">Ticket Information</h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ ticket.description }}</p>
                                </div>

                                <div v-if="ticket.attachments && ticket.attachments.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            v-for="(attachment, index) in ticket.attachments"
                                            :key="index"
                                            :href="attachment.url || `/storage/${attachment}`"
                                            target="_blank"
                                            class="px-3 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200"
                                        >
                                            📎 {{ attachment.name || `Attachment ${index + 1}` }}
                                        </a>
                                    </div>
                                </div>

                                <div v-if="ticket.resolution_notes">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes</label>
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ ticket.resolution_notes }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Comments Tab -->
                        <div v-if="activeTab === 'comments'" class="space-y-4">
                            <!-- Add Comment Form -->
                            <div v-if="canAddComment" class="bg-white rounded-lg shadow p-6">
                                <h3 class="text-lg font-semibold mb-4">Add Comment</h3>
                                
                                <textarea
                                    v-model="newComment"
                                    rows="4"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter your comment..."
                                ></textarea>

                                <div class="mt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center">
                                            <input
                                                v-model="isInternalComment"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-600">Internal Note</span>
                                        </label>

                                        <select
                                            v-model="commentType"
                                            class="border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        >
                                            <option value="comment">General Comment</option>
                                            <option value="technical">Technical Note</option>
                                            <option value="status_update">Status Update</option>
                                        </select>
                                    </div>

                                    <button
                                        @click="submitComment"
                                        :disabled="isSubmittingComment || !newComment.trim()"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ isSubmittingComment ? 'Adding...' : 'Add Comment' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Comments List -->
                            <div class="space-y-4">
                                <div
                                    v-for="comment in localComments"
                                    :key="comment.id"
                                    class="bg-white rounded-lg shadow p-6"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span class="text-indigo-600 font-semibold">
                                                    {{ comment.user.name.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ comment.user.name }}</p>
                                                <div class="flex items-center gap-2">
                                                    <span :class="getRoleBadgeColor(comment.user.role)" class="text-xs px-2 py-0.5 rounded-full">
                                                        {{ comment.user.role_label }}
                                                    </span>
                                                    <span v-if="comment.is_internal" class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">
                                                        Internal
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-500">{{ comment.formatted_created_at }}</span>
                                    </div>
                                    <p class="text-gray-700 whitespace-pre-wrap">{{ comment.comment }}</p>
                                </div>

                                <div v-if="localComments.length === 0" class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                                    No comments yet
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Tab -->
                        <div v-if="activeTab === 'timeline'" class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-6">Activity Timeline</h3>
                            
                            <div class="space-y-6">
                                <div
                                    v-for="(item, index) in timeline"
                                    :key="item.id"
                                    class="flex gap-4"
                                >
                                    <div class="flex flex-col items-center">
                                        <div :class="getTimelineColor(item.color)" class="w-3 h-3 rounded-full"></div>
                                        <div v-if="index < timeline.length - 1" class="w-0.5 h-full bg-gray-200 mt-2"></div>
                                    </div>
                                    <div class="flex-1 pb-6">
                                        <p class="font-medium text-gray-900">{{ item.description }}</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ item.actor.name }} • {{ item.formatted_created_at }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="timeline.length === 0" class="text-center text-gray-500 py-8">
                                    No activity yet
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow p-6 space-y-6 sticky top-6">
                            <!-- Ticket Info -->
                            <div>
                                <h3 class="text-lg font-semibold mb-4">Ticket Info</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="text-sm text-gray-900">{{ ticket.formatted_created_at }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                        <dd class="text-sm text-gray-900">{{ ticket.formatted_updated_at }}</dd>
                                    </div>
                                    <div v-if="ticket.due_date">
                                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                        <dd class="text-sm text-gray-900">{{ ticket.formatted_due_date }}</dd>
                                    </div>
                                    <div v-if="ticket.is_overdue" class="pt-2 border-t">
                                        <span class="text-sm text-red-600 font-medium">⚠️ Overdue</span>
                                    </div>
                                </dl>
                            </div>

                            <!-- Requester Info -->
                            <div v-if="ticket.user" class="pt-6 border-t">
                                <h3 class="text-lg font-semibold mb-4">Requester</h3>
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-gray-900">{{ ticket.user.name }}</p>
                                    <p class="text-sm text-gray-500">{{ ticket.user.department }}</p>
                                    <p class="text-sm text-gray-500">{{ ticket.user.email }}</p>
                                    <p class="text-sm text-gray-500">{{ ticket.user.phone }}</p>
                                </div>
                            </div>

                            <!-- Application Info -->
                            <div v-if="ticket.aplikasi" class="pt-6 border-t">
                                <h3 class="text-lg font-semibold mb-4">Application</h3>
                                <p class="text-sm font-medium text-gray-900">{{ ticket.aplikasi.name }}</p>
                                <p class="text-sm text-gray-500">{{ ticket.kategori_masalah?.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolve Ticket Modal -->
        <ResolveTicketModal
            :show="showResolveModal"
            :ticket="ticket"
            @close="closeResolveModal"
            @resolved="handleTicketResolved"
        />

        <!-- Reassignment Request Modal -->
        <ReassignmentRequestModal
            :show="showReassignmentModal"
            :ticket="ticket"
            @close="closeReassignmentModal"
            @submitted="handleReassignmentSubmitted"
        />
    </AppLayout>
</template>

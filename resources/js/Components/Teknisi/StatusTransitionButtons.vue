<template>
    <div class="flex flex-wrap gap-2">
        <!-- Start Working / In Progress -->
        <button
            v-if="canTransitionTo('in_progress')"
            @click="updateStatus('in_progress')"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span v-if="loading && targetStatus === 'in_progress'">Processing...</span>
            <span v-else>Start Working</span>
        </button>

        <!-- Mark as Waiting Response -->
        <button
            v-if="canTransitionTo('waiting_response')"
            @click="updateStatus('waiting_response')"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span v-if="loading && targetStatus === 'waiting_response'">Processing...</span>
            <span v-else>Waiting Response</span>
        </button>

        <!-- Resolve -->
        <button
            v-if="canTransitionTo('resolved')"
            @click="showResolveModal = true"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Mark as Resolved</span>
        </button>

        <!-- Request Reassignment -->
        <button
            v-if="showReassignButton"
            @click="showReassignModal = true"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span>Request Reassignment</span>
        </button>

        <!-- Resolve Modal -->
        <TransitionRoot appear :show="showResolveModal" as="template">
            <Dialog as="div" @close="showResolveModal = false" class="relative z-50">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-bold text-gray-900 mb-4">
                                    Mark Ticket as Resolved
                                </DialogTitle>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Resolution Summary *
                                        </label>
                                        <input
                                            v-model="resolveData.solution_summary"
                                            type="text"
                                            placeholder="Brief summary of the solution"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Resolution Notes *
                                        </label>
                                        <textarea
                                            v-model="resolveData.resolution_notes"
                                            rows="4"
                                            placeholder="Detailed explanation of how the issue was resolved..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Technical Notes (Internal)
                                        </label>
                                        <textarea
                                            v-model="resolveData.technical_notes"
                                            rows="3"
                                            placeholder="Internal technical details..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        ></textarea>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-end space-x-3">
                                    <button
                                        @click="showResolveModal = false"
                                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="resolveTicket"
                                        :disabled="!resolveData.solution_summary || !resolveData.resolution_notes || loading"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="loading">Resolving...</span>
                                        <span v-else>Confirm Resolution</span>
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Reassignment Modal -->
        <TransitionRoot appear :show="showReassignModal" as="template">
            <Dialog as="div" @close="showReassignModal = false" class="relative z-50">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-bold text-gray-900 mb-4">
                                    Request Ticket Reassignment
                                </DialogTitle>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Reason for Reassignment *
                                        </label>
                                        <textarea
                                            v-model="reassignData.reason"
                                            rows="4"
                                            placeholder="Explain why this ticket should be reassigned..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        ></textarea>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-end space-x-3">
                                    <button
                                        @click="showReassignModal = false"
                                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="requestReassignment"
                                        :disabled="!reassignData.reason || loading"
                                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="loading">Submitting...</span>
                                        <span v-else>Submit Request</span>
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import axios from 'axios';

const props = defineProps({
    ticketId: {
        type: [Number, String],
        required: true
    },
    currentStatus: {
        type: String,
        required: true
    },
    showReassignButton: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['updated', 'resolved', 'error']);

const loading = ref(false);
const targetStatus = ref('');
const showResolveModal = ref(false);
const showReassignModal = ref(false);

const resolveData = ref({
    solution_summary: '',
    resolution_notes: '',
    technical_notes: ''
});

const reassignData = ref({
    reason: ''
});

const statusTransitions = {
    'open': ['in_progress'],
    'assigned': ['in_progress'],
    'in_progress': ['waiting_response', 'resolved'],
    'waiting_response': ['in_progress', 'resolved'],
    'resolved': [],
    'closed': []
};

const canTransitionTo = (status) => {
    return statusTransitions[props.currentStatus]?.includes(status);
};

const updateStatus = async (newStatus) => {
    loading.value = true;
    targetStatus.value = newStatus;

    try {
        const response = await axios.post(`/teknisi/tickets/${props.ticketId}/update-status`, {
            status: newStatus
        });

        if (response.data.success) {
            emit('updated', response.data.ticket);
        }
    } catch (error) {
        console.error('Failed to update status:', error);
        emit('error', error.response?.data?.errors || ['Failed to update status']);
    } finally {
        loading.value = false;
        targetStatus.value = '';
    }
};

const resolveTicket = async () => {
    loading.value = true;

    try {
        const response = await axios.post(`/teknisi/tickets/${props.ticketId}/resolve`, resolveData.value);

        if (response.data.success) {
            showResolveModal.value = false;
            emit('resolved', response.data.ticket);
            // Reset form
            resolveData.value = {
                solution_summary: '',
                resolution_notes: '',
                technical_notes: ''
            };
        }
    } catch (error) {
        console.error('Failed to resolve ticket:', error);
        emit('error', error.response?.data?.errors || ['Failed to resolve ticket']);
    } finally {
        loading.value = false;
    }
};

const requestReassignment = async () => {
    loading.value = true;

    try {
        const response = await axios.post(`/teknisi/tickets/${props.ticketId}/request-reassignment`, reassignData.value);

        if (response.data.success) {
            showReassignModal.value = false;
            emit('updated', { message: response.data.message });
            // Reset form
            reassignData.value = { reason: '' };
        }
    } catch (error) {
        console.error('Failed to request reassignment:', error);
        emit('error', error.response?.data?.errors || ['Failed to request reassignment']);
    } finally {
        loading.value = false;
    }
};
</script>

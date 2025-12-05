<template>
    <Modal
            :show="show"
            @close="$emit('close')"
            :title="t('modal.assignTicket')"
            size="md"
        >
        <!-- Ticket Information -->
        <div v-if="ticket" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900">{{ ticket.ticket_number }}</p>
                    <p class="text-sm text-gray-600 mt-1 truncate">{{ ticket.title }}</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <span class="text-xs text-gray-500">{{ t('ticket.priority') }}:</span>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(ticket.priority)]">
                            {{ ticket.priority_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form id="assignment-form" @submit.prevent="submitAssignment">
            <div class="space-y-4">
                <!-- Teknisi Selection -->
                <div>
                    <label for="teknisi" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('modal.assignTicketModal.selectTeknisi') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select
                            id="teknisi"
                            v-model="form.teknisi_nip"
                            required
                            class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 appearance-none bg-white cursor-pointer transition-colors"
                        >
                            <option value="">{{ t('modal.assignTicketModal.chooseTeknisi') }}</option>
                            <option
                                v-for="teknisi in formattedTeknisis"
                                :key="teknisi.nip || teknisi.id || teknisi"
                                :value="teknisi.nip || teknisi.value || teknisi.id || teknisi"
                            >
                                {{ teknisi.displayName || teknisi.name || teknisi.label || t('common.unknownTeknisi') }}
                            </option>
                            <option v-if="formattedTeknisis.length === 0" disabled>
                                {{ t('modal.assignTicketModal.noTeknisiAvailable') }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Warning when no teknisis available -->
                    <div v-if="formattedTeknisis.length === 0" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">{{ t('modal.assignTicketModal.noAvailableTeknisi') }}</p>
                                <p class="text-xs text-yellow-600 mt-1">
                                    {{ t('modal.assignTicketModal.checkTeknisiRecords') }}
                                    <span class="block mt-1">{{ t('modal.assignTicketModal.debugTeknisiCount', { count: teknisis?.length || 0 }) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Error message -->
                    <p v-if="form.errors.teknisi_nip" class="mt-2 text-sm text-red-600">
                        {{ form.errors.teknisi_nip }}
                    </p>
                </div>

                <!-- Assignment Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('modal.assignTicketModal.assignmentNotes') }} <span class="text-gray-400">({{ t('common.optional') }})</span>
                    </label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none transition-colors"
                        :placeholder="t('modal.assignTicketModal.addNotesPlaceholder')"
                    ></textarea>
                </div>
            </div>
        </form>

        <!-- Modal Footer -->
        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors font-medium text-sm"
                :disabled="form.processing"
            >
                {{ t('common.cancel') }}
            </button>
            <button
                type="submit"
                form="assignment-form"
                :disabled="form.processing"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ form.processing ? t('ticket.assigning') : t('modal.assignTicket') }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Common/Modal.vue';

const { t } = useI18n();

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    teknisis: {
        type: Array,
        required: true,
    },
    show: {
        type: Boolean,
        default: true,
    },
});

// Debug: Log teknisi data to understand structure
console.log('Teknisi data received in AssignmentModal:', {
    teknisis: props.teknisis,
    isArray: Array.isArray(props.teknisis),
    length: props.teknisis?.length,
    firstItem: props.teknisis?.[0],
    typeof: typeof props.teknisis
});

const emit = defineEmits(['close', 'assigned']);

const form = useForm({
    teknisi_nip: '',
    notes: '',
});

// Computed property to format teknisi display
const formattedTeknisis = computed(() => {
    // Check if teknisis is a valid array
    if (!Array.isArray(props.teknisis) || props.teknisis.length === 0) {
        console.log('No valid teknisis array found, returning empty array');
        return [];
    }

    const formatted = props.teknisis.map(teknisi => {
        // Handle different possible data structures
        const name = teknisi?.name || teknisi?.label || 'Unknown Teknisi';
        const department = teknisi?.department || '';
        const nip = teknisi?.nip || teknisi?.value || teknisi?.id || '';

        return {
            ...teknisi,
            name: name,
            department: department,
            nip: nip,
            displayName: name + (department ? ` (${department})` : '')
        };
    });

    return formatted;
});

const submitAssignment = () => {
    console.log('Submitting assignment:', {
        ticketId: props.ticket.id,
        teknisiNip: form.teknisi_nip,
        notes: form.notes,
        route: route('admin.tickets-management.assign', props.ticket.id)
    });

    form.post(route('admin.tickets-management.assign', props.ticket.id), {
        onSuccess: () => {
            console.log('Assignment successful');
            emit('assigned');
        },
        onError: (errors) => {
            console.error('Assignment errors:', errors);
        },
    });
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
</script>

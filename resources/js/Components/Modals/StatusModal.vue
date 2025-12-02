<template>
    <Modal
        :show="show"
        @close="$emit('close')"
        :title="t('modal.updateTicketStatus')"
        size="md"
    >
        <!-- Error Message Display -->
        <div
            v-if="errorMessage"
            class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg relative"
        >
            <button
                type="button"
                @click="errorMessage = ''"
                class="absolute top-2 right-2 text-red-400 hover:text-red-600 transition-colors p-1 rounded-md hover:bg-red-50"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <div class="flex pr-8">
                <div class="py-1">
                    <svg
                        class="fill-current h-5 w-5 text-red-500 mr-3"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-red-800">
                        {{ t('message.statusUpdateError') }}
                    </p>
                    <p class="text-sm text-red-600 whitespace-pre-line">
                        {{ errorMessage }}
                    </p>
                </div>
            </div>
        </div>

        <form id="status-form" @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <label
                        for="status"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {{ t('ticket.status') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select
                            id="status"
                            v-model="form.status"
                            @change="clearErrorOnChange"
                            class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm appearance-none bg-white cursor-pointer transition-colors"
                            required
                        >
                            <option
                                v-for="option in availableStatusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none"
                        >
                            <svg
                                class="w-5 h-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Workflow Guidance -->
                    <div
                        class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-blue-400"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-800">
                                    {{ t('ticket.workflowGuidance') }}
                                </p>
                                <p class="text-xs text-blue-600 mt-1">
                                    {{ t('status.open') }} → {{ t('status.assigned') }} → {{ t('status.inProgress') }} → {{ t('status.waiting') }} → {{ t('status.resolved') }} → {{ t('status.closed') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="notes"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {{ t('ticket.notes') }} <span class="text-gray-400">({{ t('common.optional') }})</span>
                    </label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none transition-colors"
                        :placeholder="t('ticket.addNotesPlaceholder')"
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
                :disabled="processing"
            >
                {{ t('common.cancel') }}
            </button>
            <button
                type="submit"
                form="status-form"
                :disabled="processing"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                @click="submit"
            >
                <svg
                    v-if="processing"
                    class="animate-spin -ml-1 mr-2 h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                {{ processing ? t('ticket.updating') : t('ticket.updateStatus') }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { route } from "ziggy-js";
import Modal from "@/Components/Common/Modal.vue";

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    ticket: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close", "updated"]);

const processing = ref(false);
const errorMessage = ref("");
const form = ref({
    status: props.ticket.status || "open",
    notes: "",
});

// Debug: Log the ticket status to ensure it's working
console.log("Ticket status received in StatusModal:", props.ticket.status);

// Available status options with labels (matching database enum values)
const allStatusOptions = computed(() => [
    { value: "open", label: t('status.open') },
    { value: "assigned", label: t('status.assigned') },
    { value: "in_progress", label: t('status.inProgress') },
    { value: "waiting_user", label: t('status.waitingUser') },
    { value: "waiting_admin", label: t('status.waitingAdmin') },
    { value: "resolved", label: t('status.resolved') },
    { value: "closed", label: t('status.closed') },
    { value: "cancelled", label: t('status.cancelled') },
]);

// Compute available status options based on current ticket status
const availableStatusOptions = computed(() => {
    const currentStatus = props.ticket.status || "open";

    // Get valid transitions for current status
    const validTransitions = getValidStatusTransitions(currentStatus);

    // Filter to show current status + valid transitions
    const available = allStatusOptions.value.filter(
        (option) =>
            option.value === currentStatus ||
            validTransitions.includes(option.value)
    );

    // Debug logging
    console.log("Status Options:", {
        currentStatus,
        validTransitions,
        availableCount: available.length,
        available: available.map((o) => o.value),
    });

    // Always return at least all options as fallback
    return available.length > 0 ? available : allStatusOptions;
});

// Function to get status transition rules - Step-by-step workflow (matching database values)
const getValidStatusTransitions = (currentStatus) => {
    const transitions = {
        open: ["assigned", "in_progress", "cancelled"], // From Open: Assign or start working or cancel
        assigned: ["in_progress", "open", "cancelled"], // From Assigned: Start working, reassign, or cancel
        in_progress: ["waiting_user", "waiting_admin", "resolved", "cancelled"], // From In Progress: Wait for user/admin, resolve, or cancel
        waiting_user: ["in_progress", "resolved", "cancelled"], // From Waiting User: Continue work, resolve, or cancel
        waiting_admin: ["in_progress", "resolved", "cancelled"], // From Waiting Admin: Continue work, resolve, or cancel
        resolved: ["closed"], // From Resolved: Only can close
        closed: ["open"], // From Closed: Can reopen if needed
        cancelled: ["open"], // From Cancelled: Can reopen if needed
    };
    return transitions[currentStatus] || [];
};

// Function to get user-friendly error message
const getStatusTransitionError = (fromStatus, toStatus) => {
    const statusLabels = {
        open: t('status.open'),
        assigned: t('status.assigned'),
        in_progress: t('status.inProgress'),
        waiting_user: t('status.waitingUser'),
        waiting_admin: t('status.waitingAdmin'),
        resolved: t('status.resolved'),
        closed: t('status.closed'),
        cancelled: t('status.cancelled'),
    };

    const validTransitions = getValidStatusTransitions(fromStatus);
    const validLabels = validTransitions
        .map((status) => statusLabels[status])
        .join(", ");

    return `❌ ${t('message.cannotChangeStatus', {from: statusLabels[fromStatus], to: statusLabels[toStatus]})}

            ${t('message.pleaseFollowWorkflow', {from: statusLabels[fromStatus], validStatuses: validLabels})}`;
};

// Watch for status changes to clear error messages
const clearErrorOnChange = () => {
    errorMessage.value = "";
};

const getCurrentTicketStatus = () => {
    return props.ticket.status || "open";
};

const submit = async () => {
    processing.value = true;
    errorMessage.value = "";

    try {
        const currentTicketStatus = getCurrentTicketStatus();

        // Debug logging
        console.log("Submitting status update:", {
            ticketId: props.ticket.id,
            currentTicketStatus,
            selectedStatus: form.value.status,
            route: route(
                "admin.tickets-management.update-status",
                props.ticket.id
            ),
        });

        // Check if status transition is valid before sending request
        if (form.value.status !== currentTicketStatus) {
            const validTransitions =
                getValidStatusTransitions(currentTicketStatus);
            if (!validTransitions.includes(form.value.status)) {
                errorMessage.value = getStatusTransitionError(
                    currentTicketStatus,
                    form.value.status
                );
                processing.value = false;
                return;
            }
        }

        const response = await axios.post(
            route("admin.tickets-management.update-status", props.ticket.id),
            form.value
        );

        if (response.data.success) {
            processing.value = false;
            emit("updated");
        } else {
            errorMessage.value =
                response.data.message || "Status update failed";
            processing.value = false;
        }
    } catch (error) {
        const currentTicketStatus = getCurrentTicketStatus();

        if (error.response?.data?.message) {
            // Handle specific backend error messages
            if (
                error.response.data.message.includes(
                    "Invalid status transition"
                )
            ) {
                errorMessage.value = getStatusTransitionError(
                    currentTicketStatus,
                    form.value.status
                );
            } else {
                errorMessage.value = error.response.data.message;
            }
        } else {
            errorMessage.value =
                "Failed to update ticket status. Please try again.";
        }
        processing.value = false;
    }
};
</script>

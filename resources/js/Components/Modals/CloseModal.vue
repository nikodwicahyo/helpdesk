<template>
    <Modal :show="show" @close="$emit('close')" :title="t('modal.closeTicket')" size="md">
        <!-- Warning Message -->
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg
                        class="h-5 w-5 text-amber-400"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800">{{ t('common.warning') }}</h3>
                    <p class="text-sm text-amber-700 mt-1">
                        {{ t('message.confirmCloseTicket') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Ticket Info -->
        <div
            v-if="ticket"
            class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-900">
                        {{ ticket.ticket_number }}
                    </p>
                    <p class="text-sm text-gray-600 truncate">
                        {{ ticket.title }}
                    </p>
                </div>
                <span
                    :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        getStatusColor(ticket.status),
                    ]"
                >
                    {{ ticket.status_label }}
                </span>
            </div>
        </div>

        <form id="close-form" @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <label
                        for="reason"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {{ t('ticket.reasonForClosing') }}
                        <span class="text-gray-400">({{ t('common.optional') }})</span>
                    </label>
                    <textarea
                        id="reason"
                        v-model="form.reason"
                        rows="3"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none transition-colors"
                        :placeholder="t('ticket.reasonForClosingPlaceholder')"
                    ></textarea>
                </div>

                <div>
                    <label
                        for="feedback"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {{ t('ticket.feedback') }} <span class="text-gray-400">({{ t('common.optional') }})</span>
                    </label>
                    <textarea
                        id="feedback"
                        v-model="form.feedback"
                        rows="2"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none transition-colors"
                        :placeholder="t('ticket.additionalFeedback')"
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
                form="close-form"
                :disabled="processing"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
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
                {{ processing ? t('ticket.closing') : t('ticket.closeTicket') }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
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

const emit = defineEmits(["close", "closed"]);

const processing = ref(false);
const form = ref({
    reason: "",
    feedback: "",
});

const submit = () => {
    processing.value = true;

    router.post(
        route("admin.tickets-management.close", props.ticket.id),
        form.value,
        {
            onSuccess: () => {
                processing.value = false;
                emit("closed");
            },
            onError: () => {
                processing.value = false;
            },
        }
    );
};

const getStatusColor = (status) => {
    const colors = {
        open: "bg-gray-100 text-gray-800",
        assigned: "bg-blue-100 text-blue-800",
        in_progress: "bg-yellow-100 text-yellow-800",
        waiting_user: "bg-purple-100 text-purple-800",
        waiting_admin: "bg-orange-100 text-orange-800",
        resolved: "bg-green-100 text-green-800",
        closed: "bg-gray-100 text-gray-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};
</script>

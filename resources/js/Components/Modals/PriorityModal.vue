<template>
    <Modal
        :show="show"
        @close="$emit('close')"
        :title="t('modal.updateTicketPriority')"
        size="md"
    >
        <form id="priority-form" @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <label
                        for="priority"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {{ t('ticket.priority') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select
                            id="priority"
                            v-model="form.priority"
                            class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm appearance-none bg-white cursor-pointer transition-colors"
                            required
                        >
                            <option value="low">{{ t('priority.low') }}</option>
                            <option value="medium">{{ t('priority.medium') }}</option>
                            <option value="high">{{ t('priority.high') }}</option>
                            <option value="urgent">{{ t('priority.urgent') }}</option>
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
                </div>

                <div>
                    <label
                        for="reason"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {{ t('ticket.reason') }} <span class="text-gray-400">({{ t('common.optional') }})</span>
                    </label>
                    <textarea
                        id="reason"
                        v-model="form.reason"
                        rows="3"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none transition-colors"
                        :placeholder="t('ticket.reasonForPriorityChange')"
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
                form="priority-form"
                :disabled="processing"
                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
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
                {{ processing ? t('ticket.updating') : t('ticket.updatePriority') }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/Common/Modal.vue';

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

const emit = defineEmits(['close', 'updated']);

const processing = ref(false);
const form = ref({
    priority: props.ticket.priority,
    reason: '',
});

const submit = () => {
    processing.value = true;

    router.post(route('admin.tickets-management.update-priority', props.ticket.id), form.value, {
        onSuccess: () => {
            processing.value = false;
            emit('updated');
        },
        onError: () => {
            processing.value = false;
        },
    });
};
</script>

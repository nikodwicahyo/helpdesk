<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
        >
            <!-- Background overlay with blur -->
            <div
                class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
                aria-hidden="true"
                @click="close"
            ></div>

            <!-- Center modal -->
            <span
                class="hidden sm:inline-block sm:align-middle sm:h-screen"
                aria-hidden="true"
                >&#8203;</span
            >

            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10"
                        >
                            <svg
                                class="h-6 w-6 text-yellow-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                />
                            </svg>
                        </div>
                        <div
                            class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full"
                        >
                            <h3
                                class="text-lg leading-6 font-medium text-gray-900"
                                id="modal-title"
                            >
                                {{ $t("ticket.rateTicket") }}
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    {{ $t("ticket.rateTicketDescription") }}
                                </p>

                                <!-- Star Rating -->
                                <div class="mb-6">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        {{ $t("ticket.rating") }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <StarRating
                                        v-model="form.rating"
                                        :size="40"
                                        :show-rating="true"
                                    />
                                    <p
                                        v-if="errors.rating"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.rating }}
                                    </p>
                                </div>

                                <!-- Feedback -->
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        {{ $t("ticket.feedback") }} ({{
                                            $t("common.optional")
                                        }})
                                    </label>
                                    <textarea
                                        v-model="form.feedback"
                                        rows="4"
                                        :maxlength="1000"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        :placeholder="
                                            $t('ticket.feedbackPlaceholder')
                                        "
                                    ></textarea>
                                    <div class="flex justify-between mt-1">
                                        <p
                                            v-if="errors.feedback"
                                            class="text-sm text-red-600"
                                        >
                                            {{ errors.feedback }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ form.feedback.length }}/1000
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
                >
                    <button
                        type="button"
                        @click="submit"
                        :disabled="processing || !form.rating"
                        :class="[
                            'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm',
                            processing || !form.rating
                                ? 'bg-gray-400 cursor-not-allowed'
                                : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
                        ]"
                    >
                        <svg
                            v-if="processing"
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                        {{
                            processing
                                ? $t("common.submitting")
                                : $t("action.submit")
                        }}
                    </button>
                    <button
                        type="button"
                        @click="close"
                        :disabled="processing"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        {{ $t("common.cancel") }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import StarRating from "@/Components/UI/StarRating.vue";

const { t } = useI18n();

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    ticketId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["close", "success"]);

const form = ref({
    rating: 0,
    feedback: "",
});

const errors = ref({});
const processing = ref(false);

watch(
    () => props.isOpen,
    (newValue) => {
        if (newValue) {
            // Reset form when modal opens
            form.value = {
                rating: 0,
                feedback: "",
            };
            errors.value = {};
        }
    }
);

const close = () => {
    if (!processing.value) {
        emit("close");
    }
};

const submit = async () => {
    if (processing.value || !form.value.rating) return;

    errors.value = {};
    processing.value = true;

    try {
        await router.post(
            route("user.tickets.rate", props.ticketId),
            form.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit("success");
                    close();
                },
                onError: (err) => {
                    errors.value = err;
                },
                onFinish: () => {
                    processing.value = false;
                },
            }
        );
    } catch (error) {
        console.error("Rating submission error:", error);
        processing.value = false;
    }
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
                />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div
                    class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
                >
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                        >
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <!-- Header -->
                                <div
                                    class="flex items-center justify-between mb-6"
                                >
                                    <div class="flex items-center">
                                        <div
                                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10"
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
                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                                />
                                            </svg>
                                        </div>
                                        <DialogTitle
                                            as="h3"
                                            class="ml-4 text-xl font-semibold leading-6 text-gray-900"
                                        >
                                            Request Reassignment
                                        </DialogTitle>
                                    </div>
                                    <button
                                        @click="$emit('close')"
                                        class="text-gray-400 hover:text-gray-500 focus:outline-none"
                                    >
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Info Banner -->
                                <div
                                    class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200"
                                >
                                    <div class="flex">
                                        <svg
                                            class="h-5 w-5 text-yellow-400 mr-3 mt-0.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <div class="text-sm text-yellow-700">
                                            <p class="font-medium">Note</p>
                                            <p class="mt-1">
                                                This request will be sent to the
                                                Admin Helpdesk for review. The
                                                ticket will remain assigned to
                                                you until the request is
                                                approved.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ticket Info -->
                                <div
                                    v-if="ticket"
                                    class="mb-6 p-4 bg-gray-50 rounded-lg"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                {{ ticket.ticket_number }}
                                            </p>
                                            <p
                                                class="text-base font-semibold text-gray-900"
                                            >
                                                {{ ticket.title }}
                                            </p>
                                        </div>
                                        <span
                                            :class="
                                                getPriorityBadgeClass(
                                                    ticket.priority
                                                )
                                            "
                                            class="px-3 py-1 text-xs font-medium rounded-full"
                                        >
                                            {{
                                                ticket.priority_label ||
                                                ticket.priority
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Form -->
                                <form
                                    @submit.prevent="handleSubmit"
                                    class="space-y-5"
                                >
                                    <!-- Reason -->
                                    <div>
                                        <label
                                            for="reason"
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                        >
                                            Reason for Reassignment
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.reason"
                                            id="reason"
                                            rows="4"
                                            required
                                            maxlength="500"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent resize-none"
                                            placeholder="Explain why this ticket should be reassigned..."
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ form.reason?.length || 0 }}/500
                                            characters
                                        </p>
                                        <p
                                            v-if="errors.reason"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.reason }}
                                        </p>
                                    </div>

                                    <!-- Suggested Teknisi -->
                                    <div>
                                        <label
                                            for="suggested_teknisi"
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                        >
                                            Suggest Another Teknisi
                                            <span
                                                class="text-gray-400 text-xs ml-1"
                                                >(Optional)</span
                                            >
                                        </label>
                                        <select
                                            v-model="form.suggested_teknisi_nip"
                                            id="suggested_teknisi"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        >
                                            <option value="">
                                                -- No suggestion --
                                            </option>
                                            <option
                                                v-for="teknisi in availableTeknisis"
                                                :key="teknisi.nip"
                                                :value="teknisi.nip"
                                            >
                                                {{ teknisi.name }} ({{
                                                    teknisi.department || "N/A"
                                                }})
                                            </option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">
                                            If you know a teknisi better suited
                                            for this ticket
                                        </p>
                                    </div>

                                    <!-- Reassignment Type -->
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >Reason Category</label
                                        >
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.category"
                                                    type="radio"
                                                    value="expertise"
                                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >Requires different
                                                    expertise</span
                                                >
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.category"
                                                    type="radio"
                                                    value="workload"
                                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >High workload /
                                                    availability issue</span
                                                >
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.category"
                                                    type="radio"
                                                    value="escalation"
                                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >Needs escalation to senior
                                                    teknisi</span
                                                >
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.category"
                                                    type="radio"
                                                    value="other"
                                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >Other reason</span
                                                >
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Error Messages -->
                                    <div
                                        v-if="Object.keys(errors).length > 0"
                                        class="rounded-lg bg-red-50 p-4"
                                    >
                                        <div class="flex">
                                            <svg
                                                class="h-5 w-5 text-red-400"
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
                                            <div class="ml-3">
                                                <ul
                                                    class="text-sm text-red-700 list-disc list-inside"
                                                >
                                                    <li
                                                        v-for="(
                                                            error, key
                                                        ) in errors"
                                                        :key="key"
                                                    >
                                                        {{ error }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Footer -->
                            <div
                                class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                            >
                                <button
                                    type="button"
                                    @click="handleSubmit"
                                    :disabled="submitting || !isFormValid"
                                    class="inline-flex w-full justify-center rounded-lg bg-yellow-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-700 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="submitting"
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
                                        submitting
                                            ? "Submitting..."
                                            : "Submit Request"
                                    }}
                                </button>
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    :disabled="submitting"
                                    class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                >
                                    Cancel
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from "@headlessui/vue";
import { route } from "ziggy-js";
import { Ziggy } from "@/ziggy";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    ticket: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "submitted"]);

const form = ref({
    reason: "",
    suggested_teknisi_nip: "",
    category: "expertise",
});

const availableTeknisis = ref([]);
const errors = ref({});
const submitting = ref(false);
const loading = ref(false);

const isFormValid = computed(() => {
    return form.value.reason.trim().length >= 10;
});

watch(
    () => props.show,
    async (newVal) => {
        if (newVal) {
            await loadAvailableTeknisis();
        } else {
            resetForm();
        }
    }
);

const resetForm = () => {
    form.value = {
        reason: "",
        suggested_teknisi_nip: "",
        category: "expertise",
    };
    errors.value = {};
};

const loadAvailableTeknisis = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route("api.teknisis.available", undefined, undefined, Ziggy));
        if (response.data.success) {
            availableTeknisis.value = response.data.teknisis || [];
        }
    } catch (error) {
        console.error("Failed to load teknisis:", error);
        availableTeknisis.value = [];
    } finally {
        loading.value = false;
    }
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        low: "bg-gray-100 text-gray-800",
        medium: "bg-blue-100 text-blue-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };
    return classes[priority] || "bg-gray-100 text-gray-800";
};

const handleSubmit = async () => {
    if (!isFormValid.value || !props.ticket) return;

    errors.value = {};
    submitting.value = true;

    try {
        const payload = {
            reason: `[${form.value.category.toUpperCase()}] ${
                form.value.reason
            }`,
        };

        if (form.value.suggested_teknisi_nip) {
            payload.suggested_teknisi_nip = form.value.suggested_teknisi_nip;
        }

        const response = await axios.post(
            route("teknisi.tickets.request-reassignment", { ticket: props.ticket.id }, undefined, Ziggy),
            payload
        );

        if (response.data.success) {
            emit("submitted", response.data);
            emit("close");
        }
    } catch (error) {
        if (error.response?.status === 422) {
            const responseErrors = error.response.data.errors;
            if (Array.isArray(responseErrors)) {
                errors.value = { general: responseErrors.join(", ") };
            } else {
                errors.value = responseErrors || {
                    general: "Validation failed",
                };
            }
        } else if (error.response?.status === 403) {
            const responseErrors = error.response.data.errors;
            if (Array.isArray(responseErrors)) {
                errors.value = { general: responseErrors.join(", ") };
            } else {
                errors.value = {
                    general: "You are not authorized to perform this action.",
                };
            }
        } else {
            errors.value = {
                general:
                    error.response?.data?.message ||
                    "Failed to submit request. Please try again.",
            };
        }
    } finally {
        submitting.value = false;
    }
};
</script>

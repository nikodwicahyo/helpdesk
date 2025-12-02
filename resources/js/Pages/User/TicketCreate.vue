<template>
    <AppLayout role="user" :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ t("ticket.createTicket") }}
                    </h1>
                    <p class="text-gray-600 mt-1">
                        {{ t("ticket.reportIssue") }}
                        <span
                            v-if="draftSaved"
                            class="ml-3 text-sm text-green-600 font-medium transition-opacity duration-500"
                        >
                            ✓ {{ t("common.draftSaved") }}
                        </span>
                    </p>
                </div>
                <Link
                    :href="route('user.tickets.index')"
                    class="text-indigo-600 hover:text-indigo-800 font-medium"
                >
                    ← {{ t("common.back") }} {{ t("nav.tickets") }}
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Main Form Card -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ t("ticket.ticketInformation") }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ t("ticket.provideDetails") }}
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Application Selection -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ t("ticket.application") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.aplikasi_id"
                                @change="loadCategories"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :class="{
                                    'border-red-500': errors.aplikasi_id,
                                }"
                            >
                                <option value="">
                                    {{ t("ticket.selectApplicationService") }}
                                </option>
                                <option
                                    v-for="app in applications"
                                    :key="app.id"
                                    :value="app.id"
                                >
                                    {{ t('ticket.appWithCode', { name: app.name, code: app.code }) }}
                                </option>
                            </select>
                            <p
                                v-if="errors.aplikasi_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.aplikasi_id }}
                            </p>
                        </div>

                        <!-- Category Selection -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ t("ticket.category") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.kategori_masalah_id"
                                required
                                :disabled="
                                    !form.aplikasi_id || loadingCategories
                                "
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                :class="{
                                    'border-red-500':
                                        errors.kategori_masalah_id,
                                }"
                            >
                                <option value="">
                                    {{
                                        loadingCategories
                                            ? t("common.loading")
                                            : t(
                                                  "modal.categoryModal.selectACategory"
                                              )
                                    }}
                                </option>
                                <option
                                    v-for="category in availableCategories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <p
                                v-if="errors.kategori_masalah_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.kategori_masalah_id }}
                            </p>
                        </div>

                        <!-- Title -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ t("ticket.subject") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                maxlength="200"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :class="{ 'border-red-500': errors.title }"
                                :placeholder="t('ticket.issueTitlePlaceholder')"
                            />
                            <div class="flex justify-between mt-1">
                                <p
                                    v-if="errors.title"
                                    class="text-sm text-red-600"
                                >
                                    {{ errors.title }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ (form.title || '').length }}/200
                                    {{ t("common.characters") }}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ t("ticket.description") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                required
                                rows="6"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :class="{ 'border-red-500': errors.description }"
                                :placeholder="
                                    t('ticket.detailedDescriptionPlaceholder')
                                "
                            ></textarea>
                            <p
                                v-if="errors.description"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.description }}
                            </p>
                        </div>

                        <!-- Priority and Location Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Priority -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    {{ t("ticket.priority") }}
                                    {{ t("ticket.level") }}
                                </label>
                                <select
                                    v-model="form.priority"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="low">
                                        {{ t("priority.low") }} -
                                        {{ t("ticket.generalInquiry") }}
                                    </option>
                                    <option value="medium" selected>
                                        {{ t("priority.medium") }} -
                                        {{ t("ticket.standardIssue") }}
                                    </option>
                                    <option value="high">
                                        {{ t("priority.high") }} -
                                        {{ t("ticket.significantImpact") }}
                                    </option>
                                    <option value="urgent">
                                        {{ t("priority.urgent") }} -
                                        {{ t("ticket.criticalIssue") }}
                                    </option>
                                </select>
                                <p
                                    v-if="errors.priority"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.priority }}
                                </p>
                            </div>

                            <!-- Location -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    {{ t("ticket.location") }} ({{
                                        t("common.optional")
                                    }})
                                </label>
                                <input
                                    v-model="form.location"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="
                                        t('ticket.locationPlaceholder')
                                    "
                                />
                                <p
                                    v-if="errors.location"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.location }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Attachments Card -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ t("ticket.attachments") }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ t("ticket.uploadFilesDescription") }}
                        </p>
                    </div>

                    <div class="p-6">
                        <FileUpload
                            v-model="form.lampiran"
                            label=""
                            :multiple="true"
                            accept="image/*,.pdf,.doc,.docx,.txt,.log"
                            :max-size="2 * 1024 * 1024"
                            :max-files="5"
                            @error="handleFileError"
                        />
                        <p
                            v-if="errors.lampiran"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ errors.lampiran }}
                        </p>
                    </div>
                </div>

                <!-- Priority Information Card -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-start space-x-3">
                        <svg
                            class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5"
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
                        <div>
                            <h3 class="text-lg font-medium text-blue-900 mb-2">
                                {{ t('ticket.priorityGuidelines.title') }}
                            </h3>
                            <div class="space-y-2 text-sm text-blue-800">
                                <div class="flex items-start space-x-2">
                                    <span class="font-medium text-red-600"
                                        >{{ t('ticket.priorityGuidelines.urgentTitle') }}</span
                                    >
                                    <span
                                        >{{ t('ticket.priorityGuidelines.urgentDescription') }}</span
                                    >
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="font-medium text-orange-600"
                                        >{{ t('ticket.priorityGuidelines.highTitle') }}</span
                                    >
                                    <span
                                        >{{ t('ticket.priorityGuidelines.highDescription') }}</span
                                    >
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="font-medium text-blue-600"
                                        >{{ t('ticket.priorityGuidelines.mediumTitle') }}</span
                                    >
                                    <span
                                        >{{ t('ticket.priorityGuidelines.mediumDescription') }}</span
                                    >
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="font-medium text-gray-600"
                                        >{{ t('ticket.priorityGuidelines.lowTitle') }}</span
                                    >
                                    <span
                                        >{{ t('ticket.priorityGuidelines.lowDescription') }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between">
                    <Link
                        :href="route('user.dashboard')"
                        class="text-gray-600 hover:text-gray-800 font-medium"
                    >
                        {{ t("common.cancel") }}
                    </Link>

                    <div class="flex items-center space-x-4">
                        <button
                            type="button"
                            @click="saveDraft"
                            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition"
                            :disabled="form.processing"
                        >
                            {{ t("ticket.saveDraft") }}
                        </button>

                        <button
                            type="submit"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="form.processing"
                        >
                            <span
                                v-if="!form.processing"
                                class="flex items-center"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                    />
                                </svg>
                                {{ t("ticket.submitTicket") }}
                            </span>
                            <span v-else class="flex items-center">
                                <svg
                                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
                                {{ t("common.submitting") }}
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import FileUpload from "@/Components/Common/FileUpload.vue";
import { debounce } from "lodash";
import axios from "axios";

const { t } = useI18n();

const props = defineProps({
    applications: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const breadcrumbs = [
    { label: "Dashboard", href: route("user.dashboard") },
    { label: "Tickets", href: route("user.tickets.index") },
    { label: t("ticket.createNewTicket") },
];

const availableCategories = ref([]);
const loadingCategories = ref(false);
const draftSaved = ref(false);
const draftLoading = ref(false);
const hasDraft = ref(false);
const lastSaved = ref(null);

const form = useForm({
    aplikasi_id: "",
    kategori_masalah_id: "",
    title: "",
    description: "",
    priority: "medium",
    location: "",
    lampiran: [],
});

const loadCategories = async () => {
    if (!form.aplikasi_id) {
        availableCategories.value = [];
        form.kategori_masalah_id = "";
        return;
    }

    loadingCategories.value = true;
    form.kategori_masalah_id = "";

    try {
        const response = await axios.get(
            `/api/applications/${form.aplikasi_id}/categories`
        );
        
        if (response.data && response.data.success) {
            availableCategories.value = response.data.categories || [];
        } else {
            availableCategories.value = [];
        }
    } catch (error) {
        console.error("Error loading categories:", error);
        if (error.response && error.response.data) {
            console.error("Server error:", error.response.data);
        }
        availableCategories.value = [];
    } finally {
        loadingCategories.value = false;
    }
};

const handleFileError = (error) => {
    // Handle file upload errors
    console.error("File upload error:", error);
};

const submit = () => {
    form.post(route("user.tickets.store"), {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
        onError: (errors) => {
            // Scroll to first error
            const firstErrorField = Object.keys(errors)[0];
            const element = document.querySelector(
                `[name="${firstErrorField}"]`
            );
            if (element) {
                element.scrollIntoView({ behavior: "smooth", block: "center" });
                element.focus();
            }
        },
    });
};

const saveDraft = debounce(async () => {
    // Don't save if form is empty
    if (!form.title && !form.description && !form.aplikasi_id) return;

    try {
        const response = await axios.post(route("user.tickets.drafts.save"), {
            aplikasi_id: form.aplikasi_id || null,
            kategori_masalah_id: form.kategori_masalah_id || null,
            title: form.title || null,
            description: form.description || null,
            priority: form.priority || 'medium',
            location: form.location || null,
        });

        if (response.data.success) {
            draftSaved.value = true;
            lastSaved.value = new Date();
            setTimeout(() => {
                draftSaved.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error("Error saving draft:", error);
    }
}, 3000);

// Check for existing draft on mount
const checkDraft = async () => {
    try {
        draftLoading.value = true;
        const response = await axios.get(route("user.tickets.drafts.load"));

        if (response.data.success && response.data.draft) {
            hasDraft.value = true;
            if (confirm(t("ticket.draftFound"))) {
                loadDraft(response.data.draft);
            }
        }
    } catch (error) {
        console.error("Error checking draft:", error);
    } finally {
        draftLoading.value = false;
    }
};

const loadDraft = (draftData) => {
    form.aplikasi_id = draftData.aplikasi_id || "";
    form.kategori_masalah_id = draftData.kategori_masalah_id || "";
    form.title = draftData.title || "";
    form.description = draftData.description || "";
    form.priority = draftData.priority || "medium";
    form.location = draftData.location || "";

    if (form.aplikasi_id) {
        loadCategories();
    }
};

// Watch for changes to trigger auto-save
watch(
    () => [
        form.aplikasi_id,
        form.kategori_masalah_id,
        form.title,
        form.description,
        form.priority,
        form.location,
    ],
    () => {
        saveDraft();
    }
);

// Check draft on mount
onMounted(() => {
    checkDraft();
});
</script>

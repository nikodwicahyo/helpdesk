<template>
    <AppLayout role="user">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ t("ticket.editTicket") }}
                    </h1>
                    <p class="text-gray-600 mt-1">
                        {{ t("ticket.updateTicketDetails") }}
                        {{ ticket.ticket_number }}
                    </p>
                </div>
                <Link
                    :href="route('user.tickets.show', ticket.id)"
                    class="text-indigo-600 hover:text-indigo-800 font-medium"
                >
                    ← {{ t("ticket.backToTicket") }}
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Main Form Card -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ $t("ticket.ticketInformation") }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $t("ticket.updateYourTicketDetails") }}
                        </p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Application Selection -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ $t("ticket.application") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.aplikasi_id"
                                @change="loadCategories"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                :class="{
                                    'border-red-500': form.errors.aplikasi_id,
                                }"
                            >
                                <option value="">
                                    {{ $t("ticket.selectApplicationService") }}
                                </option>
                                <option
                                    v-for="app in applications"
                                    :key="app.id"
                                    :value="app.id"
                                >
                                    {{ app.name }} ({{ app.code }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.aplikasi_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.aplikasi_id }}
                            </p>
                        </div>

                        <!-- Category Selection -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ $t("ticket.category") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.kategori_masalah_id"
                                required
                                :disabled="
                                    !form.aplikasi_id || loadingCategories
                                "
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100"
                                :class="{
                                    'border-red-500':
                                        form.errors.kategori_masalah_id,
                                }"
                            >
                                <option value="">
                                    {{
                                        loadingCategories
                                            ? $t("common.loading")
                                            : $t("ticket.selectCategory")
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
                                v-if="form.errors.kategori_masalah_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.kategori_masalah_id }}
                            </p>
                        </div>

                        <!-- Title -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ $t("ticket.subject") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                maxlength="200"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.title }"
                                :placeholder="
                                    $t('ticket.issueTitlePlaceholder')
                                "
                            />
                            <div class="flex justify-between mt-1">
                                <p
                                    v-if="form.errors.title"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.title }}
                                </p>
{{ form.title.length }}/200 {{ t("common.characters") }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ $t("ticket.description") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                required
                                rows="6"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                :class="{
                                    'border-red-500': form.errors.description,
                                }"
                                :placeholder="
                                    $t('ticket.detailedDescriptionPlaceholder')
                                "
                            ></textarea>
                            <p
                                v-if="form.errors.description"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Priority and Location -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >{{ $t("ticket.priority") }}</label
                                >
                                <select
                                    v-model="form.priority"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="low">
                                        {{ $t("priority.low") }} -
                                        {{
                                            $t(
                                                "ticket.priorityGuidelines.lowDescription"
                                            )
                                        }}
                                    </option>
                                    <option value="medium">
                                        {{ $t("priority.medium") }} -
                                        {{
                                            $t(
                                                "ticket.priorityGuidelines.mediumDescription"
                                            )
                                        }}
                                    </option>
                                    <option value="high">
                                        {{ $t("priority.high") }} -
                                        {{
                                            $t(
                                                "ticket.priorityGuidelines.highDescription"
                                            )
                                        }}
                                    </option>
                                    <option value="urgent">
                                        {{ $t("priority.urgent") }} -
                                        {{
                                            $t(
                                                "ticket.priorityGuidelines.urgentDescription"
                                            )
                                        }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    {{ $t("ticket.location") }} ({{
                                        $t("common.optional")
                                    }})
                                </label>
                                <input
                                    v-model="form.location"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                    :placeholder="
                                        $t('ticket.locationPlaceholder')
                                    "
                                />
                            </div>
                        </div>

                        <!-- Existing Attachments -->
                        <div
                            v-if="
                                ticket.attachments &&
                                ticket.attachments.length > 0
                            "
                        >
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ $t("ticket.attachments") }}
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    v-for="attachment in ticket.attachments"
                                    :key="attachment.filename"
                                    class="flex items-center p-2 bg-gray-50 rounded border text-xs"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-500 mr-2"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                                        />
                                    </svg>
                                    <span class="truncate">{{
                                        attachment.original_name
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- New Attachments -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                {{ $t("common.upload") }}
                                {{ $t("ticket.attachments") }} ({{
                                    $t("common.optional")
                                }})
                            </label>
                            <FileUpload
                                v-model="form.files"
                                :multiple="true"
                                accept="image/*,.pdf,.doc,.docx,.txt"
                                :max-size="2 * 1024 * 1024"
                                :max-files="5"
                            />
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4">
                    <Link
                        :href="route('user.tickets.show', ticket.id)"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                    >
                        {{ $t("common.cancel") }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 flex items-center"
                    >
                        <svg
                            v-if="form.processing"
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
                        <span v-if="!form.processing">{{
                            $t("ticket.updateTicket")
                        }}</span>
                        <span v-else>{{ $t("common.updating") }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import FileUpload from "@/Components/Common/FileUpload.vue";
import axios from "axios";

const { t } = useI18n();

const props = defineProps({
    ticket: Object,
    applications: Array,
    categories: Array,
});

const form = useForm({
    title: props.ticket.title,
    description: props.ticket.description,
    aplikasi_id: props.ticket.aplikasi_id,
    kategori_masalah_id: props.ticket.kategori_masalah_id,
    priority: props.ticket.priority,
    location: props.ticket.location,
    files: [],
});

const availableCategories = ref(
    props.categories.filter(
        (cat) => cat.aplikasi_id === props.ticket.aplikasi_id
    )
);
const loadingCategories = ref(false);

const loadCategories = async () => {
    if (!form.aplikasi_id) {
        availableCategories.value = [];
        form.kategori_masalah_id = "";
        return;
    }

    loadingCategories.value = true;
    try {
        const response = await axios.get(
            `/api/applications/${form.aplikasi_id}/categories`
        );
        availableCategories.value = response.data;

        // Reset category if it's not in the new list
        if (
            !availableCategories.value.find(
                (c) => c.id === form.kategori_masalah_id
            )
        ) {
            form.kategori_masalah_id = "";
        }
    } catch (error) {
        console.error("Failed to load categories:", error);
        availableCategories.value = [];
    } finally {
        loadingCategories.value = false;
    }
};

const submit = () => {
    form.post(route("user.tickets.update", props.ticket.id), {
        forceFormData: true,
        onSuccess: () => {
            // Will redirect to ticket detail page
        },
    });
};
</script>

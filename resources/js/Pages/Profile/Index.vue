<template>
    <AppLayout :role="role">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg"
                    >
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ t("nav.myProfile") }}
                        </h1>
                        <p class="text-gray-600">
                            {{ t("profile.updatePersonalInfo") }}
                        </p>
                    </div>
                </div>
                <Link
                    :href="`/${rolePrefix}/profile/edit`"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
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
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                    {{ t("common.edit") }}
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Profile Overview Card -->
            <div class="bg-white shadow-lg rounded-lg mb-8">
                <div class="p-8">
                    <div class="flex items-center space-x-6">
                        <!-- User Initials -->
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <UserInitials :user="user" size="xl" />
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                {{ user?.name || t("profile.guestUser") }}
                            </h2>
                            <p class="text-gray-600 mb-4">
                                {{ user?.display_name || "" }}
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-500">{{
                                        t("profile.role")
                                    }}</span>
                                    <p
                                        class="font-medium text-gray-900 capitalize"
                                    >
                                        {{ role.replace("_", " ") }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">{{
                                        t("profile.department")
                                    }}</span>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            user?.department ||
                                            t("profile.notSpecified")
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">{{
                                        t("profile.position")
                                    }}</span>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            user?.position ||
                                            t("profile.notSpecified")
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="bg-white shadow-lg rounded-lg mb-8">
                <div class="p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ t("profile.personalInformation") }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >{{ t("user.email") }}</label
                            >
                            <p class="text-gray-900">
                                {{ user?.email || t("profile.notProvided") }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >{{ t("user.phone") }}</label
                            >
                            <p class="text-gray-900">
                                {{ user?.phone || t("profile.notProvided") }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >{{ t("user.nip") }}</label
                            >
                            <p class="font-mono text-gray-900">
                                {{ user?.nip || t("profile.notAssigned") }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >{{ t("common.status") }}</label
                            >
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                :class="
                                    user?.status === 'active'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                "
                            >
                                {{
                                    user?.status === "active"
                                        ? t("status.active")
                                        : t("status.inactive") ||
                                          t("common.unknown")
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Completion -->
            <div class="bg-white shadow-lg rounded-lg mb-8">
                <div class="p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ t("profile.profileCompletion") }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span
                                    class="text-sm font-medium text-gray-700"
                                    >{{ t("profile.basicInformation") }}</span
                                >
                                <span class="text-sm text-gray-900"
                                    >{{
                                        Math.round(
                                            profileCompletion.basic_info
                                        )
                                    }}%</span
                                >
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                    :style="`width: ${profileCompletion.basic_info}%`"
                                ></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span
                                    class="text-sm font-medium text-gray-700"
                                    >{{ t("profile.contactInformation") }}</span
                                >
                                <span class="text-sm text-gray-900"
                                    >{{
                                        Math.round(
                                            profileCompletion.contact_info
                                        )
                                    }}%</span
                                >
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                    :style="`width: ${profileCompletion.contact_info}%`"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-lg font-semibold text-gray-900"
                                    >{{ t("profile.overallCompletion") }}</span
                                >
                                <span class="text-2xl font-bold text-blue-600"
                                    >{{
                                        Math.round(profileCompletion.overall)
                                    }}%</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Statistics -->
            <div v-if="role === 'user'" class="bg-white shadow-lg rounded-lg">
                <div class="p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ t("nav.myTickets") }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">
                                {{ stats.tickets_created }}
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ t("profile.ticketsCreated") }}
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">
                                {{ stats.tickets_resolved }}
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ t("profile.ticketsResolved") }}
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-orange-600">
                                {{ Math.round(stats.avg_resolution_time) }}h
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ t("profile.avgResolutionTime") }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else-if="role === 'teknisi'"
                class="bg-white shadow-lg rounded-lg"
            >
                <div class="p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ t("teknisi.myTasks") }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">
                                {{ stats.tickets_assigned }}
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ t("profile.ticketsAssigned") }}
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">
                                {{ stats.tickets_resolved }}
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ t("profile.ticketsResolved") }}
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-yellow-600">
                                {{ Math.round(stats.avg_rating * 10) / 10 }}
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ t("profile.averageRating") }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import AppLayout from "@/Layouts/AppLayout.vue";
import UserInitials from "@/Components/UI/UserInitials.vue";

const { t } = useI18n();

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    role: {
        type: String,
        required: true,
    },
    profileCompletion: {
        type: Object,
        default: () => ({
            basic_info: 0,
            contact_info: 0,
            overall: 0,
        }),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

// Convert role to URL prefix
const getRolePrefix = (role) => {
    const roleMap = {
        admin_helpdesk: "admin",
        admin_aplikasi: "admin-aplikasi",
        teknisi: "teknisi",
        user: "user",
    };
    return roleMap[role] || "user";
};

const rolePrefix = getRolePrefix(props.role);
</script>

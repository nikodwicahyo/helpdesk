<template>
    <AppLayout role="admin" :title="t('nav.userManagement')">
        <template #header>
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 animate-slideInDown"
            >
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg"
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
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1
                                class="text-3xl sm:text-4xl font-bold text-gray-900"
                            >
                                {{ t("nav.userManagement") }}
                            </h1>
                            <p
                                class="text-gray-600 text-sm sm:text-base animate-fadeInUp animation-delay-200"
                            >
                                {{
                                    t(
                                        "adminHelpdesk.userManagement.description"
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex items-center space-x-4 text-sm text-gray-500"
                    >
                        <div class="flex items-center">
                            <div
                                class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"
                            ></div>
                            <span>{{ t("activityLog.liveMonitoring") }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg
                                class="w-4 h-4 mr-1 text-blue-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span>{{
                                new Date().toLocaleString(locale, {
                                    weekday: "long",
                                    hour: "2-digit",
                                    minute: "2-digit",
                                })
                            }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg
                                class="w-4 h-4 mr-1 text-purple-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            <span
                                >{{ users.total || 0 }}
                                {{ t("nav.users") }}</span
                            >
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="openImportModal"
                        class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg
                            class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>
                        <span class="hidden sm:inline"
                            >{{ t("common.import") }}
                            {{ t("common.csv") }}</span
                        >
                        <span class="sm:hidden">📥</span>
                    </button>
                    <button
                        @click="openCreateModal"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg
                            class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                            />
                        </svg>
                        <span class="hidden sm:inline"
                            >{{ t("common.create") }} {{ t("nav.users") }}</span
                        >
                        <span class="sm:hidden">➕</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Main Content -->
        <div class="pb-12">
            <div class="max-w-xxl mx-auto sm:px-2 lg:px-2 space-y-6">
                <!-- Stats Cards -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6"
                >
                    <div
                        class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg"
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
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900">
                            {{ users.total || 0 }}
                        </h3>
                        <p class="text-sm font-medium text-gray-600 mt-1">
                            {{
                                t(
                                    "adminHelpdesk.userManagement.stats.totalUsers"
                                )
                            }}
                        </p>
                    </div>

                    <div
                        class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg"
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
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900">
                            {{ activeUsersCount }}
                        </h3>
                        <p class="text-sm font-medium text-gray-600 mt-1">
                            {{ t("status.active") }} {{ t("nav.users") }}
                        </p>
                    </div>

                    <div
                        class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg"
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
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900">
                            {{ inactiveUsersCount }}
                        </h3>
                        <p class="text-sm font-medium text-gray-600 mt-1">
                            {{ t("status.inactive") }} {{ t("nav.users") }}
                        </p>
                    </div>

                    <div
                        class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg"
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
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900">
                            {{ newUsersThisMonth }}
                        </h3>
                        <p class="text-sm font-medium text-gray-600 mt-1">
                            {{ t("common.newUsers") }}
                        </p>
                    </div>
                </div>

                <!-- User Table -->
                <div
                    class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20"
                >
                    <div class="p-6 border-b border-gray-200">
                        <!-- Search and Filter -->
                        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    :placeholder="t('search.searchTickets')"
                                    autocomplete="off"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="filterRole"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">
                                        {{ t("activityLog.allEntities") }}
                                    </option>
                                    <option value="user">
                                        {{ t("roles.user") }}
                                    </option>
                                    <option value="admin_helpdesk">
                                        {{ t("roles.adminHelpdesk") }}
                                    </option>
                                    <option value="admin_aplikasi">
                                        {{ t("roles.adminAplikasi") }}
                                    </option>
                                    <option value="teknisi">
                                        {{ t("roles.teknisi") }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <select
                                    v-model="filterStatus"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">
                                        {{ t("activityLog.allEntities") }}
                                    </option>
                                    <option value="active">
                                        {{ t("status.active") }}
                                    </option>
                                    <option value="inactive">
                                        {{ t("status.inactive") }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <button
                                    @click="resetFilters"
                                    class="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                >
                                    {{ t("common.reset") }}
                                </button>
                            </div>
                        </div>

                        <!-- User Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ t("user.nip") }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ t("user.name") }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ t("user.email") }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ t("user.role") }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ t("ticket.status") }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ t("action.actions") }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="user in paginatedUsers"
                                        :key="user.nip"
                                        class="hover:bg-gray-50 transition-colors"
                                    >
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        >
                                            <div class="flex items-center">
                                                <UserInitials
                                                    :user="{ name: user.name }"
                                                    size="sm"
                                                    class="mr-3"
                                                />
                                                <span class="font-mono">{{
                                                    user.nip
                                                }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ user.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{
                                                    user.department ||
                                                    t("common.notAvailable")
                                                }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        >
                                            {{ user.email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="
                                                    user.role_badge ||
                                                    getRoleBadgeClass(
                                                        user.actual_role ||
                                                            user.role
                                                    )
                                                "
                                            >
                                                {{
                                                    user.role_label ||
                                                    getRoleLabel(
                                                        user.actual_role ||
                                                            user.role
                                                    )
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="
                                                    user.status_badge ||
                                                    'bg-gray-100 text-gray-800'
                                                "
                                            >
                                                {{
                                                    user.status === "active"
                                                        ? t("status.active")
                                                        : t("status.inactive")
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                        >
                                            <button
                                                @click="openEditModal(user)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                                :disabled="!user.can_edit"
                                                :title="
                                                    !user.can_edit
                                                        ? t(
                                                              'modal.userModal.cannotEdit'
                                                          )
                                                        : ''
                                                "
                                            >
                                                {{ t("common.edit") }}
                                            </button>
                                            <button
                                                @click="toggleUserStatus(user)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                                :disabled="
                                                    !user.can_toggle_status
                                                "
                                                :title="
                                                    !user.can_toggle_status
                                                        ? t(
                                                              'modal.userModal.cannotChangeStatus'
                                                          )
                                                        : ''
                                                "
                                            >
                                                {{
                                                    user.status === "active"
                                                        ? t(
                                                              "modal.userModal.deactivate"
                                                          )
                                                        : t(
                                                              "modal.userModal.activate"
                                                          )
                                                }}
                                            </button>
                                            <button
                                                @click="resetUserPassword(user)"
                                                class="text-yellow-600 hover:text-yellow-900"
                                                :disabled="!user.can_edit"
                                                :title="
                                                    !user.can_edit
                                                        ? t(
                                                              'modal.userModal.cannotResetPassword'
                                                          )
                                                        : ''
                                                "
                                            >
                                                {{
                                                    t(
                                                        "modal.userModal.resetPassword"
                                                    )
                                                }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <SimplePagination
                            :data="users"
                            label="pengguna"
                            @page-changed="handlePageChange"
                            class="mt-6"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for creating/editing users - Enhanced Design -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <!-- Modal Header with Icon -->
                <div
                    class="flex items-center space-x-3 pb-4 border-b border-gray-200 mb-6"
                >
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg"
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
                        <h3 class="text-xl font-bold text-gray-900">
                            {{
                                isEditing
                                    ? t("common.edit") + " " + t("nav.users")
                                    : t("common.create") + " " + t("nav.users")
                            }}
                        </h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{
                                isEditing
                                    ? t("modal.userModal.editDescription")
                                    : t("modal.userModal.createDescription")
                            }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="space-y-6">
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            {{ t("user.nip") }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.nip"
                                type="text"
                                required
                                :disabled="isEditing"
                                autocomplete="username"
                                :placeholder="
                                    t('modal.userModal.nipPlaceholder')
                                "
                                maxlength="18"
                                @blur="!isEditing && validateNIP()"
                                :class="[
                                    'w-full px-3 py-2 pr-10 border rounded-md shadow-sm focus:outline-none transition-colors',
                                    {
                                        'border-gray-300 focus:ring-blue-500 focus:border-blue-500':
                                            !validationStatus.nip || isEditing,
                                        'border-yellow-400 focus:ring-yellow-500 focus:border-yellow-500':
                                            validationStatus.nip === 'checking',
                                        'border-green-500 focus:ring-green-500 focus:border-green-500 bg-green-50':
                                            validationStatus.nip === 'valid',
                                        'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50':
                                            validationStatus.nip === 'invalid',
                                        'bg-gray-100 cursor-not-allowed':
                                            isEditing,
                                    },
                                ]"
                            />
                            <!-- Validation icon -->
                            <div
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                            >
                                <!-- Checking spinner -->
                                <svg
                                    v-if="validationStatus.nip === 'checking'"
                                    class="animate-spin h-5 w-5 text-yellow-500"
                                    xmlns="http://www.w3.org/2000/svg"
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
                                <!-- Valid checkmark -->
                                <svg
                                    v-else-if="validationStatus.nip === 'valid'"
                                    class="h-5 w-5 text-green-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <!-- Invalid X -->
                                <svg
                                    v-else-if="
                                        validationStatus.nip === 'invalid'
                                    "
                                    class="h-5 w-5 text-red-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </div>
                        <!-- Validation message -->
                        <p
                            v-if="validationMessages.nip && !isEditing"
                            :class="[
                                'mt-1 text-sm',
                                {
                                    'text-yellow-600':
                                        validationStatus.nip === 'checking',
                                    'text-green-600':
                                        validationStatus.nip === 'valid',
                                    'text-red-600':
                                        validationStatus.nip === 'invalid',
                                },
                            ]"
                        >
                            {{ validationMessages.nip }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            {{ t("modal.userModal.fullName") }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            autocomplete="name"
                            :placeholder="
                                t('modal.userModal.fullNamePlaceholder')
                            "
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            {{ t("user.email") }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                :placeholder="
                                    t('modal.userModal.emailPlaceholder')
                                "
                                @blur="validateEmail()"
                                :class="[
                                    'w-full px-3 py-2 pr-10 border rounded-md shadow-sm focus:outline-none transition-colors',
                                    {
                                        'border-gray-300 focus:ring-blue-500 focus:border-blue-500':
                                            !validationStatus.email,
                                        'border-yellow-400 focus:ring-yellow-500 focus:border-yellow-500':
                                            validationStatus.email ===
                                            'checking',
                                        'border-green-500 focus:ring-green-500 focus:border-green-500 bg-green-50':
                                            validationStatus.email === 'valid',
                                        'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50':
                                            validationStatus.email ===
                                            'invalid',
                                    },
                                ]"
                            />
                            <!-- Validation icon -->
                            <div
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                            >
                                <!-- Checking spinner -->
                                <svg
                                    v-if="validationStatus.email === 'checking'"
                                    class="animate-spin h-5 w-5 text-yellow-500"
                                    xmlns="http://www.w3.org/2000/svg"
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
                                <!-- Valid checkmark -->
                                <svg
                                    v-else-if="
                                        validationStatus.email === 'valid'
                                    "
                                    class="h-5 w-5 text-green-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <!-- Invalid X -->
                                <svg
                                    v-else-if="
                                        validationStatus.email === 'invalid'
                                    "
                                    class="h-5 w-5 text-red-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </div>
                        <!-- Validation message -->
                        <p
                            v-if="validationMessages.email"
                            :class="[
                                'mt-1 text-sm',
                                {
                                    'text-yellow-600':
                                        validationStatus.email === 'checking',
                                    'text-green-600':
                                        validationStatus.email === 'valid',
                                    'text-red-600':
                                        validationStatus.email === 'invalid',
                                },
                            ]"
                        >
                            {{ validationMessages.email }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t("user.phone") }}</label
                        >
                        <input
                            v-model="form.phone"
                            type="tel"
                            autocomplete="tel"
                            :placeholder="t('modal.userModal.phonePlaceholder')"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t("user.department") }}</label
                        >
                        <input
                            v-model="form.department"
                            type="text"
                            autocomplete="organization"
                            :placeholder="
                                t('modal.userModal.positionPlaceholder')
                            "
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t("user.position") }}</label
                        >
                        <input
                            v-model="form.position"
                            type="text"
                            autocomplete="organization-title"
                            :placeholder="
                                t('modal.userModal.positionPlaceholder')
                            "
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t("user.role") }}</label
                        >
                        <select
                            v-model="form.role"
                            required
                            :disabled="
                                isEditing && form.role === 'admin_helpdesk'
                            "
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            <option value="user">{{ t("roles.user") }}</option>
                            <option value="admin_helpdesk">
                                {{ t("roles.adminHelpdesk") }}
                            </option>
                            <option value="admin_aplikasi">
                                {{ t("roles.adminAplikasi") }}
                            </option>
                            <option value="teknisi">
                                {{ t("roles.teknisi") }}
                            </option>
                        </select>
                        <p
                            v-if="isEditing && form.role === 'admin_helpdesk'"
                            class="mt-1 text-sm text-red-600"
                        >
                            Admin Helpdesk users cannot change their role.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t("ticket.status") }}</label
                        >
                        <select
                            v-model="form.status"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="active">
                                {{ t("status.active") }}
                            </option>
                            <option value="inactive">
                                {{ t("status.inactive") }}
                            </option>
                        </select>
                    </div>

                    <!-- Hidden username field for accessibility (using NIP as username) -->
                    <input
                        type="text"
                        name="username"
                        :value="form.nip"
                        autocomplete="username"
                        class="sr-only"
                        aria-hidden="true"
                        tabindex="-1"
                    />

                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            {{ t("user.password") }}
                            <span v-if="!isEditing" class="text-red-500"
                                >*</span
                            >
                        </label>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            :required="!isEditing"
                            :placeholder="
                                isEditing
                                    ? t('modal.userModal.passwordPlaceholder')
                                    : t('common.min8Characters')
                            "
                            autocomplete="new-password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            {{ t("modal.userModal.passwordRequirements") }}
                        </p>
                    </div>

                    <!-- Password confirmation - always show for new users, conditionally for editing -->
                    <div v-if="!isEditing || form.password" class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            {{
                                t("modal.userModal.confirmPasswordPlaceholder")
                            }}
                            <span
                                v-if="!isEditing || form.password"
                                class="text-red-500"
                                >*</span
                            >
                        </label>
                        <input
                            v-model="form.password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            :required="!isEditing || !!form.password"
                            :placeholder="
                                t('modal.userModal.confirmPasswordPlaceholder')
                            "
                            autocomplete="new-password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            {{ t("modal.userModal.passwordMatchRequirement") }}
                        </p>
                        <div class="mt-1">
                            <input
                                type="checkbox"
                                id="showPassword"
                                v-model="showPassword"
                            />
                            <label
                                for="showPassword"
                                class="ml-2 text-sm text-gray-700"
                                >{{
                                    t("modal.userModal.showPasswordLabel")
                                }}</label
                            >
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {{ t("common.cancel") }}
                        </button>
                        <button
                            type="submit"
                            :disabled="formSubmitting"
                            class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{
                                formSubmitting
                                    ? t("common.saving")
                                    : isEditing
                                    ? t("common.update")
                                    : t("common.save")
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- CSV Import Modal -->
        <Modal :show="showImportModal" @close="closeImportModal" size="lg">
            <template #header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center"
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
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ t("common.import") }} {{ t("nav.users") }}
                                {{ t("modal.fromCSV") }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ t("modal.bulkImportDescription") }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="space-y-6">
                <!-- Instructions Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 mb-2">
                        {{ t("modal.importInstructions") }}:
                    </h4>
                    <ul
                        class="text-sm text-blue-800 space-y-1 list-disc list-inside"
                    >
                        <li>
                            {{ t("modal.downloadTemplate") }}
                        </li>
                        <li>
                            {{ t("modal.fillUserData") }}
                        </li>
                        <li>
                            {{ t("modal.requiredColumns") }}
                        </li>
                        <li>
                            {{ t("modal.optionalColumns") }}
                        </li>
                        <li>
                            {{ t("modal.validRoles") }}
                        </li>
                        <li>
                            {{ t("modal.validStatus") }}
                        </li>
                    </ul>
                </div>

                <!-- Download Template Section -->
                <div
                    class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border"
                >
                    <div>
                        <h4 class="font-medium text-gray-900">
                            {{ t("modal.csvTemplate") }}
                        </h4>
                        <p class="text-sm text-gray-600">
                            {{ t("modal.gettingStartedTemplate") }}
                        </p>
                    </div>
                    <button
                        @click="downloadTemplate"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition flex items-center"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        {{ t("common.download") }} {{ t("modal.csvTemplate") }}
                    </button>
                </div>

                <!-- File Upload Section -->
                <div v-if="!importResults">
                    <div
                        @dragover.prevent="handleDragOver"
                        @dragleave.prevent="handleDragLeave"
                        @drop.prevent="handleDrop"
                        :class="[
                            'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                            isDragging
                                ? 'border-green-500 bg-green-50'
                                : 'border-gray-300 hover:border-gray-400',
                            importFile ? 'border-green-500 bg-green-50' : '',
                        ]"
                    >
                        <div v-if="!importFile">
                            <svg
                                class="w-12 h-12 mx-auto text-gray-400 mb-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                />
                            </svg>
                            <div class="mb-4">
                                <p
                                    class="text-lg font-medium text-gray-900 mb-2"
                                >
                                    {{ t("modal.importCSV.dropFile") }}
                                </p>
                                <p class="text-sm text-gray-500 mb-4">
                                    {{ t("modal.importCSV.orClickToBrowse") }}
                                </p>
                            </div>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".csv,.txt"
                                @change="handleFileSelect"
                                class="hidden"
                            />
                            <button
                                @click="$refs.fileInput.click()"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                {{ t("common.chooseFile") }}
                            </button>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                class="flex items-center justify-center space-x-3"
                            >
                                <svg
                                    class="w-8 h-8 text-green-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <div class="text-left">
                                    <p class="font-medium text-gray-900">
                                        {{ importFile.name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ formatFileSize(importFile.size) }}
                                    </p>
                                </div>
                                <button
                                    @click="removeFile"
                                    class="text-red-500 hover:text-red-700 transition"
                                >
                                    <svg
                                        class="w-5 h-5"
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

                            <button
                                @click="uploadCsv"
                                :disabled="importProgress"
                                class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-lg font-medium transition flex items-center justify-center"
                            >
                                <svg
                                    v-if="importProgress"
                                    class="w-5 h-5 mr-2 animate-spin"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                    />
                                </svg>
                                {{
                                    importProgress
                                        ? t("common.importing")
                                        : t("modal.importCSV.importUsers")
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Import Results Section -->
                <div v-if="importResults" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            class="bg-blue-50 border border-blue-200 rounded-lg p-4"
                        >
                            <div class="flex items-center space-x-2">
                                <svg
                                    class="w-5 h-5 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                <span class="text-sm font-medium text-blue-900">
                                    {{ t("modal.importCSV.totalRows") }}
                                </span>
                            </div>
                            <p class="text-2xl font-bold text-blue-900 mt-1">
                                {{ importResults.total_rows }}
                            </p>
                        </div>

                        <div
                            class="bg-green-50 border border-green-200 rounded-lg p-4"
                        >
                            <div class="flex items-center space-x-2">
                                <svg
                                    class="w-5 h-5 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span
                                    class="text-sm font-medium text-green-900"
                                    >{{ t("common.successful") }}</span
                                >
                            </div>
                            <p class="text-2xl font-bold text-green-900 mt-1">
                                {{ importResults.successful_imports }}
                            </p>
                        </div>

                        <div
                            v-if="importResults.failed_imports > 0"
                            class="bg-red-50 border border-red-200 rounded-lg p-4"
                        >
                            <div class="flex items-center space-x-2">
                                <svg
                                    class="w-5 h-5 text-red-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"
                                    />
                                </svg>
                                <span
                                    class="text-sm font-medium text-red-900"
                                    >{{ t("common.failed") }}</span
                                >
                            </div>
                            <p class="text-2xl font-bold text-red-900 mt-1">
                                {{ importResults.failed_imports }}
                            </p>
                        </div>
                    </div>

                    <!-- Error Details -->
                    <div
                        v-if="
                            importResults.errors &&
                            importResults.errors.length > 0
                        "
                        class="border border-red-200 rounded-lg p-4 bg-red-50"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-red-900">
                                {{ t("modal.importCSV.importErrors") }}
                            </h4>
                            <button
                                @click="showErrorDetails = !showErrorDetails"
                                class="text-sm text-red-600 hover:text-red-800 transition"
                            >
                                {{
                                    showErrorDetails
                                        ? t("common.hideDetails")
                                        : t("common.showDetails")
                                }}
                            </button>
                        </div>

                        <div
                            v-if="showErrorDetails"
                            class="space-y-2 max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="(error, index) in importResults.errors"
                                :key="index"
                                class="bg-white border border-red-200 rounded p-3 text-sm"
                            >
                                <div class="flex items-start space-x-2">
                                    <svg
                                        class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"
                                        />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="font-medium text-red-900">
                                            Row {{ error.row }}
                                        </p>
                                        <p class="text-red-700">
                                            {{ error.error }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="downloadErrorReport"
                            v-if="importResults.failed_imports > 0"
                            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition flex items-center"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            {{ t("modal.importCSV.downloadErrorReport") }}
                        </button>
                        <button
                            @click="resetImport"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition"
                        >
                            {{ t("modal.importCSV.importAnotherFile") }}
                        </button>
                    </div>
                </div>
            </div>

            <template #footer v-if="!importResults">
                <div class="flex justify-end">
                    <button
                        @click="closeImportModal"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition"
                    >
                        {{ t("common.cancel") }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import axios from "axios";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import SimplePagination from "@/Components/Common/SimplePagination.vue";
import UserInitials from "@/Components/UI/UserInitials.vue";

const { t, locale } = useI18n();

const page = usePage();

// Props from controller
const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 15, current_page: 1 }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({}),
    },
});

// Initialize users data properly - ensure we handle the props correctly
const users = reactive({
    data: props.users?.data || [],
    total: props.users?.total || 0,
    per_page: props.users?.per_page || 15,
    current_page: props.users?.current_page || 1,
    from: props.users?.from || 0,
    to: props.users?.to || 0,
});

// Watch for prop changes and update local ref
watch(
    () => props.users,
    (newUsers) => {
        if (newUsers) {
            console.log("Props users updated:", newUsers);
            users.data = newUsers.data || [];
            users.total = newUsers.total || 0;
            users.per_page = newUsers.per_page || 15;
            users.current_page = newUsers.current_page || 1;
            users.from = newUsers.from || 0;
            users.to = newUsers.to || 0;
        }
    },
    { immediate: true, deep: true }
);

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const formSubmitting = ref(false);
const showPassword = ref(false);

// Validation states
const validationStatus = ref({
    nip: null, // null | 'checking' | 'valid' | 'invalid'
    email: null,
});

const validationMessages = ref({
    nip: "",
    email: "",
});

// Debounce utility
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// CSV Import state
const showImportModal = ref(false);
const importFile = ref(null);
const importProgress = ref(false);
const importResults = ref(null);
const showErrorDetails = ref(false);
const isDragging = ref(false);
const fileInput = ref(null);

// Pagination - Use server-side pagination
const currentPage = ref(props.users?.current_page || 1);
const itemsPerPage = ref(props.users?.per_page || 15);

// Form data
const form = ref({
    id: null,
    nip: "",
    name: "",
    email: "",
    role: "user",
    password: "",
    password_confirmation: "",
    status: "active",
    phone: "",
    department: "",
    position: "",
    table_type: "users",
});

// Filters
const searchQuery = ref(props.filters?.search || "");
const filterRole = ref(props.filters?.role || "");
const filterStatus = ref(props.filters?.status || "");

// Computed properties for stats
const activeUsersCount = computed(() => {
    return users.data?.filter((user) => user.status === "active").length || 0;
});

const inactiveUsersCount = computed(() => {
    return users.data?.filter((user) => user.status === "inactive").length || 0;
});

const newUsersThisMonth = computed(() => {
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    return (
        users.data?.filter((user) => {
            const createdAt = new Date(user.created_at);
            return (
                createdAt.getMonth() === currentMonth &&
                createdAt.getFullYear() === currentYear
            );
        }).length || 0
    );
});

// Computed properties - use server-side data directly
const totalPages = computed(() => {
    const total = users.total || 0;
    const perPage = itemsPerPage.value || 15;
    return Math.ceil(total / perPage);
});

const paginatedUsers = computed(() => {
    return users.data || [];
});

// Function to load users with filters
const loadUsers = async () => {
    const params = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (filterRole.value) params.role = filterRole.value;
    if (filterStatus.value) params.status = filterStatus.value;
    if (currentPage.value > 1) params.page = currentPage.value;

    try {
        await router.get("/admin/users-management", params, {
            preserveState: true,
            preserveScroll: true,
            only: ["users"],
            onSuccess: (page) => {
                console.log("Load users success:", page.props.users);
                // The watcher will handle updating users.value
            },
        });
    } catch (error) {
        console.error("Error loading users:", error);
    }
};

// Client-side NIP format validation (matches backend ValidNipFormat rule)
const validateNIPFormat = (nip) => {
    // Remove any non-digit characters
    const cleanedNip = nip.replace(/\D/g, "");

    // Check if exactly 18 digits
    if (cleanedNip.length !== 18) {
        return { valid: false, message: t("validation.nip.length") };
    }

    // Check if all digits
    if (!/^\d+$/.test(cleanedNip)) {
        return { valid: false, message: t("validation.nip.numeric") };
    }

    // Validate birth date (first 8 digits in YYYYMMDD format)
    const birthDate = cleanedNip.substring(0, 8);
    const year = parseInt(birthDate.substring(0, 4));
    const month = parseInt(birthDate.substring(4, 6));
    const day = parseInt(birthDate.substring(6, 8));

    // Validate year range
    if (year < 1920 || year > 2050) {
        return {
            valid: false,
            message: t("validation.nip.invalidYear"),
        };
    }

    // Validate month
    if (month < 1 || month > 12) {
        return {
            valid: false,
            message: t("validation.nip.invalidMonth"),
        };
    }

    // Validate day based on month
    let maxDays = 31;
    if ([4, 6, 9, 11].includes(month)) {
        maxDays = 30;
    } else if (month === 2) {
        // Check for leap year
        const isLeapYear =
            (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
        maxDays = isLeapYear ? 29 : 28;
    }

    if (day < 1 || day > maxDays) {
        return {
            valid: false,
            message: t("validation.nip.invalidDay"),
        };
    }

    return { valid: true, message: "" };
};

// Validation functions with client-side checks
const validateNIP = debounce(async () => {
    if (!form.value.nip || isEditing.value) return;

    const nip = form.value.nip.trim();
    if (nip.length < 5) {
        validationStatus.value.nip = null;
        validationMessages.value.nip = "";
        return;
    }

    // Client-side format validation first (instant feedback)
    const formatCheck = validateNIPFormat(nip);
    if (!formatCheck.valid) {
        validationStatus.value.nip = "invalid";
        validationMessages.value.nip = formatCheck.message;
        return;
    }

    // If format is valid, check availability via API
    validationStatus.value.nip = "checking";
    validationMessages.value.nip = t("validation.nip.checkingAvailability");

    try {
        const response = await fetch("/admin/users/check-nip", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ nip }),
        });

        const data = await response.json();

        if (data.valid && data.available) {
            validationStatus.value.nip = "valid";
            validationMessages.value.nip = t(
                "validation.nip.validAndAvailable"
            );
        } else {
            validationStatus.value.nip = "invalid";
            validationMessages.value.nip =
                data.message || t("validation.nip.alreadyRegistered");
        }
    } catch (error) {
        console.error("NIP validation error:", error);
        validationStatus.value.nip = "invalid";
        validationMessages.value.nip = t("validation.nip.checkFailed");
    }
}, 500);

// Client-side email format validation (more comprehensive)
const validateEmailFormat = (email) => {
    // Check if empty
    if (!email || email.trim() === "") {
        return { valid: false, message: t("validation.email.required") };
    }

    // Check length
    if (email.length > 255) {
        return { valid: false, message: t("validation.email.maxLength") };
    }

    // Comprehensive email regex
    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (!emailRegex.test(email)) {
        return { valid: false, message: t("validation.email.invalidFormat") };
    }

    // Additional checks
    if (email.includes("..")) {
        return {
            valid: false,
            message: t("validation.email.doubleChar"),
        };
    }

    if (email.startsWith(".") || email.startsWith("-")) {
        return {
            valid: false,
            message: t("validation.email.leadingChar"),
        };
    }

    return { valid: true, message: "" };
};

const validateEmail = debounce(async () => {
    if (!form.value.email) {
        validationStatus.value.email = null;
        validationMessages.value.email = "";
        return;
    }

    const email = form.value.email.trim().toLowerCase();

    // Client-side format validation first (instant feedback)
    const formatCheck = validateEmailFormat(email);
    if (!formatCheck.valid) {
        validationStatus.value.email = "invalid";
        validationMessages.value.email = formatCheck.message;
        return;
    }

    // Skip check if editing and email hasn't changed
    if (isEditing.value) {
        const originalUser = users.data.find((u) => u.nip === form.value.nip);
        if (originalUser && originalUser.email.toLowerCase() === email) {
            validationStatus.value.email = "valid";
            validationMessages.value.email = "";
            return;
        }
    }

    // If format is valid, check availability via API
    validationStatus.value.email = "checking";
    validationMessages.value.email = t("validation.email.checkingAvailability");

    try {
        const response = await fetch("/admin/users/check-email", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ email }),
        });

        const data = await response.json();

        if (data.valid && data.available) {
            validationStatus.value.email = "valid";
            validationMessages.value.email = t(
                "validation.email.validAndAvailable"
            );
        } else {
            validationStatus.value.email = "invalid";
            validationMessages.value.email =
                data.message || t("validation.email.alreadyRegistered");
        }
    } catch (error) {
        console.error("Email validation error:", error);
        validationStatus.value.email = "invalid";
        validationMessages.value.email = t("validation.email.checkFailed");
    }
}, 500);

// Watch for NIP changes
watch(
    () => form.value.nip,
    () => {
        if (!isEditing.value) {
            validationStatus.value.nip = null;
            validationMessages.value.nip = "";
            validateNIP();
        }
    }
);

// Watch for email changes
watch(
    () => form.value.email,
    () => {
        validationStatus.value.email = null;
        validationMessages.value.email = "";
        validateEmail();
    }
);

// Watch for filter changes and reload data
const skipInitialLoad = ref(true);
watch(
    [searchQuery, filterRole, filterStatus],
    () => {
        if (!skipInitialLoad.value) {
            currentPage.value = 1; // Reset to first page when filters change
            loadUsers();
        }
    },
    { debounce: 300 }
);

// Watch for page changes from server
watch(
    () => props.users?.current_page,
    (newPage) => {
        if (newPage && newPage !== currentPage.value) {
            currentPage.value = newPage;
            console.log("Page updated from server:", newPage);
        }
    }
);

// Watch for total changes from server
watch(
    () => props.users?.total,
    (newTotal) => {
        if (newTotal !== undefined && newTotal !== (users.total || 0)) {
            users.total = newTotal;
            console.log("Total updated from server:", newTotal);
        }
    }
);

// Helper function for notifications
const showNotification = (message, type = "info") => {
    if (typeof $notify !== "undefined") {
        $notify[type](message);
    } else {
        alert(message);
    }
};

// Methods
const openCreateModal = () => {
    resetForm();
    isEditing.value = false;
    showModal.value = true;
};

const openEditModal = (user) => {
    form.value = {
        ...user,
        password: "",
        password_confirmation: "",
        table_type: user.table_type || "users",
    };
    isEditing.value = true;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        id: null,
        nip: "",
        name: "",
        email: "",
        role: "user",
        password: "",
        password_confirmation: "",
        status: "active",
        phone: "",
        department: "",
        position: "",
        table_type: "users",
    };
    showPassword.value = false;
    formSubmitting.value = false;
    // Clear validation states
    validationStatus.value = {
        nip: null,
        email: null,
    };
    validationMessages.value = {
        nip: "",
        email: "",
    };
};

const submitForm = async () => {
    formSubmitting.value = true;

    try {
        // Prepare form data with proper validation
        const formData = { ...form.value };

        // Remove fields that shouldn't be sent to backend
        delete formData.id; // Don't send id field
        delete formData.table_type; // Don't send table_type field

        // Check real-time validation status for new users
        if (!isEditing.value) {
            if (validationStatus.value.nip === "invalid") {
                showNotification(t("message.nipAlreadyRegistered"), "error");
                formSubmitting.value = false;
                return;
            }

            if (validationStatus.value.nip === "checking") {
                showNotification(t("message.nipValidationPending"), "warning");
                formSubmitting.value = false;
                return;
            }

            if (validationStatus.value.email === "invalid") {
                showNotification(t("message.emailAlreadyRegistered"), "error");
                formSubmitting.value = false;
                return;
            }

            if (validationStatus.value.email === "checking") {
                showNotification(
                    t("message.emailValidationPending"),
                    "warning"
                );
                formSubmitting.value = false;
                return;
            }
        }

        // Validate password and confirmation
        if (!isEditing.value) {
            // For new users, password is required
            if (!formData.password) {
                showNotification(t("message.passwordRequiredNewUser"), "error");
                formSubmitting.value = false;
                return;
            }

            if (!formData.password_confirmation) {
                showNotification(
                    t("message.passwordConfirmationRequired"),
                    "error"
                );
                formSubmitting.value = false;
                return;
            }

            if (formData.password !== formData.password_confirmation) {
                showNotification(t("message.passwordMismatch"), "error");
                formSubmitting.value = false;
                return;
            }
        } else {
            // For editing users
            if (!formData.password) {
                // No password provided, remove password fields
                delete formData.password;
                delete formData.password_confirmation;
            } else {
                // Password provided for editing, check confirmation
                if (
                    !formData.password_confirmation ||
                    formData.password !== formData.password_confirmation
                ) {
                    showNotification(t("message.passwordMismatch"), "error");
                    formSubmitting.value = false;
                    return;
                }
            }
        }

        // Log what we're sending for debugging
        console.log("Submitting user data:", formData);

        if (isEditing.value) {
            // Update existing user - use NIP as identifier
            const response = await axios.put(
                `/admin/users/${form.value.nip}`,
                formData
            );

            if (response.data.success) {
                // Update user in the local list
                const index = users.data.findIndex(
                    (u) => u.nip === form.value.nip
                );
                if (index !== -1 && response.data.user) {
                    users.data[index] = {
                        ...users.data[index],
                        ...response.data.user,
                        role_label: getRoleLabel(response.data.user.role),
                        status_badge: getStatusBadge(response.data.user.status),
                    };
                }
            }
        } else {
            // Create new user
            const response = await axios.post("/admin/users", formData);

            if (response.data.success && response.data.user) {
                users.data.unshift(response.data.user);
            }
        }

        closeModal();
        // Reload the page to refresh the user list
        await router.reload({ only: ["users"] });

        // Show success notification
        const successMessage = isEditing.value
            ? t("message.userUpdated")
            : t("message.userAdded");
        showNotification(successMessage, "success");
    } catch (error) {
        console.error("Error saving user:", error);
        console.error("Error response data:", error.response?.data);
        console.error("Full error details:", {
            status: error.response?.status,
            errors: error.response?.data?.errors,
            validation_errors: error.response?.data?.validation_errors,
            message: error.response?.data?.message,
        });

        let errorMessage = t("message.saveUserFailed");

        if (error.response?.status === 422) {
            // Validation errors
            const errors = error.response.data.errors;
            console.error("Validation errors:", errors);

            if (errors && Array.isArray(errors)) {
                errorMessage = errors.join(", ");
            } else if (typeof errors === "object") {
                errorMessage = Object.values(errors).flat().join(", ");
            } else {
                errorMessage = error.response.data.message || errorMessage;
            }
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }

        showNotification(errorMessage, "error");
    } finally {
        formSubmitting.value = false;
    }
};

const toggleUserStatus = async (user) => {
    if (!user.can_toggle_status) {
        const message =
            user.role === "admin_helpdesk"
                ? t("message.adminHelpdeskStatusImmutable")
                : t("message.cannotChangeUserStatus");

        showNotification(message, "warning");
        return;
    }

    const newStatus = user.status === "active" ? "inactive" : "active";
    const actionText =
        newStatus === "active" ? t("common.activate") : t("common.deactivate");

    if (!confirm(`Apakah Anda yakin ingin ${actionText} ${user.name}?`)) {
        return;
    }

    try {
        const response = await axios.post(
            `/admin/users/${user.nip}/toggle-status`,
            {
                status: newStatus,
            }
        );

        if (response.data.success) {
            // Update user in the local list
            const index = users.data.findIndex((u) => u.nip === user.nip);
            if (index !== -1) {
                users.data[index] = {
                    ...users.data[index],
                    status: newStatus,
                    status_badge: getStatusBadge(newStatus),
                };
            }

            const message =
                response.data.message || `Pengguna ${actionText} berhasil`;
            showNotification(message, "success");
        } else {
            showNotification(
                response.data.message || t("message.toggleStatusFailed"),
                "error"
            );
        }
    } catch (error) {
        console.error("Error toggling user status:", error);
        let errorMessage = "Gagal mengubah status pengguna";

        if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            if (errors && Array.isArray(errors)) {
                errorMessage = errors.join(", ");
            } else if (typeof errors === "object") {
                errorMessage = Object.values(errors).flat().join(", ");
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }

        showNotification(errorMessage, "error");
    }
};

const resetUserPassword = async (user) => {
    if (!user.can_edit) {
        showNotification(t("message.cannotResetPassword"), "warning");
        return;
    }

    const confirmationMessage = t("message.confirmResetPassword", {
        name: user.name,
    });
    if (!confirm(confirmationMessage)) {
        return;
    }

    try {
        const response = await axios.post(
            `/admin/users/${user.nip}/reset-password`
        );

        if (response.data.success) {
            const { user_info, password_info } = response.data;

            // Create a detailed success message with password information
            const passwordDetails = t("message.passwordResetDetails", {
                user_name: user_info.name,
                user_nip: user_info.nip,
                user_email: user_info.email,
                user_role: user_info.role,
                new_password: password_info.password,
                password_format: password_info.format,
                password_length: password_info.length,
                password_strength: password_info.strength,
                security_level: password_info.security_level,
                expires_at: new Date(password_info.expires_at).toLocaleString(
                    locale.value,
                    {
                        weekday: "long",
                        year: "numeric",
                        month: "long",
                        day: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                    }
                ),
                instructions: password_info.instructions,
            });

            // Show the password in an alert for copying
            alert(passwordDetails);

            // Also show a success notification with quick summary
            showNotification(
                t("message.passwordResetSuccess", {
                    name: user_info.name,
                    password: password_info.password,
                }),
                "success"
            );

            console.log("Password reset details:", {
                user: user_info,
                password: password_info,
                reset_time: new Date().toISOString(),
            });
        } else {
            const errors = response.data.errors || [
                t("message.resetPasswordFailed"),
            ];
            showNotification(
                Array.isArray(errors) ? errors.join(", ") : errors,
                "error"
            );
        }
    } catch (error) {
        console.error("Error resetting password:", error);
        let errorMessage = "Gagal mereset password pengguna";

        if (error.response?.status === 500) {
            // Server error - provide more detailed debugging info
            const debugInfo = error.response.data?.debug_info || {};
            errorMessage = t("message.serverErrorResetPassword", {
                error_type: debugInfo.error_type || t("common.unknownError"),
            });

            console.error("Server error details:", {
                status: error.response.status,
                debug_info: debugInfo,
                user_nip: user.nip,
            });
        } else if (error.response?.status === 422) {
            // Validation errors
            const errors = error.response.data.errors;
            if (errors && Array.isArray(errors)) {
                errorMessage = errors.join(", ");
            } else if (typeof errors === "object") {
                errorMessage = Object.values(errors).flat().join(", ");
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        } else if (error.code === "NETWORK_ERROR") {
            errorMessage = t("message.networkError");
        }

        showNotification(errorMessage, "error");
    }
};

const resetFilters = () => {
    searchQuery.value = "";
    filterRole.value = "";
    filterStatus.value = "";
    currentPage.value = 1;
    loadUsers();
};

const handlePageChange = (page) => {
    currentPage.value = page;
    loadUsers();
};

const getRoleLabel = (role) => {
    const labels = {
        user: "User",
        admin_helpdesk: "Admin Helpdesk",
        admin_aplikasi: "Admin Aplikasi",
        teknisi: "Teknisi",
    };
    return labels[role] || role;
};

const getRoleBadgeClass = (role) => {
    const classes = {
        user: "bg-blue-100 text-blue-800",
        admin_helpdesk: "bg-red-100 text-red-800",
        admin_aplikasi: "bg-purple-100 text-purple-800",
        teknisi: "bg-green-100 text-green-800",
    };
    return classes[role] || "bg-gray-100 text-gray-800";
};

const getStatusBadge = (status) => {
    const badges = {
        active: "bg-green-100 text-green-800",
        inactive: "bg-red-100 text-red-800",
    };
    return badges[status] || "bg-gray-100 text-gray-800";
};

const formatRole = (role) => {
    const roleMap = {
        user: "User (Pegawai)",
        admin_helpdesk: "Admin Helpdesk",
        admin_aplikasi: "Admin Aplikasi",
        teknisi: "Teknisi",
    };
    return roleMap[role] || role;
};

// CSV Import Methods
const openImportModal = () => {
    showImportModal.value = true;
    resetImport();
};

const closeImportModal = () => {
    showImportModal.value = false;
    resetImport();
};

const resetImport = () => {
    importFile.value = null;
    importProgress.value = false;
    importResults.value = null;
    showErrorDetails.value = false;
    isDragging.value = false;
};

const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const file = files[0];
        if (
            file.type === "text/csv" ||
            file.name.endsWith(".csv") ||
            file.name.endsWith(".txt")
        ) {
            importFile.value = file;
        } else {
            alert("Please upload a CSV file");
        }
    }
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        importFile.value = file;
    }
};

const removeFile = () => {
    importFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const downloadTemplate = () => {
    window.open("/admin/users/import-template", "_blank");
};

const uploadCsv = async () => {
    if (!importFile.value) {
        alert("Please select a file to import");
        return;
    }

    importProgress.value = true;

    const formData = new FormData();
    formData.append("csv_file", importFile.value);

    try {
        const response = await fetch("/admin/users/import", {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
            },
        });

        const result = await response.json();

        if (result.success) {
            importResults.value = result.results;
            // Refresh the users list
            router.reload({ only: ["users"] });
        } else {
            alert(result.message || "Import failed");
        }
    } catch (error) {
        console.error("Import error:", error);
        alert("Import failed: " + error.message);
    } finally {
        importProgress.value = false;
    }
};

const downloadErrorReport = () => {
    if (!importResults.value || !importResults.value.errors) return;

    const csvContent = [
        [t("common.row"), t("common.error"), t("common.data")],
        ...importResults.value.errors.map((error) => [
            error.row,
            error.error,
            JSON.stringify(error.data),
        ]),
    ]
        .map((row) => row.join(","))
        .join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `import_errors_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
};

// Load initial data
onMounted(() => {
    // Data is already loaded via Inertia
    console.log("User Management loaded:", users);
    console.log("Props users:", props.users);

    // Enable watchers after initial load
    setTimeout(() => {
        skipInitialLoad.value = false;
    }, 100);
});
</script>

<style scoped>
/* Enhanced Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

/* Animation Classes */
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

.animate-slideInDown {
    animation: slideInDown 0.5s ease-out;
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}

/* Glass morphism effect enhancement */
.bg-white\/90 {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Hover lift effect */
.hover-lift {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Enhanced responsive improvements */
@media (max-width: 640px) {
    .min-h-screen {
        min-height: 100vh;
        min-height: 100svh; /* Better mobile support */
    }

    /* Adjust text sizes for better mobile readability */
    .text-3xl {
        font-size: 1.875rem;
        line-height: 2.25rem;
    }

    .text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
    }

    /* Better touch targets on mobile */
    button {
        min-height: 44px;
        min-width: 44px;
    }

    select {
        min-height: 44px;
    }
}

/* Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .border-gray-300 {
        border-color: #000000;
    }

    .text-gray-600 {
        color: #000000;
    }

    .bg-gray-50 {
        background-color: #ffffff;
    }
}

/* Focus management for accessibility */
select:focus-visible,
button:focus-visible {
    outline: 2px solid #4f46e5;
    outline-offset: 2px;
}

/* Custom scrollbar for better aesthetics */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Disabled button styling */
button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #9ca3af !important;
    border-color: #9ca3af !important;
}

button:disabled:hover {
    background-color: #9ca3af !important;
    transform: none !important;
}
</style>

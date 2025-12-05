<template>
    <AppLayout role="admin">
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
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1
                                class="text-3xl sm:text-4xl font-bold text-gray-900"
                            >
                                {{ t('nav.ticketManagement') }}
                            </h1>
                            <p
                                class="text-gray-600 text-sm sm:text-base animate-fadeInUp animation-delay-200"
                            >
                                {{ t('adminHelpdesk.ticketManagement.description') }}
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
                            <span>{{ t('dashboard.admin.systemOnline') }}</span>
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
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="showCreateModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ t('ticket.createNewTicket') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ t('activityLog.filters') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.status') }}</label>
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">{{ t('common.allStatuses') }}</option>
                        <option
                            v-for="status in filterOptions.statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.priority') }}</label>
                    <select
                        v-model="filters.priority"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">{{ t('common.allPriority') }}</option>
                        <option
                            v-for="priority in filterOptions.priorities"
                            :key="priority.value"
                            :value="priority.value"
                        >
                            {{ priority.label }}
                        </option>
                    </select>
                </div>

                <!-- Application Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.application') }}</label>
                    <select
                        v-model="filters.aplikasi_id"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">{{ t('common.allApplications') }}</option>
                        <option
                            v-for="app in filterOptions.applications"
                            :key="app.value"
                            :value="app.value"
                        >
                            {{ app.label }}
                        </option>
                    </select>
                </div>

                <!-- Teknisi Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('nav.teknisi') }}</label>
                    <select
                        v-model="filters.assigned_teknisi_nip"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">{{ t('common.allTeknisi') }}</option>
                        <option
                            v-for="teknisi in filterOptions.teknisi"
                            :key="teknisi.value"
                            :value="teknisi.value"
                        >
                            {{ teknisi.label }}
                        </option>
                    </select>
                </div>

                <!-- Date From Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('time.dateFrom') }}</label>
                    <input
                        v-model="filters.date_from"
                        @change="applyFilters"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Date To Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('time.dateTo') }}</label>
                    <input
                        v-model="filters.date_to"
                        @change="applyFilters"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>
            </div>

            <!-- Search Bar -->
            <div class="mt-4 flex items-center space-x-4">
                <div class="flex-1">
                    <input
                        v-model="filters.search"
                        @input="debounceSearch"
                        type="text"
                        :placeholder="t('search.searchTickets')"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>
                <button
                    @click="clearFilters"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                >
                    {{ t('common.clearFilters') }}
                </button>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ t('nav.tickets') }} ({{ tickets.total }})
                    </h2>
                    <div class="flex items-center space-x-2">
                        <label class="flex items-center">
                            <input
                                v-model="selectAll"
                                @change="toggleSelectAll"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm text-gray-600"
                                >{{ t('common.selectAll') }}</span
                            >
                        </label>
                        <button
                            v-if="selectedTickets.length > 0"
                            @click="showBulkActionModal = true"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            {{ t('common.bulkActions') }} ({{ selectedTickets.length }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                <input
                                    v-model="selectAll"
                                    @change="toggleSelectAll"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.ticketNumber') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.ticketTitle') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('common.user') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.status') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.priority') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.application') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('ticket.assignedTo') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('common.created') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ t('action.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    v-model="selectedTickets"
                                    :value="ticket.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900"
                            >
                                {{ ticket.ticket_number }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate">
                                    {{ ticket.title }}
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                            >
                                {{ ticket.user?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getStatusColor(ticket.status),
                                    ]"
                                >
                                    {{ ticket.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getPriorityColor(ticket.priority),
                                    ]"
                                >
                                    {{ ticket.priority_label }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                            >
                                {{ ticket.aplikasi?.name }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                            >
                                {{ ticket.assigned_teknisi?.name || t('common.unassigned') }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                            >
                                {{ ticket.formatted_created_at }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                            >
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click.stop="viewTicket(ticket.id)"
                                        class="text-indigo-600 hover:text-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded p-1"
                                        :title="t('common.view')"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="editTicket(ticket)"
                                        class="text-blue-600 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded p-1"
                                        :title="t('common.edit')"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="assignTicket(ticket)"
                                        class="text-green-600 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 rounded p-1"
                                        :title="t('ticket.assignTicket')"
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
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <SimplePagination
                :data="tickets"
                :label="t('common.tickets')"
                @page-changed="handlePageChange"
            />
        </div>

        <!-- Create Ticket Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showCreateModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @click="handleBackdropClick"
                >
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <div
                            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                        ></div>
                        <div
                            class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all z-10 w-full sm:max-w-2xl"
                            @click.stop
                        >
                            <form @submit.prevent="createTicket">
                                <!-- Header -->
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h3
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ t('ticket.createNewTicket') }}
                                        </h3>
                                        <button
                                            type="button"
                                            @click="showCreateModal = false"
                                            class="flex-shrink-0 p-2 rounded-full hover:bg-gray-100"
                                        >
                                            <svg
                                                class="h-5 w-5 text-gray-400"
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
                                </div>

                                <!-- Body -->
                                <div
                                    class="px-6 py-4 max-h-[60vh] overflow-y-auto"
                                >
                                    <div class="grid grid-cols-1 gap-4">
                                        <!-- User Selection -->
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('common.user') }} *</label
                                            >
                                            <select
                                                v-model="newTicket.user_nip"
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="">
                                                    {{ t('modal.selectUser') }}
                                                </option>
                                                <option
                                                    v-for="user in users"
                                                    :key="user.nip"
                                                    :value="user.nip"
                                                >
                                                    {{ user.name }} ({{
                                                        user.nip
                                                    }}) - {{ user.department }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Application Selection -->
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.application') }} *</label
                                            >
                                            <select
                                                v-model="newTicket.aplikasi_id"
                                                @change="loadCategories"
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="">
                                                    {{ t('ticket.selectApplication') }}
                                                </option>
                                                <option
                                                    v-for="app in applications"
                                                    :key="app.id"
                                                    :value="app.id"
                                                >
                                                    {{ app.name }} ({{
                                                        app.code
                                                    }})
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Category Selection -->
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.category') }} *</label
                                            >
                                            <select
                                                v-model="
                                                    newTicket.kategori_masalah_id
                                                "
                                                required
                                                :disabled="
                                                    !newTicket.aplikasi_id
                                                "
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
                                            >
                                                <option value="">
                                                    {{ t('ticket.selectCategory') }}
                                                </option>
                                                <option
                                                    v-for="category in availableCategories"
                                                    :key="category.id"
                                                    :value="category.id"
                                                >
                                                    {{ category.name }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Title -->
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.ticketTitle') }} *</label
                                            >
                                            <input
                                                v-model="newTicket.title"
                                                required
                                                type="text"
                                                maxlength="255"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                :placeholder="t('ticket.issueTitlePlaceholder')"
                                            />
                                        </div>

                                        <!-- Description -->
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.description') }} *</label
                                            >
                                            <textarea
                                                v-model="newTicket.description"
                                                required
                                                rows="4"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                :placeholder="t('ticket.detailedDescriptionPlaceholder')"
                                            ></textarea>
                                        </div>

                                        <!-- Priority and Assignment -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-2"
                                                    >{{ t('ticket.priority') }} *</label
                                                >
                                                <select
                                                    v-model="newTicket.priority"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                >
                                                    <option value="">
                                                        {{ t('modal.selectPriority') }}
                                                    </option>
                                                    <option value="low">
                                                        {{ t('priority.low') }}
                                                    </option>
                                                    <option value="medium">
                                                        {{ t('priority.medium') }}
                                                    </option>
                                                    <option value="high">
                                                        {{ t('priority.high') }}
                                                    </option>
                                                    <option value="urgent">
                                                        {{ t('priority.urgent') }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-2"
                                                    >{{ t('ticket.assignTo') }} {{ t('nav.teknisi') }}</label
                                                >
                                                <select
                                                    v-model="
                                                        newTicket.assigned_teknisi_nip
                                                    "
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                >
                                                    <option value="">
                                                        {{ t('modal.assignTicketModal.selectTeknisi') }}
                                                    </option>
                                                    <option
                                                        v-for="teknisi in teknisis"
                                                        :key="teknisi.nip"
                                                        :value="teknisi.nip"
                                                    >
                                                        {{ teknisi.name }} ({{
                                                            teknisi.nip
                                                        }})
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Location -->
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.location') }}</label
                                            >
                                            <input
                                                v-model="newTicket.location"
                                                type="text"
                                                maxlength="255"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                :placeholder="t('ticket.locationPlaceholder')"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3"
                                >
                                    <button
                                        type="button"
                                        @click="showCreateModal = false"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="creating"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        <span v-if="creating">{{ t('common.creating') }}</span>
                                        <span v-else>{{ t('ticket.createTicket') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Edit Ticket Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showEditModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @click="handleBackdropClick"
                >
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <div
                            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                        ></div>
                        <div
                            class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all z-10 w-full sm:max-w-lg"
                            @click.stop
                        >
                            <form @submit.prevent="updateTicket">
                                <!-- Header -->
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h3
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ t('ticket.editTicket') }}
                                        </h3>
                                        <button
                                            type="button"
                                            @click="showEditModal = false"
                                            class="flex-shrink-0 p-2 rounded-full hover:bg-gray-100"
                                        >
                                            <svg
                                                class="h-5 w-5 text-gray-400"
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
                                </div>

                                <!-- Body -->
                                <div class="px-6 py-4">
                                    <div class="space-y-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.status') }}</label
                                            >
                                            <select
                                                v-model="editingTicket.status"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="open">
                                                    {{ t('status.open') }}
                                                </option>
                                                <option value="assigned">
                                                    {{ t('status.assigned') }}
                                                </option>
                                                <option value="in_progress">
                                                    {{ t('status.inProgress') }}
                                                </option>
                                                <option value="waiting_user">
                                                    {{ t('status.waitingUser') }}
                                                </option>
                                                <option value="waiting_admin">
                                                    {{ t('status.waitingAdmin') }}
                                                </option>
                                                <option
                                                    value="waiting_response"
                                                >
                                                    {{ t('status.waitingResponse') }}
                                                </option>
                                                <option value="resolved">
                                                    {{ t('status.resolved') }}
                                                </option>
                                                <option value="closed">
                                                    {{ t('status.closed') }}
                                                </option>
                                                <option value="cancelled">
                                                    {{ t('status.cancelled') }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.priority') }}</label
                                            >
                                            <select
                                                v-model="editingTicket.priority"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="low">{{ t('priority.low') }}</option>
                                                <option value="medium">
                                                    {{ t('priority.medium') }}
                                                </option>
                                                <option value="high">
                                                    {{ t('priority.high') }}
                                                </option>
                                                <option value="urgent">
                                                    {{ t('priority.urgent') }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.assignTo') }} {{ t('nav.teknisi') }}</label
                                            >
                                            <select
                                                v-model="
                                                    editingTicket.assigned_teknisi_nip
                                                "
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="">
                                                    {{ t('common.unassigned') }}
                                                </option>
                                                <option
                                                    v-for="teknisi in teknisis"
                                                    :key="teknisi.nip"
                                                    :value="teknisi.nip"
                                                >
                                                    {{ teknisi.name }} ({{
                                                        teknisi.nip
                                                    }})
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.notes') }}</label
                                            >
                                            <textarea
                                                v-model="editingTicket.notes"
                                                rows="3"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                :placeholder="t('ticket.addNotesPlaceholder')"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3"
                                >
                                    <button
                                        type="button"
                                        @click="showEditModal = false"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="updating"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        <span v-if="updating">{{ t('common.updating') }}</span>
                                        <span v-else>{{ t('ticket.updateTicket') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Assign Ticket Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showAssignModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @click="handleBackdropClick"
                >
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <div
                            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                        ></div>
                        <div
                            class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all z-10 w-full sm:max-w-md"
                            @click.stop
                        >
                            <form @submit.prevent="performAssignment">
                                <!-- Header -->
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h3
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ t('ticket.assignTicket') }}
                                        </h3>
                                        <button
                                            type="button"
                                            @click="showAssignModal = false"
                                            class="flex-shrink-0 p-2 rounded-full hover:bg-gray-100"
                                        >
                                            <svg
                                                class="h-5 w-5 text-gray-400"
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
                                </div>

                                <!-- Body -->
                                <div class="px-6 py-4">
                                    <div class="space-y-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.ticket') }}</label
                                            >
                                            <div
                                                class="p-3 bg-gray-50 rounded-md"
                                            >
                                                <p
                                                    class="font-medium text-gray-900"
                                                >
                                                    {{
                                                        assigningTicket?.ticket_number
                                                    }}
                                                </p>
                                                <p
                                                    class="text-sm text-gray-600"
                                                >
                                                    {{ assigningTicket?.title }}
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.assignTo') }} {{ t('nav.teknisi') }} *</label
                                            >
                                            <select
                                                v-model="
                                                    assignmentData.teknisi_nip
                                                "
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="">
                                                    {{ t('modal.assignTicketModal.selectTeknisi') }}
                                                </option>
                                                <option
                                                    v-for="teknisi in teknisis"
                                                    :key="teknisi.nip"
                                                    :value="teknisi.nip"
                                                >
                                                    {{ teknisi.name }} ({{
                                                        teknisi.nip
                                                    }}) -
                                                    {{ teknisi.department }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('modal.assignTicketModal.assignmentNotes') }}</label
                                            >
                                            <textarea
                                                v-model="assignmentData.notes"
                                                rows="3"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                :placeholder="t('modal.assignTicketModal.addNotesPlaceholder')"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3"
                                >
                                    <button
                                        type="button"
                                        @click="showAssignModal = false"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="assigning"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        <span v-if="assigning"
                                            >{{ t('ticket.assigning') }}</span
                                        >
                                        <span v-else>{{ t('ticket.assignTicket') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Bulk Action Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showBulkActionModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @click="handleBackdropClick"
                >
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <div
                            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                        ></div>
                        <div
                            class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all z-10 w-full sm:max-w-lg"
                            @click.stop
                        >
                            <form @submit.prevent="performBulkAction">
                                <!-- Header -->
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h3
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            {{ t('common.bulkActions') }}
                                        </h3>
                                        <button
                                            type="button"
                                            @click="showBulkActionModal = false"
                                            class="flex-shrink-0 p-2 rounded-full hover:bg-gray-100"
                                        >
                                            <svg
                                                class="h-5 w-5 text-gray-400"
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
                                </div>

                                <!-- Body -->
                                <div class="px-6 py-4">
                                    <div class="space-y-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('action.actions') }}</label
                                            >
                                            <select
                                                v-model="bulkAction.action"
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="">
                                                    {{ t('common.selectAction') }}
                                                </option>
                                                <option value="assign">
                                                    {{ t('actions.assignToTeknisi') }}
                                                </option>
                                                <option value="update_status">
                                                    {{ t('actions.updateStatus') }}
                                                </option>
                                                <option value="update_priority">
                                                    {{ t('actions.updatePriority') }}
                                                </option>
                                                <option value="close">
                                                    {{ t('actions.closeTickets') }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Conditional fields based on action -->
                                        <div
                                            v-if="
                                                bulkAction.action === 'assign'
                                            "
                                        >
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('nav.teknisi') }}</label
                                            >
                                            <select
                                                v-model="bulkAction.teknisi_nip"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="">
                                                    {{ t('modal.assignTicketModal.selectTeknisi') }}
                                                </option>
                                                <option
                                                    v-for="teknisi in teknisis"
                                                    :key="teknisi.nip"
                                                    :value="teknisi.nip"
                                                >
                                                    {{ teknisi.name }} ({{
                                                        teknisi.nip
                                                    }})
                                                </option>
                                            </select>
                                        </div>

                                        <div
                                            v-if="
                                                bulkAction.action ===
                                                'update_status'
                                            "
                                        >
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.status') }}</label
                                            >
                                            <select
                                                v-model="bulkAction.status"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="open">
                                                    {{ t('status.open') }}
                                                </option>
                                                <option value="assigned">
                                                    {{ t('status.assigned') }}
                                                </option>
                                                <option value="in_progress">
                                                    {{ t('status.inProgress') }}
                                                </option>
                                                <option value="waiting_user">
                                                    {{ t('status.waitingUser') }}
                                                </option>
                                                <option value="waiting_admin">
                                                    {{ t('status.waitingAdmin') }}
                                                </option>
                                                <option
                                                    value="waiting_response"
                                                >
                                                    {{ t('status.waitingResponse') }}
                                                </option>
                                                <option value="resolved">
                                                    {{ t('status.resolved') }}
                                                </option>
                                                <option value="closed">
                                                    {{ t('status.closed') }}
                                                </option>
                                                <option value="cancelled">
                                                    {{ t('status.cancelled') }}
                                                </option>
                                            </select>
                                        </div>

                                        <div
                                            v-if="
                                                bulkAction.action ===
                                                'update_priority'
                                            "
                                        >
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.priority') }}</label
                                            >
                                            <select
                                                v-model="bulkAction.priority"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="low">{{ t('priority.low') }}</option>
                                                <option value="medium">
                                                    {{ t('priority.medium') }}
                                                </option>
                                                <option value="high">
                                                    {{ t('priority.high') }}
                                                </option>
                                                <option value="urgent">
                                                    {{ t('priority.urgent') }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                                >{{ t('ticket.notes') }}</label
                                            >
                                            <textarea
                                                v-model="bulkAction.notes"
                                                rows="3"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                :placeholder="t('ticket.addNotesPlaceholder')"
                                            ></textarea>
                                        </div>

                                        <div class="p-3 bg-blue-50 rounded-md">
                                            <p class="text-sm text-blue-800">
                                                {{ t('modal.bulkActionModal.description', { count: selectedTickets.length }) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3"
                                >
                                    <button
                                        type="button"
                                        @click="showBulkActionModal = false"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="processingBulk"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        <span v-if="processingBulk"
                                            >{{ t('common.processing') }}</span
                                        >
                                        <span v-else>{{ t('common.applyAction') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import AppLayout from "@/Layouts/AppLayout.vue";
import SimplePagination from "@/Components/Common/SimplePagination.vue";

const { t, locale } = useI18n();

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({}),
    },
    users: {
        type: Array,
        default: () => [],
    },
    applications: {
        type: Array,
        default: () => [],
    },
    teknisis: {
        type: Array,
        default: () => [],
    },
});

// State
const selectedTickets = ref([]);
const selectAll = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showAssignModal = ref(false);
const showBulkActionModal = ref(false);
const creating = ref(false);
const updating = ref(false);
const assigning = ref(false);
const processingBulk = ref(false);

// Form data
const filters = ref({ ...props.filters });
const newTicket = ref({
    user_nip: "",
    aplikasi_id: "",
    kategori_masalah_id: "",
    title: "",
    description: "",
    priority: "",
    assigned_teknisi_nip: "",
    location: "",
});

const editingTicket = ref({});
const assigningTicket = ref(null);
const assignmentData = ref({
    teknisi_nip: "",
    notes: "",
});

const bulkAction = ref({
    action: "",
    teknisi_nip: "",
    status: "",
    priority: "",
    notes: "",
});

const availableCategories = ref([]);

// Computed
const debounceSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            applyFilters();
        }, 300);
    };
})();

// Methods
const getStatusColor = (status) => {
    const colors = {
        open: "bg-yellow-100 text-yellow-800",
        assigned: "bg-blue-100 text-blue-800",
        in_progress: "bg-indigo-100 text-indigo-800",
        waiting_user: "bg-orange-100 text-orange-800",
        waiting_admin: "bg-purple-100 text-purple-800",
        waiting_response: "bg-orange-100 text-orange-800",
        resolved: "bg-green-100 text-green-800",
        closed: "bg-gray-100 text-gray-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getPriorityColor = (priority) => {
    const colors = {
        low: "bg-gray-100 text-gray-800",
        medium: "bg-blue-100 text-blue-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };
    return colors[priority] || "bg-gray-100 text-gray-800";
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedTickets.value = props.tickets.data.map((ticket) => ticket.id);
    } else {
        selectedTickets.value = [];
    }
};

const applyFilters = () => {
    router.get(route("admin.tickets-management.index"), filters.value, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    filters.value = {
        status: "",
        priority: "",
        aplikasi_id: "",
        assigned_teknisi_nip: "",
        search: "",
        date_from: "",
        date_to: "",
    };
    applyFilters();
};

const handlePageChange = (page) => {
    const params = { ...filters.value, page };
    router.get(route("admin.tickets-management.index"), params, {
        preserveScroll: true,
    });
};

const viewTicket = (ticketId) => {
    router.visit(route("admin.tickets-management.show", ticketId));
};

const editTicket = (ticket) => {
    editingTicket.value = {
        id: ticket.id,
        status: ticket.status,
        priority: ticket.priority,
        assigned_teknisi_nip: ticket.assigned_teknisi?.nip || "",
        notes: "",
    };
    showEditModal.value = true;
};

const assignTicket = (ticket) => {
    assigningTicket.value = ticket;
    assignmentData.value = {
        teknisi_nip: "",
        notes: "",
    };
    showAssignModal.value = true;
};

const loadCategories = () => {
    const app = props.applications.find(
        (a) => a.id === newTicket.value.aplikasi_id
    );
    availableCategories.value = app ? app.kategori_masalahs : [];
};

const createTicket = async () => {
    creating.value = true;
    try {
        const response = await fetch(route("admin.tickets-management.store"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(newTicket.value),
        });

        const data = await response.json();

        if (data.success) {
            showCreateModal.value = false;
            resetNewTicketForm();
            // Reload the page to show the new ticket
            router.reload({
                only: ["tickets"],
                onSuccess: () => {
                    // You could show a success message here
                },
            });
        } else {
            alert(
                t('message.createTicketFailed') +
                    (data.errors?.join(", ") || t('message.unknownError'))
            );
        }
    } catch (error) {
        console.error("Error creating ticket:", error);
        alert(t('message.createTicketGenericFailed'));
    } finally {
        creating.value = false;
    }
};

const updateTicket = async () => {
    updating.value = true;
    try {
        const response = await fetch(
            route("admin.tickets-management.update", editingTicket.value.id),
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(editingTicket.value),
            }
        );

        const data = await response.json();

        if (data.success) {
            showEditModal.value = false;
            // Reload the page to show updated ticket
            router.reload({
                only: ["tickets"],
            });
        } else {
            alert(
                t('message.updateTicketFailed') +
                    (data.errors?.join(", ") || t('message.unknownError'))
            );
        }
    } catch (error) {
        console.error("Error updating ticket:", error);
        alert(t('message.updateTicketGenericFailed'));
    } finally {
        updating.value = false;
    }
};

const performAssignment = async () => {
    assigning.value = true;
    try {
        const response = await fetch(
            route("admin.tickets-management.assign", assigningTicket.value.id),
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(assignmentData.value),
            }
        );

        const data = await response.json();

        if (data.success) {
            showAssignModal.value = false;
            // Reload the page to show updated ticket
            router.reload({
                only: ["tickets"],
            });
        } else {
            alert(
                t('message.assignTicketFailed') +
                    (data.errors?.join(", ") || t('message.unknownError'))
            );
        }
    } catch (error) {
        console.error("Error assigning ticket:", error);
        alert(t('message.assignTicketGenericFailed'));
    } finally {
        assigning.value = false;
    }
};

const performBulkAction = async () => {
    processingBulk.value = true;
    try {
        const payload = {
            ...bulkAction.value,
            ticket_ids: selectedTickets.value,
        };

        const response = await fetch(
            route("admin.tickets-management.bulk-action"),
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(payload),
            }
        );

        const data = await response.json();

        if (response.ok && data.success) {
            showBulkActionModal.value = false;
            selectedTickets.value = [];
            selectAll.value = false;
            // Reload the page to show updated tickets
            router.reload({
                only: ["tickets"],
            });

            // Show detailed success message
            if (data.summary) {
                const successMsg =
                    t('message.bulkActionSummary', {
                        total: data.summary.total,
                        successful: data.summary.successful,
                        failed: data.summary.failed
                    });
                alert(successMsg);
            } else {
                alert(data.message || t('message.bulkActionCompleted'));
            }
        } else {
            // Handle different types of errors
            let errorMessage = t('message.bulkActionFailed');

            if (!response.ok) {
                errorMessage = t('message.serverErrorBulkAction', { status: response.status, statusText: response.statusText });
            } else if (data.errors && Array.isArray(data.errors)) {
                errorMessage =
                    "Failed to perform bulk action:\n" + data.errors.join("\n");
            } else if (data.message) {
                errorMessage = data.message;
            }

            console.error("Bulk action failed:", {
                status: response.status,
                data: data,
                errors: data.errors,
            });

            alert(errorMessage);
        }
    } catch (error) {
        console.error("Error performing bulk action:", error);
        alert(
            t('message.networkErrorBulkAction')
        );
    } finally {
        processingBulk.value = false;
    }
};

const exportTickets = () => {
    const params = new URLSearchParams(filters.value);
    window.open(
        route("admin.tickets-management.export") + "?" + params.toString(),
        "_blank"
    );
};

const resetNewTicketForm = () => {
    newTicket.value = {
        user_nip: "",
        aplikasi_id: "",
        kategori_masalah_id: "",
        title: "",
        description: "",
        priority: "",
        assigned_teknisi_nip: "",
        location: "",
    };
    availableCategories.value = [];
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        // Close all modals
        showCreateModal.value = false;
        showEditModal.value = false;
        showAssignModal.value = false;
        showBulkActionModal.value = false;
    }
};

// Watch for changes in tickets data to update select all
watch(
    () => props.tickets.data,
    (newTickets) => {
        if (selectAll.value) {
            selectedTickets.value = newTickets.map((ticket) => ticket.id);
        }
    }
);

// Lifecycle hooks
onMounted(() => {
    // Component mounted
});

// Unmount component
onUnmounted(() => {
    // Component unmounted
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative.bg-white,
.modal-leave-active .relative.bg-white {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from .relative.bg-white,
.modal-leave-to .relative.bg-white {
    transform: scale(0.96) translateY(-10px);
    opacity: 0;
}

/* Ensure backdrop blur works on all browsers */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
</style>

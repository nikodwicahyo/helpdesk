<template>
    <AppLayout role="teknisi">
        <template #header>
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center"
                    >
                        <span class="font-bold text-gray-900">{{
                            $t("dashboard.teknisi.myTasks")
                        }}</span>
                        <span
                            v-if="stats.urgent_tickets > 0"
                            class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 animate-pulse"
                        >
                            {{ stats.urgent_tickets }}
                            {{ $t("dashboard.teknisi.urgent") }}
                        </span>
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm sm:text-base">
                        {{ $t("dashboard.teknisi.manageAndResolve") }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('teknisi.knowledge-base.index')"
                        class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-m font-medium rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
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
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                        {{ $t("dashboard.teknisi.knowledgeBase") }}
                    </Link>
                    <button
                        @click="refreshBoard"
                        :disabled="isRefreshing"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-m font-medium rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg
                            :class="[
                                'w-4 h-4 mr-2',
                                isRefreshing ? 'animate-spin' : '',
                            ]"
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
                        {{
                            isRefreshing
                                ? $t("dashboard.teknisi.refreshing")
                                : $t("dashboard.teknisi.refresh")
                        }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Performance Stats -->
        <div
            class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8"
        >
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 hover:shadow-md transition-shadow duration-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide"
                        >
                            {{ $t("dashboard.teknisi.assigned") }}
                        </p>
                        <p
                            class="mt-1 text-2xl sm:text-3xl font-bold text-gray-900"
                        >
                            {{ stats.assigned_tickets }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $t("dashboard.teknisi.currentlyAssigned") }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl">
                        <svg
                            class="w-6 h-6 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 hover:shadow-md transition-shadow duration-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide"
                        >
                            {{ $t("dashboard.teknisi.inProgress") }}
                        </p>
                        <p
                            class="mt-1 text-2xl sm:text-3xl font-bold text-indigo-600"
                        >
                            {{ stats.in_progress_tickets }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $t("dashboard.teknisi.beingWorkedOn") }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-xl">
                        <svg
                            class="w-6 h-6 text-indigo-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 hover:shadow-md transition-shadow duration-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide"
                        >
                            {{ $t("dashboard.teknisi.resolvedToday") }}
                        </p>
                        <p
                            class="mt-1 text-2xl sm:text-3xl font-bold text-green-600"
                        >
                            {{ stats.resolved_today }}
                        </p>
                        <p
                            v-if="stats.resolved_today_trend > 0"
                            class="mt-1 text-xs text-green-500 flex items-center"
                        >
                            <svg
                                class="w-3 h-3 mr-1"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            +{{ stats.resolved_today_trend }}%
                        </p>
                        <p v-else class="mt-1 text-xs text-gray-400">
                            {{ $t("dashboard.teknisi.greatWork") }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-xl">
                        <svg
                            class="w-6 h-6 text-green-600"
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
            </div>
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 hover:shadow-md transition-shadow duration-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide"
                        >
                            {{ $t("dashboard.teknisi.avgResolution") }}
                        </p>
                        <p
                            class="mt-1 text-2xl sm:text-3xl font-bold text-amber-600"
                        >
                            {{ stats.avg_resolution_time }}h
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $t("dashboard.teknisi.thisWeek") }}
                        </p>
                    </div>
                    <div class="p-3 bg-amber-50 rounded-xl">
                        <svg
                            class="w-6 h-6 text-amber-600"
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and View Options -->
        <div
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 mb-6"
        >
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <!-- Search and Filters -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1"
                >
                    <div class="relative flex-1 max-w-md">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            @input="filterTickets"
                            type="text"
                            placeholder="Search by ticket number, title, or user..."
                            class="block w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        >
                            <svg
                                class="h-4 w-4"
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
                    <div class="flex items-center gap-2">
                        <select
                            v-model="priorityFilter"
                            @change="filterTickets"
                            class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors cursor-pointer"
                        >
                            <option value="">All Priority</option>
                            <option value="urgent">🔴 Urgent Only</option>
                            <option value="high">🟠 High & Above</option>
                            <option value="overdue">⏰ Overdue</option>
                        </select>
                        <select
                            v-model="statusFilter"
                            class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors cursor-pointer"
                        >
                            <option value="">All Status</option>
                            <option value="assigned">📋 Assigned</option>
                            <option value="in_progress">⚡ In Progress</option>
                            <option value="waiting_response">
                                ⏳ Waiting Response
                            </option>
                            <option value="resolved">✅ Resolved</option>
                        </select>
                    </div>
                </div>

                <!-- View Toggle and Count -->
                <div
                    class="flex items-center justify-between sm:justify-end gap-4"
                >
                    <div class="flex items-center text-sm text-gray-500">
                        <span class="font-medium text-gray-900">{{
                            filteredTickets.length
                        }}</span>
                        <span class="ml-1"
                            >ticket{{
                                filteredTickets.length !== 1 ? "s" : ""
                            }}</span
                        >
                    </div>
                    <div class="flex items-center bg-gray-100 rounded-xl p-1">
                        <button
                            @click="viewMode = 'kanban'"
                            :class="[
                                'flex items-center px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200',
                                viewMode === 'kanban'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 mr-1.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                                />
                            </svg>
                            {{ $t('common.gridView') }}
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="[
                                'flex items-center px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200',
                                viewMode === 'list'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 mr-1.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                />
                            </svg>
                            {{ $t('common.listView') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Board View -->
        <div
            v-if="viewMode === 'kanban'"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6"
        >
            <!-- Assigned Column -->
            <div
                class="bg-gradient-to-b from-amber-50 to-white rounded-xl border border-amber-100 shadow-sm overflow-hidden"
            >
                <div class="p-4 bg-amber-50/80 border-b border-amber-100">
                    <div class="flex items-center justify-between">
                        <h3
                            class="font-semibold text-gray-900 flex items-center text-sm"
                        >
                            <span
                                class="w-2.5 h-2.5 bg-amber-500 rounded-full mr-2 shadow-sm"
                            ></span>
                            {{ $t("status.assigned") }}
                        </h3>
                        <span
                            class="bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                        >
                            {{ getColumnTickets("assigned").length }}
                        </span>
                    </div>
                </div>
                <div
                    :class="[
                        'p-3 min-h-[350px] space-y-3 transition-colors duration-200',
                        isDragging && dragTarget === 'assigned'
                            ? 'bg-amber-100/50'
                            : '',
                    ]"
                    @dragover.prevent="dragTarget = 'assigned'"
                    @dragleave="dragTarget = null"
                    @drop="handleDrop($event, 'assigned')"
                >
                    <TransitionGroup
                        name="ticket-list"
                        tag="div"
                        class="space-y-3"
                    >
                        <KanbanTicket
                            v-for="ticket in getColumnTickets('assigned')"
                            :key="ticket.id"
                            :ticket="ticket"
                            :draggable="true"
                            @dragstart="handleDragStart($event, ticket)"
                            @dragend="handleDragEnd"
                            @click="viewTicket(ticket.id)"
                            @status-change="updateTicketStatus"
                            @resolve="openResolveModal"
                            @reassign="openReassignModal"
                        />
                    </TransitionGroup>
                    <div
                        v-if="getColumnTickets('assigned').length === 0"
                        class="flex flex-col items-center justify-center text-gray-400 py-12"
                    >
                        <div class="p-3 bg-gray-100 rounded-full mb-3">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                />
                            </svg>
                        </div>
                        <p class="text-sm font-medium">
                            {{ $t("common.noData") }}
                        </p>
                        <p class="text-xs mt-1">{{ $t("common.noResults") }}</p>
                    </div>
                </div>
            </div>

            <!-- In Progress Column -->
            <div
                class="bg-gradient-to-b from-blue-50 to-white rounded-xl border border-blue-100 shadow-sm overflow-hidden"
            >
                <div class="p-4 bg-blue-50/80 border-b border-blue-100">
                    <div class="flex items-center justify-between">
                        <h3
                            class="font-semibold text-gray-900 flex items-center text-sm"
                        >
                            <span
                                class="w-2.5 h-2.5 bg-blue-500 rounded-full mr-2 shadow-sm animate-pulse"
                            ></span>
                            {{ $t("status.inProgress") }}
                        </h3>
                        <span
                            class="bg-blue-100 text-blue-700 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                        >
                            {{ getColumnTickets("in_progress").length }}
                        </span>
                    </div>
                </div>
                <div
                    :class="[
                        'p-3 min-h-[350px] space-y-3 transition-colors duration-200',
                        isDragging && dragTarget === 'in_progress'
                            ? 'bg-blue-100/50'
                            : '',
                    ]"
                    @dragover.prevent="dragTarget = 'in_progress'"
                    @dragleave="dragTarget = null"
                    @drop="handleDrop($event, 'in_progress')"
                >
                    <TransitionGroup
                        name="ticket-list"
                        tag="div"
                        class="space-y-3"
                    >
                        <KanbanTicket
                            v-for="ticket in getColumnTickets('in_progress')"
                            :key="ticket.id"
                            :ticket="ticket"
                            :draggable="true"
                            @dragstart="handleDragStart($event, ticket)"
                            @dragend="handleDragEnd"
                            @click="viewTicket(ticket.id)"
                            @status-change="updateTicketStatus"
                            @resolve="openResolveModal"
                            @reassign="openReassignModal"
                        />
                    </TransitionGroup>
                    <div
                        v-if="getColumnTickets('in_progress').length === 0"
                        class="flex flex-col items-center justify-center text-gray-400 py-12"
                    >
                        <div class="p-3 bg-gray-100 rounded-full mb-3">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                        </div>
                        <p class="text-sm font-medium">
                            {{ $t("common.noData") }}
                        </p>
                        <p class="text-xs mt-1">{{ $t("common.noResults") }}</p>
                    </div>
                </div>
            </div>

            <!-- Waiting Response Column -->
            <div
                class="bg-gradient-to-b from-orange-50 to-white rounded-xl border border-orange-100 shadow-sm overflow-hidden"
            >
                <div class="p-4 bg-orange-50/80 border-b border-orange-100">
                    <div class="flex items-center justify-between">
                        <h3
                            class="font-semibold text-gray-900 flex items-center text-sm"
                        >
                            <span
                                class="w-2.5 h-2.5 bg-orange-500 rounded-full mr-2 shadow-sm"
                            ></span>
                            {{ $t("status.waitingUser") }}
                        </h3>
                        <span
                            class="bg-orange-100 text-orange-700 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                        >
                            {{ getColumnTickets("waiting_response").length }}
                        </span>
                    </div>
                </div>
                <div
                    :class="[
                        'p-3 min-h-[350px] space-y-3 transition-colors duration-200',
                        isDragging && dragTarget === 'waiting_response'
                            ? 'bg-orange-100/50'
                            : '',
                    ]"
                    @dragover.prevent="dragTarget = 'waiting_response'"
                    @dragleave="dragTarget = null"
                    @drop="handleDrop($event, 'waiting_response')"
                >
                    <TransitionGroup
                        name="ticket-list"
                        tag="div"
                        class="space-y-3"
                    >
                        <KanbanTicket
                            v-for="ticket in getColumnTickets(
                                'waiting_response'
                            )"
                            :key="ticket.id"
                            :ticket="ticket"
                            :draggable="true"
                            @dragstart="handleDragStart($event, ticket)"
                            @dragend="handleDragEnd"
                            @click="viewTicket(ticket.id)"
                            @status-change="updateTicketStatus"
                            @resolve="openResolveModal"
                            @reassign="openReassignModal"
                        />
                    </TransitionGroup>
                    <div
                        v-if="getColumnTickets('waiting_response').length === 0"
                        class="flex flex-col items-center justify-center text-gray-400 py-12"
                    >
                        <div class="p-3 bg-gray-100 rounded-full mb-3">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <p class="text-sm font-medium">
                            {{ $t("common.noData") }}
                        </p>
                        <p class="text-xs mt-1">
                            {{ $t("status.waitingUser") }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resolved Column -->
            <div
                class="bg-gradient-to-b from-green-50 to-white rounded-xl border border-green-100 shadow-sm overflow-hidden"
            >
                <div class="p-4 bg-green-50/80 border-b border-green-100">
                    <div class="flex items-center justify-between">
                        <h3
                            class="font-semibold text-gray-900 flex items-center text-sm"
                        >
                            <span
                                class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2 shadow-sm"
                            ></span>
                            {{ $t("status.resolved") }}
                        </h3>
                        <span
                            class="bg-green-100 text-green-700 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                        >
                            {{ getColumnTickets("resolved").length }}
                        </span>
                    </div>
                </div>
                <div
                    :class="[
                        'p-3 min-h-[350px] space-y-3 transition-colors duration-200',
                        isDragging && dragTarget === 'resolved'
                            ? 'bg-green-100/50'
                            : '',
                    ]"
                    @dragover.prevent="dragTarget = 'resolved'"
                    @dragleave="dragTarget = null"
                    @drop="handleDrop($event, 'resolved')"
                >
                    <TransitionGroup
                        name="ticket-list"
                        tag="div"
                        class="space-y-3"
                    >
                        <KanbanTicket
                            v-for="ticket in getColumnTickets('resolved')"
                            :key="ticket.id"
                            :ticket="ticket"
                            :draggable="true"
                            @dragstart="handleDragStart($event, ticket)"
                            @click="viewTicket(ticket.id)"
                            @status-change="updateTicketStatus"
                            @resolve="openResolveModal"
                            @reassign="openReassignModal"
                        />
                    </TransitionGroup>
                    <div
                        v-if="getColumnTickets('resolved').length === 0"
                        class="flex flex-col items-center justify-center text-gray-400 py-12"
                    >
                        <div class="p-3 bg-gray-100 rounded-full mb-3">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <p class="text-sm font-medium">
                            {{ $t("common.noData") }}
                        </p>
                        <p class="text-xs mt-1">
                            {{ $t("status.resolved") }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-else class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("ticket.ticket") }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("ticket.ticketTitle") }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("user.name") }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("ticket.status") }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("ticket.priority") }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("ticket.age") }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ $t("common.actions") }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="ticket in filteredTickets"
                            :key="ticket.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <span
                                        class="text-sm font-mono text-gray-900"
                                        >{{ ticket.ticket_number }}</span
                                    >
                                    <div
                                        v-if="ticket.is_overdue"
                                        class="w-2 h-2 bg-red-500 rounded-full"
                                        title="Overdue"
                                    ></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ ticket.title }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 truncate mt-1"
                                    >
                                        {{ ticket.description }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900">
                                        {{ ticket.user?.nama_lengkap }}
                                    </p>
                                    <p class="text-gray-500">
                                        {{ ticket.user?.email }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <select
                                    :value="getDisplayStatus(ticket.status)"
                                    @change="
                                        handleStatusChange(
                                            ticket,
                                            $event.target.value
                                        )
                                    "
                                    class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option
                                        value="assigned"
                                        v-if="
                                            ticket.status === 'open' ||
                                            ticket.status === 'assigned'
                                        "
                                        disabled
                                    >
                                        Assigned (Start Working →)
                                    </option>
                                    <option value="in_progress">
                                        In Progress
                                    </option>
                                    <option value="waiting_user">
                                        Waiting Response
                                    </option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getPriorityColor(ticket.priority),
                                    ]"
                                >
                                    {{ ticket.priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ ticket.time_elapsed || "-" }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="viewTicket(ticket.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                        title="View Details"
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
                                        v-if="
                                            [
                                                'in_progress',
                                                'waiting_user',
                                                'waiting_response',
                                            ].includes(ticket.status)
                                        "
                                        @click="openResolveModal(ticket)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Resolve Ticket"
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
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="
                                            [
                                                'open',
                                                'assigned',
                                                'in_progress',
                                                'waiting_user',
                                                'waiting_response',
                                            ].includes(ticket.status)
                                        "
                                        @click="openReassignModal(ticket)"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Request Reassignment"
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
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="ticket.status === 'resolved'"
                                        @click="markAsClosed(ticket.id)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Mark as Closed"
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
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resolve Ticket Modal -->
        <ResolveTicketModal
            :show="showResolveModal"
            :ticket="selectedTicket"
            @close="closeResolveModal"
            @resolved="handleTicketResolved"
        />

        <!-- Reassignment Request Modal -->
        <ReassignmentRequestModal
            :show="showReassignModal"
            :ticket="selectedTicket"
            @close="closeReassignModal"
            @submitted="handleReassignmentSubmitted"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { debounce } from "lodash";
import axios from "axios";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import KanbanTicket from "@/Components/Tickets/KanbanTicket.vue";
import ResolveTicketModal from "@/Components/Modals/ResolveTicketModal.vue";
import ReassignmentRequestModal from "@/Components/Modals/ReassignmentRequestModal.vue";

const props = defineProps({
    tickets: {
        type: [Array, Object],
        required: true,
    },
    quickStats: {
        type: Object,
        default: () => ({}),
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

const viewMode = ref("kanban");
const searchQuery = ref("");
const priorityFilter = ref("");
const statusFilter = ref("");
const isRefreshing = ref(false);
const isDragging = ref(false);
const dragTarget = ref(null);
const draggedTicket = ref(null);
const showResolveModal = ref(false);
const showReassignModal = ref(false);
const selectedTicket = ref(null);

// Get tickets array from prop (handle both array and pagination object)
const ticketsArray = computed(() => {
    if (Array.isArray(props.tickets)) {
        return props.tickets;
    } else if (props.tickets && props.tickets.data) {
        return props.tickets.data;
    }
    return [];
});

// Stats computed property with default values
const stats = computed(() => {
    const defaultStats = {
        assigned_tickets: 0,
        in_progress_tickets: 0,
        resolved_today: 0,
        resolved_today_trend: 0,
        avg_resolution_time: 0,
        open_tickets: 0,
        waiting_response_tickets: 0,
        overdue_tickets: 0,
        urgent_tickets: 0,
    };

    return { ...defaultStats, ...props.quickStats };
});

const filteredTickets = computed(() => {
    let filtered = ticketsArray.value;

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (ticket) =>
                ticket.title.toLowerCase().includes(query) ||
                ticket.description.toLowerCase().includes(query) ||
                ticket.ticket_number.toLowerCase().includes(query) ||
                ticket.user?.nama_lengkap?.toLowerCase().includes(query)
        );
    }

    // Apply priority filter
    if (priorityFilter.value) {
        switch (priorityFilter.value) {
            case "urgent":
                filtered = filtered.filter(
                    (ticket) => ticket.priority === "urgent"
                );
                break;
            case "high":
                filtered = filtered.filter((ticket) =>
                    ["urgent", "high"].includes(ticket.priority)
                );
                break;
            case "overdue":
                filtered = filtered.filter((ticket) => ticket.is_overdue);
                break;
        }
    }

    // Apply status filter
    if (statusFilter.value) {
        switch (statusFilter.value) {
            case "assigned":
                filtered = filtered.filter(
                    (ticket) =>
                        ticket.status === "open" || ticket.status === "assigned"
                );
                break;
            case "in_progress":
                filtered = filtered.filter(
                    (ticket) => ticket.status === "in_progress"
                );
                break;
            case "waiting_response":
                filtered = filtered.filter(
                    (ticket) =>
                        ticket.status === "waiting_response" ||
                        ticket.status === "pending"
                );
                break;
            case "resolved":
                filtered = filtered.filter(
                    (ticket) => ticket.status === "resolved"
                );
                break;
        }
    }

    return filtered;
});

const getColumnTickets = (status) => {
    switch (status) {
        case "assigned":
            // Include both 'open' and 'assigned' tickets in the Assigned column
            return filteredTickets.value.filter(
                (ticket) =>
                    ticket.status === "open" || ticket.status === "assigned"
            );
        case "waiting_response":
            // Include both 'waiting_user' and 'waiting_response' for compatibility
            return filteredTickets.value.filter(
                (ticket) =>
                    ticket.status === "waiting_user" ||
                    ticket.status === "waiting_response"
            );
        default:
            return filteredTickets.value.filter(
                (ticket) => ticket.status === status
            );
    }
};

const filterTickets = debounce(() => {
    // Filter logic is handled by computed property
}, 300);

const refreshBoard = () => {
    isRefreshing.value = true;
    router.reload({
        only: ["tickets", "quickStats"],
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const handleDragStart = (event, ticket) => {
    draggedTicket.value = ticket;
    isDragging.value = true;
    event.dataTransfer.effectAllowed = "move";
    event.dataTransfer.setData("text/html", event.target.innerHTML);
};

const handleDragEnd = () => {
    isDragging.value = false;
    dragTarget.value = null;
};

// Map frontend column status to backend status values
const mapStatusToBackend = (frontendStatus) => {
    const statusMap = {
        assigned: "in_progress", // When dropped to "Assigned", start working on it
        in_progress: "in_progress",
        waiting_response: "waiting_user",
        waiting_user: "waiting_user",
        resolved: "resolved",
    };
    return statusMap[frontendStatus] || frontendStatus;
};

// Get display status for dropdown
const getDisplayStatus = (status) => {
    if (status === "open" || status === "assigned") {
        return "assigned";
    }
    if (status === "waiting_user" || status === "waiting_response") {
        return "waiting_user";
    }
    return status;
};

// Normalize status for comparison
const normalizeStatus = (status) => {
    if (status === "open" || status === "assigned") {
        return "assigned";
    }
    if (status === "waiting_user" || status === "waiting_response") {
        return "waiting_user";
    }
    return status;
};

// Handle status change from dropdown - validates transitions
const handleStatusChange = (ticket, newStatus) => {
    const currentStatus = ticket.status;

    // Map to backend status value
    const backendStatus = mapStatusToBackend(newStatus);

    // Prevent invalid selections
    if (newStatus === "assigned") {
        alert(
            "Cannot change status back to Assigned. Use the ticket detail page to manage this ticket."
        );
        return;
    }

    // Update the status
    updateTicketStatus(ticket.id, backendStatus);
};

const handleDrop = (event, newStatus) => {
    event.preventDefault();
    isDragging.value = false;
    dragTarget.value = null;

    if (draggedTicket.value) {
        const currentStatus = normalizeStatus(draggedTicket.value.status);
        const targetStatus = normalizeStatus(newStatus);

        // Prevent dropping back to "assigned" column - not allowed
        if (newStatus === "assigned" && currentStatus !== "assigned") {
            alert(
                "Cannot move ticket back to Assigned. Tickets can only move forward in the workflow."
            );
            draggedTicket.value = null;
            return;
        }

        // Only update if the status is actually different
        if (currentStatus !== targetStatus) {
            const backendStatus = mapStatusToBackend(newStatus);
            updateTicketStatus(draggedTicket.value.id, backendStatus);
        }
    }
    draggedTicket.value = null;
};

const updateTicketStatus = async (ticketId, newStatus) => {
    // Log for debugging
    console.log("Updating ticket status:", { ticketId, newStatus });

    try {
        const response = await axios.post(
            route("teknisi.tickets.update-status", ticketId),
            { status: newStatus }
        );

        console.log("Status update response:", response.data);

        if (response.data.success) {
            // Refresh the page data
            refreshBoard();
        } else {
            const errorMsg =
                response.data.errors?.join(", ") || "Failed to update status";
            console.error("Status update failed:", response.data);
            alert(errorMsg);
        }
    } catch (error) {
        console.error("Failed to update ticket status:", error);
        console.error("Error response:", error.response?.data);

        let errorMsg = "Failed to update ticket status";
        if (error.response?.data?.errors) {
            errorMsg = error.response.data.errors.join(", ");
        } else if (error.response?.data?.message) {
            errorMsg = error.response.data.message;
        } else if (error.response?.data?.debug) {
            errorMsg = `Status '${
                error.response.data.debug.requested_status
            }' not allowed. Allowed: ${error.response.data.debug.allowed_statuses.join(
                ", "
            )}`;
        }

        alert(errorMsg);
    }
};

// Modal states
const showNotesEditor = ref(false);

const viewTicket = (ticketId) => {
    router.visit(route("teknisi.tickets.show", ticketId));
};

// Open resolve modal
const openResolveModal = (ticket) => {
    selectedTicket.value = ticket;
    showResolveModal.value = true;
};

const closeResolveModal = () => {
    showResolveModal.value = false;
    selectedTicket.value = null;
};

// Open reassign modal
const openReassignModal = (ticket) => {
    selectedTicket.value = ticket;
    showReassignModal.value = true;
};

const closeReassignModal = () => {
    showReassignModal.value = false;
    selectedTicket.value = null;
};

// Modal handlers
const handleTicketUpdated = (updatedTicket) => {
    refreshBoard();
};

const handleTicketResolved = (resolvedTicket) => {
    showResolveModal.value = false;
    selectedTicket.value = null;
    refreshBoard();
};

const handleReassignmentSubmitted = (response) => {
    showReassignModal.value = false;
    selectedTicket.value = null;
    refreshBoard();
};

const handleNoteSubmitted = (response) => {
    console.log("Note submitted:", response);
    refreshBoard();
};

const markAsClosed = async (ticketId) => {
    // Note: Teknisi cannot directly close tickets - they can only resolve
    // Closing is done by user or admin after resolution
    alert(
        "Tickets can only be closed by the user who created them after reviewing the resolution."
    );
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

// Auto-refresh every 30 seconds
onMounted(() => {
    setInterval(() => {
        refreshBoard();
    }, 30000);
});
</script>

<style scoped>
.ticket-list-enter-active,
.ticket-list-leave-active {
    transition: all 0.3s ease;
}

.ticket-list-enter-from,
.ticket-list-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.ticket-list-move {
    transition: transform 0.3s ease;
}
</style>

<template>
    <AppLayout role="teknisi">
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 animate-slideInDown">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $t('dashboard.teknisiDashboard') }}</h1>
                            <p class="text-gray-600 text-sm sm:text-base animate-fadeInUp animation-delay-200">
                                {{ $t('dashboard.welcomeBack') }}, {{ $page.props.auth.user?.name }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                            <span>{{ t('dashboard.teknisi.ticketsInProgress', { count: stats.in_progress_tickets || 0 }) }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ new Date().toLocaleString(t('time.locale'), { weekday: 'long', hour: '2-digit', minute: '2-digit' }) }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ t('dashboard.teknisi.resolutionRatePercentage', { rate: performance?.resolution_rate || 0 }) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/teknisi/knowledge-base"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span class="hidden sm:inline">{{ $t('dashboard.teknisi.knowledgeBase') }}</span>
                        <span class="sm:hidden">📚</span>
                    </Link>
                    <Link
                        href="/teknisi/tickets"
                        class="bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="hidden sm:inline">{{ $t('dashboard.teknisi.myTasks') }}</span>
                        <span class="sm:hidden">📋</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Performance Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-5 mb-6">
            <!-- Assigned Tickets Card -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        Active
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $t('dashboard.teknisi.assignedTickets') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            <AnimatedNumber
                                :target="stats.myAssignedTickets || stats.assigned_tickets || 0"
                                :duration="2000"
                                :delay="200"
                            />
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $t('dashboard.teknisi.currentlyAssigned') }}</p>
                </div>
            </div>

            <!-- In Progress Card -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $t('dashboard.teknisi.inProgress') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            <AnimatedNumber
                                :target="stats.in_progress_tickets || 0"
                                :duration="2000"
                                :delay="400"
                            />
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $t('dashboard.teknisi.beingWorkedOn') }}</p>
                </div>
            </div>

            <!-- Resolved Today Card -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div v-if="stats.resolved_today_trend !== 0" class="flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="stats.resolved_today_trend > 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stats.resolved_today_trend > 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'"/>
                        </svg>
                        {{ Math.abs(stats.resolved_today_trend).toFixed(0) }}%
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $t('dashboard.teknisi.resolvedToday') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            <AnimatedNumber
                                :target="stats.resolved_today || 0"
                                :duration="2000"
                                :delay="600"
                            />
                        </p>
                    </div>
                    <p class="mt-1 text-xs" :class="stats.resolved_today_trend > 0 ? 'text-green-600' : stats.resolved_today_trend < 0 ? 'text-red-600' : 'text-gray-500'">
                        {{ stats.resolved_today_trend > 0 ? '+' : '' }}{{ stats.resolved_today_trend || 0 }}% {{ $t('dashboard.teknisi.vsYesterday') }}
                    </p>
                </div>
            </div>

            <!-- Avg Resolution Time Card -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-cyan-100 to-teal-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                        Avg
                    </span>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $t('dashboard.teknisi.avgResolutionTime') || 'Avg Resolution' }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            <AnimatedNumber
                                :target="stats.avg_resolution_time || performance.avg_resolution_time || 0"
                                :duration="2000"
                                :delay="700"
                                :decimals="1"
                            />
                        </p>
                        <span class="ml-1 text-sm font-medium text-gray-500">{{ $t('dashboard.teknisi.hours') || 'h' }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $t('dashboard.teknisi.perTicket') || 'per ticket' }}</p>
                </div>
            </div>

            <!-- Average Rating Card -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-amber-100 to-orange-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div class="flex items-center space-x-0.5">
                        <svg v-for="star in 5" :key="star" :class="['w-3.5 h-3.5', star <= Math.round(stats.avg_rating) ? 'text-amber-400' : 'text-gray-300']" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $t('dashboard.teknisi.averageRating') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            <AnimatedNumber
                                :target="parseFloat(stats.avg_rating) || 0"
                                :duration="2000"
                                :delay="800"
                                :decimals="1"
                            />
                        </p>
                        <span class="ml-1 text-sm font-medium text-gray-500">/5</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $t('dashboard.teknisi.userSatisfaction') }}</p>
                </div>
            </div>
        </div>

        <!-- Urgent Tickets Alert -->
        <div v-if="urgentTickets.length > 0" class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-red-800">{{ $t('dashboard.teknisi.urgentAttention') || 'Requires Urgent Attention' }}</h3>
                    <div class="mt-2 space-y-2">
                        <div
                            v-for="ticket in urgentTickets"
                            :key="ticket.id"
                            @click="viewTicket(ticket.id)"
                            class="flex items-center justify-between p-2 bg-white rounded-lg shadow-sm cursor-pointer hover:shadow-md transition"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="text-xs font-mono text-gray-500">{{ ticket.ticket_number }}</span>
                                <span class="text-sm font-medium text-gray-900 truncate max-w-xs">{{ ticket.title }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span v-if="isOverdue(ticket)" class="px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">
                                    {{ $t('dashboard.teknisi.overdue') || 'Overdue' }}
                                </span>
                                <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getPriorityColor(ticket.priority)]">
                                    {{ ticket.priority_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <!-- Left Column - My Tasks & Ticket Queue -->
            <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                <!-- My Tasks Kanban Board -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ $t('dashboard.teknisi.myTasks') }}
                            </h2>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="refreshTasks"
                                    :disabled="isRefreshing"
                                    class="text-indigo-600 hover:text-indigo-800 p-2 rounded-lg hover:bg-indigo-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    :title="$t('dashboard.teknisi.refresh')"
                                >
                                    <svg
                                        :class="['w-5 h-5 transition-transform', isRefreshing ? 'animate-spin' : '']"
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
                                </button>
                                <select
                                    v-model="taskFilter"
                                    class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer"
                                    :disabled="isRefreshing"
                                >
                                    <option value="all">{{ $t('dashboard.teknisi.allTasks') }}</option>
                                    <option value="urgent">{{ $t('dashboard.teknisi.urgentOnly') }}</option>
                                    <option value="overdue">{{ $t('dashboard.teknisi.overdue') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Kanban Columns -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Assigned Column -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div
                                    class="flex items-center justify-between mb-4"
                                >
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $t('dashboard.teknisi.assigned') }}
                                    </h3>
                                    <span
                                        class="bg-gray-200 text-gray-700 px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ assignedTickets.length }}
                                    </span>
                                </div>
                                <draggable
                                    v-model="kanbanColumns.assigned"
                                    item-key="id"
                                    data-status="assigned"
                                    group="tickets"
                                    @end="onDragEnd"
                                    class="space-y-3 min-h-[200px]"
                                    :animation="200"
                                    ghost-class="opacity-50"
                                >
                                    <template #item="{element: ticket}">
                                        <div
                                            :data-ticket-id="ticket.id"
                                            class="bg-white p-3 rounded-lg shadow-sm border cursor-move hover:shadow-md transition"
                                        >
                                            <div
                                                class="flex items-center justify-between mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                <span
                                                    class="text-xs font-mono text-gray-500"
                                                    >{{
                                                        ticket.ticket_number
                                                    }}</span
                                                >
                                                <div class="flex items-center space-x-1">
                                                    <span v-if="isOverdue(ticket)" class="px-1.5 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">
                                                        SLA
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'px-2 py-1 text-xs font-medium rounded-full',
                                                            getPriorityColor(
                                                                ticket.priority
                                                            ),
                                                        ]"
                                                    >
                                                        {{ ticket.priority_label }}
                                                    </span>
                                                </div>
                                            </div>
                                            <h4
                                                class="font-medium text-gray-900 text-sm mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                {{ ticket.title }}
                                            </h4>
                                            <div
                                                class="flex items-center justify-between text-xs text-gray-500"
                                            >
                                                <span>{{
                                                    ticket.user?.nama_lengkap || 'Unknown'
                                                }}</span>
                                                <span>{{
                                                    ticket.formatted_created_at
                                                }}</span>
                                            </div>
                                            <button
                                                @click.stop="
                                                    startWorking(ticket.id)
                                                "
                                                class="w-full mt-2 bg-indigo-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-indigo-700 transition"
                                            >
                                                {{ $t('dashboard.teknisi.startWorking') }}
                                            </button>
                                        </div>
                                    </template>
                                </draggable>
                            </div>

                            <!-- In Progress Column -->
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div
                                    class="flex items-center justify-between mb-4"
                                >
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $t('dashboard.teknisi.inProgress') }}
                                    </h3>
                                    <span
                                        class="bg-blue-200 text-blue-700 px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ inProgressTickets.length }}
                                    </span>
                                </div>
                                <draggable
                                    v-model="kanbanColumns.in_progress"
                                    item-key="id"
                                    data-status="in_progress"
                                    group="tickets"
                                    @end="onDragEnd"
                                    class="space-y-3 min-h-[200px]"
                                    :animation="200"
                                    ghost-class="opacity-50"
                                >
                                    <template #item="{element: ticket}">
                                        <div
                                            :data-ticket-id="ticket.id"
                                            class="bg-white p-3 rounded-lg shadow-sm border cursor-move hover:shadow-md transition"
                                        >
                                            <div
                                                class="flex items-center justify-between mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                <span
                                                    class="text-xs font-mono text-gray-500"
                                                    >{{
                                                        ticket.ticket_number
                                                    }}</span
                                                >
                                                <div class="flex items-center space-x-1">
                                                    <span v-if="isOverdue(ticket)" class="px-1.5 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">
                                                        SLA
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'px-2 py-1 text-xs font-medium rounded-full',
                                                            getPriorityColor(
                                                                ticket.priority
                                                            ),
                                                        ]"
                                                    >
                                                        {{ ticket.priority_label }}
                                                    </span>
                                                </div>
                                            </div>
                                            <h4
                                                class="font-medium text-gray-900 text-sm mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                {{ ticket.title }}
                                            </h4>
                                            <div
                                                class="flex items-center justify-between text-xs text-gray-500 mb-2"
                                            >
                                                <span>{{
                                                    ticket.user?.nama_lengkap || 'Unknown'
                                                }}</span>
                                                <span>{{
                                                    ticket.time_elapsed
                                                }}</span>
                                            </div>
                                            <div class="flex space-x-1">
                                                <button
                                                    @click.stop="
                                                        pauseTicket(ticket.id)
                                                    "
                                                    class="flex-1 bg-yellow-600 text-white px-2 py-1 rounded text-xs font-medium hover:bg-yellow-700 transition"
                                                >
                                                    {{ $t('dashboard.teknisi.pause') }}
                                                </button>
                                                <button
                                                    @click.stop="
                                                        resolveTicket(ticket.id)
                                                    "
                                                    class="flex-1 bg-green-600 text-white px-2 py-1 rounded text-xs font-medium hover:bg-green-700 transition"
                                                >
                                                    {{ $t('dashboard.teknisi.resolve') }}
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>

                            <!-- Pending Column -->
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <div
                                    class="flex items-center justify-between mb-4"
                                >
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $t('dashboard.teknisi.pending') }}
                                    </h3>
                                    <span
                                        class="bg-yellow-200 text-yellow-700 px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ pendingTickets.length }}
                                    </span>
                                </div>
                                <draggable
                                    v-model="kanbanColumns.waiting_response"
                                    item-key="id"
                                    data-status="waiting_user"
                                    group="tickets"
                                    @end="onDragEnd"
                                    class="space-y-3 min-h-[200px]"
                                    :animation="200"
                                    ghost-class="opacity-50"
                                >
                                    <template #item="{element: ticket}">
                                        <div
                                            :data-ticket-id="ticket.id"
                                            class="bg-white p-3 rounded-lg shadow-sm border cursor-move hover:shadow-md transition"
                                        >
                                            <div
                                                class="flex items-center justify-between mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                <span
                                                    class="text-xs font-mono text-gray-500"
                                                    >{{
                                                        ticket.ticket_number
                                                    }}</span
                                                >
                                                <div class="flex items-center space-x-1">
                                                    <span v-if="isOverdue(ticket)" class="px-1.5 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">
                                                        SLA
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'px-2 py-1 text-xs font-medium rounded-full',
                                                            getPriorityColor(
                                                                ticket.priority
                                                            ),
                                                        ]"
                                                    >
                                                        {{ ticket.priority_label }}
                                                    </span>
                                                </div>
                                            </div>
                                            <h4
                                                class="font-medium text-gray-900 text-sm mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                {{ ticket.title }}
                                            </h4>
                                            <div
                                                class="flex items-center justify-between text-xs text-gray-500 mb-2"
                                            >
                                                <span>{{
                                                    ticket.user?.nama_lengkap || 'Unknown'
                                                }}</span>
                                                <span>{{ $t('dashboard.teknisi.waitingResponse') }}</span>
                                            </div>
                                            <button
                                                @click.stop="
                                                    resumeTicket(ticket.id)
                                                "
                                                class="w-full bg-indigo-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-indigo-700 transition"
                                            >
                                                {{ $t('dashboard.teknisi.resume') }}
                                            </button>
                                        </div>
                                    </template>
                                </draggable>
                            </div>

                            <!-- Resolved Column -->
                            <!-- Resolved Column -->
                            <div class="bg-green-50 rounded-lg p-4">
                                <div
                                    class="flex items-center justify-between mb-4"
                                >
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $t('dashboard.teknisi.resolved') }}
                                    </h3>
                                    <span
                                        class="bg-green-200 text-green-700 px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ resolvedTickets.length }}
                                    </span>
                                </div>
                                <draggable
                                    v-model="kanbanColumns.resolved"
                                    item-key="id"
                                    data-status="resolved"
                                    group="tickets"
                                    @end="onDragEnd"
                                    class="space-y-3 min-h-[200px]"
                                    :animation="200"
                                    ghost-class="opacity-50"
                                >
                                    <template #item="{element: ticket}">
                                        <div
                                            :data-ticket-id="ticket.id"
                                            class="bg-white p-3 rounded-lg shadow-sm border cursor-move hover:shadow-md transition"
                                        >
                                            <div
                                                class="flex items-center justify-between mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                <span
                                                    class="text-xs font-mono text-gray-500"
                                                    >{{
                                                        ticket.ticket_number
                                                    }}</span
                                                >
                                                <div
                                                    class="flex items-center space-x-1"
                                                >
                                                    <svg
                                                        class="w-4 h-4 text-green-600"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </div>
                                            </div>
                                            <h4
                                                class="font-medium text-gray-900 text-sm mb-2"
                                                @click="viewTicket(ticket.id)"
                                            >
                                                {{ ticket.title }}
                                            </h4>
                                            <div
                                                class="flex items-center justify-between text-xs text-gray-500"
                                            >
                                                <span>{{
                                                    ticket.user?.nama_lengkap || 'Unknown'
                                                }}</span>
                                                <span>{{
                                                    ticket.formatted_resolved_at
                                                }}</span>
                                            </div>
                                            <div
                                                v-if="ticket.rating"
                                                class="flex items-center mt-2"
                                            >
                                                <div class="flex text-yellow-400">
                                                    <svg
                                                        v-for="star in 5"
                                                        :key="star"
                                                        :class="[
                                                            'w-3 h-3',
                                                            star <= ticket.rating
                                                                ? 'text-yellow-400'
                                                                : 'text-gray-300',
                                                        ]"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                        />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-xs text-gray-500 ml-1"
                                                    >{{ ticket.rating }}/5</span
                                                >
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        {{ $t('dashboard.teknisi.quickActions') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link
                            href="/teknisi/tickets"
                            class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition group"
                        >
                            <div
                                class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition"
                            >
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
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">
                                    {{ $t('dashboard.teknisi.myTasks') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $t('dashboard.teknisi.viewCompleteList') }}
                                </p>
                            </div>
                        </Link>

                        <Link
                            href="/teknisi/knowledge-base"
                            class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition group"
                        >
                            <div
                                class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition"
                            >
                                <svg
                                    class="w-6 h-6 text-purple-600"
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
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">
                                    {{ $t('dashboard.teknisi.knowledgeBase') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $t('dashboard.teknisi.findSolutions') }}
                                </p>
                            </div>
                        </Link>

                        <Link
                            href="/teknisi/reports"
                            class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition group"
                        >
                            <div
                                class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition"
                            >
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
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">
                                    {{ $t('dashboard.teknisi.myReports') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $t('dashboard.teknisi.performanceData') }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Right Column - Performance & Info -->
            <div class="space-y-6 sm:space-y-8">
                <!-- Performance Metrics -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ $t('dashboard.teknisi.performanceMetrics') }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <!-- Resolution Rate -->
                        <div class="group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-gray-700">{{ $t('dashboard.teknisi.resolutionRate') }}</span>
                                <span class="text-sm font-semibold" :class="performance.resolution_rate >= 80 ? 'text-green-600' : performance.resolution_rate >= 60 ? 'text-amber-600' : 'text-red-600'">
                                    {{ performance.resolution_rate }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-2 rounded-full transition-all duration-500"
                                    :class="performance.resolution_rate >= 80 ? 'bg-gradient-to-r from-green-400 to-green-500' : performance.resolution_rate >= 60 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-red-400 to-red-500'"
                                    :style="{ width: `${performance.resolution_rate}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Avg Resolution Time -->
                        <div class="flex items-center justify-between py-2 border-b border-gray-50">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">{{ $t('dashboard.teknisi.avgResolutionTime') || 'Avg Resolution Time' }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ performance.avg_resolution_time || 0 }}{{ $t('dashboard.teknisi.hours') || 'h' }}
                            </span>
                        </div>

                        <!-- Avg Response Time -->
                        <div class="flex items-center justify-between py-2 border-b border-gray-50">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">{{ $t('dashboard.teknisi.avgResponseTime') }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ performance.avg_response_time }}{{ $t('dashboard.teknisi.hours') || 'h' }}
                            </span>
                        </div>

                        <!-- Tickets This Week -->
                        <div class="flex items-center justify-between py-2 border-b border-gray-50">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">{{ $t('dashboard.teknisi.ticketsThisWeek') }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ performance.tickets_this_week }}</span>
                        </div>

                        <!-- User Rating -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">{{ $t('dashboard.teknisi.userRating') }}</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <div class="flex">
                                    <svg
                                        v-for="star in 5"
                                        :key="star"
                                        :class="['w-4 h-4', star <= performance.avg_rating ? 'text-amber-400' : 'text-gray-200']"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ performance.avg_rating?.toFixed(1) || '0.0' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Specializations -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ $t('dashboard.teknisi.mySpecializations') }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div v-if="specializations.length === 0" class="text-center py-4">
                            <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No specializations yet</p>
                        </div>
                        <div
                            v-for="skill in specializations"
                            :key="skill.name"
                            class="group"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-gray-700 truncate max-w-[150px]">{{ skill.name }}</span>
                                <span class="text-xs font-semibold" :class="skill.level >= 80 ? 'text-green-600' : skill.level >= 60 ? 'text-amber-600' : 'text-red-600'">
                                    {{ skill.level }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-2 rounded-full transition-all duration-500"
                                    :class="skill.level >= 80 ? 'bg-gradient-to-r from-green-400 to-green-500' : skill.level >= 60 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-red-400 to-red-500'"
                                    :style="{ width: `${skill.level}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Feedback -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ $t('dashboard.teknisi.recentFeedback') }}
                            </h2>
                        </div>
                    </div>

                    <div v-if="recentFeedback.length === 0" class="p-8 text-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">{{ $t('dashboard.teknisi.noFeedbackYet') }}</p>
                        <p class="text-xs text-gray-400 mt-1">Feedback from users will appear here</p>
                    </div>

                    <div v-else class="divide-y divide-gray-50">
                        <div
                            v-for="feedback in recentFeedback"
                            :key="feedback.id"
                            class="p-4 hover:bg-gray-50 transition-colors duration-150"
                        >
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <UserInitials :user="{ name: feedback.user_name }" size="sm" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="font-medium text-gray-900 text-sm truncate">{{ feedback.user_name }}</p>
                                        <div class="flex items-center space-x-1">
                                            <div class="flex">
                                                <svg
                                                    v-for="star in 5"
                                                    :key="star"
                                                    :class="['w-3 h-3', star <= feedback.rating ? 'text-amber-400' : 'text-gray-200']"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 line-clamp-2">{{ feedback.feedback }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ feedback.formatted_created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Detail Modal - Removed, using full page instead -->
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import axios from "axios";
import draggable from 'vuedraggable';
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import AnimatedNumber from '@/Components/UI/AnimatedNumber.vue';
import UserInitials from "@/Components/UI/UserInitials.vue";
import { useI18n } from 'vue-i18n';

const { t } = useI18n();


const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    myTickets: {
        type: Array,
        default: () => [],
    },
    performance: {
        type: Object,
        default: () => ({}),
    },
    specializations: {
        type: Array,
        default: () => [],
    },
    recentFeedback: {
        type: Array,
        default: () => [],
    },
});



const taskFilter = ref("all");
const isRefreshing = ref(false);

// Helper functions (defined early for use in other functions)
const getPriorityColor = (priority) => {
    const colors = {
        low: "bg-gray-100 text-gray-800",
        medium: "bg-blue-100 text-blue-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };
    return colors[priority] || "bg-gray-100 text-gray-800";
};

const isOverdue = (ticket) => {
    if (ticket.is_overdue) return true;
    if (!ticket.due_date) return false;
    return new Date(ticket.due_date) < new Date();
};

// Kanban columns - reactive arrays for drag-drop
const kanbanColumns = ref({
    assigned: [],
    in_progress: [],
    waiting_response: [],
    resolved: []
});

// Filter function based on taskFilter value
const filterTicket = (ticket) => {
    if (taskFilter.value === "all") return true;
    if (taskFilter.value === "urgent") return ticket.priority === "urgent" || ticket.priority === "high";
    if (taskFilter.value === "overdue") return isOverdue(ticket);
    return true;
};

// Initialize Kanban columns from props with filter applied
const initializeKanban = () => {
    const tickets = Array.isArray(props.myTickets) ? props.myTickets : [];
    kanbanColumns.value = {
        assigned: tickets.filter((ticket) => (ticket.status === "open" || ticket.status === "assigned") && filterTicket(ticket)),
        in_progress: tickets.filter((ticket) => ticket.status === "in_progress" && filterTicket(ticket)),
        waiting_response: tickets.filter((ticket) => (ticket.status === "waiting_user" || ticket.status === "waiting_response") && filterTicket(ticket)),
        resolved: tickets.filter((ticket) => ticket.status === "resolved" && filterTicket(ticket))
    };
};

// Legacy computed properties for compatibility
const assignedTickets = computed(() => kanbanColumns.value.assigned);
const inProgressTickets = computed(() => kanbanColumns.value.in_progress);
const pendingTickets = computed(() => kanbanColumns.value.waiting_response);
const resolvedTickets = computed(() => kanbanColumns.value.resolved);

// Watch for filter changes and re-initialize kanban
watch(taskFilter, () => {
    initializeKanban();
});

// Watch for myTickets prop changes
watch(() => props.myTickets, () => {
    initializeKanban();
}, { deep: true });

// Initialize on mount
onMounted(() => {
    initializeKanban();
});

const refreshTasks = async () => {
    if (isRefreshing.value) return;
    
    isRefreshing.value = true;
    try {
        await router.reload({ 
            only: ["myTickets", "stats", "performance"],
            onFinish: () => {
                isRefreshing.value = false;
                initializeKanban();
            }
        });
    } catch (error) {
        console.error('Failed to refresh tasks:', error);
        isRefreshing.value = false;
    }
};

const viewTicket = (ticketId) => {
    router.visit(route('teknisi.tickets.show', ticketId));
};

// Drag-drop handler
const onDragEnd = async (evt) => {
    const ticketId = evt.item.dataset.ticketId;
    const newStatus = evt.to.dataset.status;
    
    if (!ticketId || !newStatus) return;
    
    try {
        // Update ticket status via API
        const response = await axios.post(`/teknisi/tickets/${ticketId}/update-status`, {
            status: newStatus
        });
        
        if (response.data.success) {
            // Success - ticket already moved by draggable
            console.log('Ticket status updated:', response.data.ticket);
        }
    } catch (error) {
        console.error('Failed to update ticket status:', error);
        // Revert the drag on error
        initializeKanban();
    }
};

const startWorking = async (ticketId) => {
    try {
        await axios.post(`/teknisi/tickets/${ticketId}/update-status`, {
            status: "in_progress"
        });
        refreshTasks();
    } catch (error) {
        console.error('Failed to start working:', error);
    }
};

const pauseTicket = async (ticketId) => {
    try {
        await axios.post(`/teknisi/tickets/${ticketId}/update-status`, {
            status: "waiting_user"
        });
        refreshTasks();
    } catch (error) {
        console.error('Failed to pause ticket:', error);
    }
};

const resumeTicket = async (ticketId) => {
    try {
        await axios.post(`/teknisi/tickets/${ticketId}/update-status`, {
            status: "in_progress"
        });
        refreshTasks();
    } catch (error) {
        console.error('Failed to resume ticket:', error);
    }
};

const resolveTicket = (ticketId) => {
    router.visit(`/teknisi/tickets/${ticketId}`);
};

// Modal handlers
const handleTicketUpdated = (updatedTicket) => {
    refreshTasks();
};

// Removed modal handlers - using full page instead

const urgentTickets = computed(() => {
    const tickets = Array.isArray(props.myTickets) ? props.myTickets : [];
    return tickets.filter(ticket => {
        // Exclude resolved and closed tickets
        if (ticket.status === 'resolved' || ticket.status === 'closed') {
            return false;
        }
        // Include urgent priority, overdue, or due within 2 hours
        return ticket.priority === 'urgent' || 
            isOverdue(ticket) ||
            (ticket.due_date && new Date(ticket.due_date) <= new Date(Date.now() + 2 * 60 * 60 * 1000));
    }).slice(0, 5);
});
</script>

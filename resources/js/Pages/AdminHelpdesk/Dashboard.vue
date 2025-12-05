<template>
    <AppLayout role="admin">
        <template #header>
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 animate-slideInDown"
            >
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg"
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
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1
                                class="text-3xl sm:text-4xl font-bold text-gray-900"
                            >
                                {{ t('dashboard.admin.title') }}
                            </h1>
                            <p
                                class="text-gray-600 text-sm sm:text-base animate-fadeInUp animation-delay-200"
                            >
                                {{ t('dashboard.admin.description') }}
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
                            <span>{{ quickStats.total_users || 0 }} {{ t('dashboard.admin.users') }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="refreshDashboardStats"
                        :disabled="isRefreshing"
                        class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 disabled:from-gray-400 disabled:to-gray-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group disabled:cursor-not-allowed disabled:transform-none"
                    >
                        <svg
                            class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300"
                            :class="{ 'animate-spin': isRefreshing }"
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
                        <span class="hidden sm:inline">{{
                            isRefreshing ? t('dashboard.admin.refreshing') : t('action.refresh')
                        }}</span>
                        <span class="sm:hidden">{{
                            isRefreshing ? "⏳" : "🔄"
                        }}</span>
                    </button>
                    <Link
                        href="/admin/reports"
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
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        <span class="hidden sm:inline">{{ t('nav.reports') }}</span>
                        <span class="sm:hidden">📊</span>
                    </Link>
                    <Link
                        href="/admin/users-management"
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
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <span class="hidden sm:inline">{{ t('dashboard.admin.manageUsers') }}</span>
                        <span class="sm:hidden">👥</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Enhanced System Overview Stats -->
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 sm:gap-5 lg:gap-6 mb-6"
        >
            <div class="animate-fadeInUp animation-delay-100 w-full">
                <div
                    class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300 h-full"
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
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                                />
                            </svg>
                        </div>
                        <div
                            v-if="props.stats.tickets_today_trend"
                            class="flex items-center"
                            :class="
                                (props.stats.tickets_today_trend?.value || 0) >
                                0
                                    ? 'text-green-600'
                                    : 'text-red-600'
                            "
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    :d="
                                        (props.stats.tickets_today_trend
                                            ?.value || 0) > 0
                                            ? 'M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z'
                                            : 'M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z'
                                    "
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-xs font-medium ml-1"
                                >{{
                                    Math.abs(
                                        props.stats.tickets_today_trend
                                            ?.value || 0
                                    )
                                }}%</span
                            >
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber
                            :target="props.stats.tickets_today || 0"
                            :duration="1500"
                        />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">
                        {{ t('dashboard.admin.totalTicketsToday') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ t('dashboard.admin.newTicketsReceived') }}
                    </p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-200 w-full">
                <div
                    class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300 h-full"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg"
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
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"
                                />
                            </svg>
                        </div>
                        <div
                            v-if="props.stats.unassigned_tickets > 0"
                            class="w-2 h-2 bg-red-500 rounded-full animate-pulse"
                        ></div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber
                            :target="props.stats.unassigned_tickets || 0"
                            :duration="1500"
                        />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">
                        {{ t('dashboard.admin.unassignedTickets') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ t('dashboard.admin.needsAttention') }}</p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-300 w-full">
                <div
                    class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300 h-full"
                >
                    <div class="flex items-center justify-between mb-4">
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
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <div
                            v-if="props.stats.in_progress_tickets > 0"
                            class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"
                        ></div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber
                            :target="
                                props.stats.in_progress_tickets ||
                                props.stats.inProgressTickets ||
                                0
                            "
                            :duration="1500"
                        />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">
                        {{ t('dashboard.admin.inProgress') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ t('dashboard.admin.beingWorkedOn') }}</p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-400 w-full">
                <div
                    class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300 h-full"
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
                        <div
                            v-if="
                                props.stats.resolved_today_trend ||
                                props.stats.resolvedTodayTrend
                            "
                            class="flex items-center"
                            :class="
                                (props.stats.resolved_today_trend?.value ||
                                    props.stats.resolvedTodayTrend?.value ||
                                    0) > 0
                                    ? 'text-green-600'
                                    : 'text-red-600'
                            "
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    :d="
                                        (props.stats.resolved_today_trend
                                            ?.value ||
                                            props.stats.resolvedTodayTrend
                                                ?.value ||
                                            0) > 0
                                            ? 'M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z'
                                            : 'M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z'
                                    "
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-xs font-medium ml-1"
                                >{{
                                    Math.abs(
                                        props.stats.resolved_today_trend
                                            ?.value ||
                                            props.stats.resolvedTodayTrend
                                                ?.value ||
                                            0
                                    )
                                }}%</span
                            >
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber
                            :target="
                                props.stats.resolved_today ||
                                props.stats.resolvedToday ||
                                0
                            "
                            :duration="1500"
                        />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">
                        {{ t('dashboard.admin.resolvedToday') }}
                    </p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-500 w-full">
                <div
                    class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300 h-full"
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
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div
                            class="text-xs font-medium px-2 py-1 rounded-full"
                            :class="
                                (props.stats.avg_resolution_time ||
                                    props.stats.avgResolutionTime ||
                                    0) <= 2
                                    ? 'bg-green-100 text-green-800'
                                    : (props.stats.avg_resolution_time ||
                                          props.stats.avgResolutionTime ||
                                          0) <= 4
                                    ? 'bg-yellow-100 text-yellow-800'
                                    : 'bg-red-100 text-red-800'
                            "
                        >
                            {{
                                (props.stats.avg_resolution_time ||
                                    props.stats.avgResolutionTime ||
                                    0) <= 2
                                    ? t('dashboard.admin.fast')
                                    : (props.stats.avg_resolution_time ||
                                          props.stats.avgResolutionTime ||
                                          0) <= 4
                                    ? t('dashboard.admin.normal')
                                    : t('dashboard.admin.slow')
                            }}
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber
                            :target="
                                props.stats.avg_resolution_time ||
                                props.stats.avgResolutionTime ||
                                0
                            "
                            :duration="1500"
                            suffix="h"
                        />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">
                        {{ t('dashboard.admin.avgResolutionTime') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ t('dashboard.admin.last30days') }}</p>
                </div>
            </div>
        </div>

        <!-- Charts Section - 2 Column Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6 mb-6">
            <!-- Ticket Trend Chart -->
            <div
                class="bg-white rounded-xl shadow-lg p-5 sm:p-6 hover:shadow-2xl transition-all duration-300 border border-gray-100 overflow-hidden"
            >
                <div class="flex items-center space-x-3 mb-4">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center shadow-md"
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
                                d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                            />
                        </svg>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">
                        {{ t('dashboard.admin.ticketTrend') }}
                    </h2>
                </div>
                <div class="w-full overflow-hidden">
                    <LineChart
                        :data="ticketTrendChartData"
                        :height="250"
                        :options="chartOptions"
                    />
                </div>
            </div>

            <!-- Priority Distribution -->
            <div
                class="bg-white rounded-xl shadow-lg p-5 sm:p-6 hover:shadow-2xl transition-all duration-300 border border-gray-100 overflow-hidden"
            >
                <div class="flex items-center space-x-3 mb-4">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center shadow-md"
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
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">
                        {{ t('dashboard.admin.priorityDistribution') }}
                    </h2>
                </div>
                <div class="w-full overflow-hidden">
                    <BarChart
                        :data="priorityChartData"
                        :height="250"
                        :options="barChartOptions"
                    />
                </div>
            </div>

            <!-- Top Applications -->
            <div
                class="bg-white rounded-xl shadow-lg p-5 sm:p-6 hover:shadow-2xl transition-all duration-300 border border-gray-100 overflow-hidden"
            >
                <div class="flex items-center space-x-3 mb-4">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md"
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
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">
                        {{ t('dashboard.admin.topApplications') }}
                    </h2>
                </div>
                <div class="w-full overflow-hidden">
                    <BarChart
                        :data="applicationChartData"
                        :height="250"
                        :options="barChartOptions"
                    />
                </div>
            </div>

            <!-- Teknisi Workload -->
            <div
                class="bg-white rounded-xl shadow-lg p-5 sm:p-6 hover:shadow-2xl transition-all duration-300 border border-gray-100 overflow-hidden"
            >
                <div class="flex items-center space-x-3 mb-4">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg flex items-center justify-center shadow-md"
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
                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">
                        {{ t('dashboard.admin.teknisiWorkload') }}
                    </h2>
                </div>
                <div class="w-full overflow-hidden">
                    <BarChart
                        :data="teknisiWorkloadChartData"
                        :height="250"
                        :options="barChartOptions"
                    />
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid - Modern Professional Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-6 mb-6">
            <!-- Left Column - Primary Content (2 cols) -->
            <div class="lg:col-span-2 space-y-5 lg:space-y-6">
                <!-- Unassigned Tickets Queue -->
                <div
                    class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 flex flex-col h-full border border-gray-100"
                >
                    <div
                        class="p-5 sm:p-6 border-b border-gray-200 bg-gradient-to-r from-red-50 to-orange-50"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-600 rounded-lg flex items-center justify-center shadow-md"
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
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h2
                                    class="text-lg sm:text-xl font-bold text-gray-900"
                                >
                                    {{ t('dashboard.admin.unassignedTicketsQueue') }}
                                </h2>
                            </div>
                            <div class="flex items-center space-x-4">
                                <button
                                    @click="bulkAssignMode = !bulkAssignMode"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                >
                                    {{
                                        bulkAssignMode
                                            ? t('dashboard.admin.cancelBulk')
                                            : t('dashboard.admin.bulkAssign')
                                    }}
                                </button>
                                <Link
                                    href="/admin/tickets-management"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                >
                                    {{ t('dashboard.admin.viewAll') }} →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="unassignedTickets.length === 0"
                        class="p-8 text-center"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
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
                        <p class="mt-2 text-gray-500">
                            {{ t('dashboard.admin.allTicketsAssigned') }}
                        </p>
                        <p class="text-sm text-gray-400">
                            {{ t('dashboard.admin.greatJobManagingQueue') }}
                        </p>
                    </div>

                    <div
                        v-else
                        class="divide-y divide-gray-200 max-h-[420px] overflow-y-auto scrollbar-thin"
                    >
                        <div
                            v-for="ticket in unassignedTickets"
                            :key="ticket.id"
                            class="p-6 hover:bg-gray-50 transition"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <input
                                        v-if="bulkAssignMode"
                                        v-model="selectedTickets"
                                        :value="ticket.id"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <div class="flex-1">
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <span
                                                class="text-sm font-mono text-gray-500"
                                                >{{
                                                    ticket.ticket_number
                                                }}</span
                                            >
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
                                            <span
                                                v-if="ticket.is_overdue"
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800"
                                            >
                                                {{ t('dashboard.admin.overdue') }}
                                            </span>
                                        </div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 mt-2"
                                        >
                                            {{ ticket.title }}
                                        </h3>
                                        <div
                                            class="flex items-center space-x-4 mt-2 text-sm text-gray-500"
                                        >
                                            <span>{{ ticket.user.name }}</span>
                                            <span v-if="ticket.aplikasi">{{
                                                ticket.aplikasi.name
                                            }}</span>
                                            <span>{{
                                                ticket.formatted_created_at
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="!bulkAssignMode"
                                    class="flex items-center space-x-2"
                                >
                                    <select
                                        v-model="
                                            ticketTeknisiSelection[ticket.id]
                                        "
                                        class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                        :disabled="assigningTickets[ticket.id]"
                                    >
                                        <option :value="''" disabled selected>
                                            {{ t('dashboard.admin.selectTeknisi') }}
                                        </option>
                                        <option
                                            v-for="teknisi in availableTeknisi"
                                            :key="teknisi.id"
                                            :value="teknisi.id"
                                        >
                                            {{ teknisi.name }} ({{
                                                teknisi.active_tickets_count
                                            }}
                                            {{ t('dashboard.admin.active') }})
                                        </option>
                                    </select>
                                    <button
                                        v-if="ticketTeknisiSelection[ticket.id]"
                                        @click="
                                            assignTicket(
                                                ticket.id,
                                                ticketTeknisiSelection[
                                                    ticket.id
                                                ]
                                            )
                                        "
                                        :disabled="assigningTickets[ticket.id]"
                                        class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1"
                                    >
                                        <svg
                                            v-if="assigningTickets[ticket.id]"
                                            class="animate-spin h-4 w-4 text-white"
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
                                        <span>{{
                                            assigningTickets[ticket.id]
                                                ? t('ticket.assigning')
                                                : t('dashboard.admin.assign')
                                        }}</span>
                                    </button>
                                    <button
                                        @click="viewTicket(ticket.id)"
                                        class="p-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-md transition"
                                        :title="t('dashboard.admin.viewTicketDetails')"
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Assignment Controls -->
                    <div
                        v-if="bulkAssignMode && selectedTickets.length > 0"
                        class="p-6 border-t border-gray-200 bg-indigo-50"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <div class="flex items-center space-x-2">
                                <svg
                                    class="w-5 h-5 text-indigo-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                    />
                                </svg>
                                <span
                                    class="text-sm font-medium text-indigo-900"
                                >
                                    {{ selectedTickets.length }}
                                    {{
                                        selectedTickets.length === 1
                                            ? t('dashboard.admin.ticketSelected')
                                            : t('dashboard.admin.ticketsSelected')
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <select
                                    v-model="bulkAssignTeknisi"
                                    class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                    :disabled="bulkAssigning"
                                >
                                    <option :value="''" disabled selected>
                                        {{ t('dashboard.admin.selectTeknisi') }}
                                    </option>
                                    <option
                                        v-for="teknisi in availableTeknisi"
                                        :key="teknisi.id"
                                        :value="teknisi.id"
                                    >
                                        {{ teknisi.name }} ({{
                                            teknisi.active_tickets_count
                                        }}
                                        {{ t('dashboard.admin.active') }})
                                    </option>
                                </select>
                                <button
                                    @click="bulkAssignTickets"
                                    :disabled="
                                        !bulkAssignTeknisi || bulkAssigning
                                    "
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                                >
                                    <svg
                                        v-if="bulkAssigning"
                                        class="animate-spin h-4 w-4 text-white"
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
                                    <svg
                                        v-else
                                        class="w-4 h-4"
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
                                    <span>{{
                                        bulkAssigning
                                            ? t('ticket.assigning')
                                            : t('dashboard.admin.assignSelected')
                                    }}</span>
                                </button>
                                <button
                                    @click="
                                        bulkAssignMode = false;
                                        selectedTickets = [];
                                        bulkAssignTeknisi = '';
                                    "
                                    :disabled="bulkAssigning"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition disabled:opacity-50"
                                >
                                    {{ t('common.cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div
                    class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100"
                >
                    <div
                        class="p-5 sm:p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50"
                    >
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md"
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
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                            </div>
                            <h2
                                class="text-lg sm:text-xl font-bold text-gray-900"
                            >
                                {{ t('dashboard.admin.recentSystemActivity') }}
                            </h2>
                        </div>
                    </div>

                    <div
                        class="divide-y divide-gray-200 max-h-[300px] overflow-y-auto scrollbar-thin"
                    >
                        <div
                            v-if="
                                !recentActivity || recentActivity.length === 0
                            "
                            class="p-8 text-center text-gray-500"
                        >
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400"
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
                            <p class="mt-2 text-sm">{{ t('dashboard.admin.noRecentActivity') }}</p>
                        </div>
                        <div
                            v-else
                            v-for="activity in recentActivity"
                            :key="activity.id"
                            class="p-4 hover:bg-gray-50 transition"
                        >
                            <div class="flex items-start space-x-3">
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full mt-2 flex-shrink-0',
                                        getActivityColor(activity.type),
                                    ]"
                                ></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900">
                                        {{ activity.description }}
                                    </p>
                                    <div
                                        class="flex items-center space-x-4 mt-1 text-xs text-gray-500"
                                    >
                                        <span>{{ activity.user_name }}</span>
                                        <span>{{
                                            activity.formatted_created_at
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Teknisi Performance & System Health -->
            <div class="lg:col-span-1 space-y-5 lg:space-y-6">
                <!-- Teknisi Performance -->
                <div
                    class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 flex flex-col border border-gray-100"
                >
                    <div
                        class="p-5 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50"
                    >
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow-md"
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
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">
                                {{ t('dashboard.admin.teknisiPerformance') }}
                            </h2>
                        </div>
                    </div>

                    <div
                        v-if="
                            !teknisiPerformance ||
                            teknisiPerformance.length === 0
                        "
                        class="p-8 text-center text-gray-500"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <p class="mt-2 text-sm">{{ t('dashboard.admin.noTeknisiData') }}</p>
                    </div>

                    <div
                        v-else
                        class="max-h-[280px] overflow-y-auto scrollbar-thin"
                    >
                        <div class="p-5 space-y-3">
                            <div
                                v-for="teknisi in teknisiPerformance"
                                :key="teknisi.id"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center space-x-3">
                                    <UserInitials
                                        :user="{ name: teknisi.name }"
                                        size="md"
                                    />
                                    <div>
                                        <p
                                            class="font-medium text-gray-900 text-sm"
                                        >
                                            {{ teknisi.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ teknisi.keahlian }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">
                                        {{ teknisi.active_tickets_count }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ t('dashboard.admin.active') }}</p>
                                    <div class="flex items-center mt-1">
                                        <div class="flex text-yellow-400">
                                            <svg
                                                v-for="star in 5"
                                                :key="star"
                                                :class="[
                                                    'w-3 h-3',
                                                    star <=
                                                    Number(
                                                        teknisi.rating_avg || 0
                                                    )
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
                                            >{{
                                                Number(
                                                    teknisi.rating_avg || 0
                                                ).toFixed(1)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div
                    class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100"
                >
                    <div
                        class="p-5 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50"
                    >
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-md"
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
                            <h2 class="text-lg font-bold text-gray-900">
                                {{ t('dashboard.admin.systemHealth') }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900"
                                >{{ t('dashboard.admin.responseTime') }}</span
                            >
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-2 h-2 bg-green-500 rounded-full"
                                ></div>
                                <span class="text-sm text-gray-600"
                                    >{{ systemHealth.avg_response_time }}h</span
                                >
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900"
                                >{{ t('dashboard.admin.resolutionRate') }}</span
                            >
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-2 h-2 bg-green-500 rounded-full"
                                ></div>
                                <span class="text-sm text-gray-600"
                                    >{{ systemHealth.resolution_rate }}%</span
                                >
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900"
                                >{{ t('dashboard.admin.userSatisfaction') }}</span
                            >
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-2 h-2 bg-green-500 rounded-full"
                                ></div>
                                <span class="text-sm text-gray-600"
                                    >{{
                                        systemHealth.user_satisfaction
                                    }}/5.0</span
                                >
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900"
                                >{{ t('dashboard.admin.activeUsers') }}</span
                            >
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-2 h-2 bg-blue-500 rounded-full"
                                ></div>
                                <span class="text-sm text-gray-600">{{
                                    systemHealth.active_users
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div
                    class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100"
                >
                    <div
                        class="p-5 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-yellow-50"
                    >
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-lg flex items-center justify-center shadow-md"
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
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">
                                {{ t('dashboard.admin.quickStats') }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600"
                                >{{ t('dashboard.admin.totalUsers') }}</span
                            >
                            <span class="font-semibold text-gray-900">{{
                                quickStats.total_users
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600"
                                >{{ t('dashboard.admin.totalTeknisi') }}</span
                            >
                            <span class="font-semibold text-gray-900">{{
                                quickStats.total_teknisi
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600"
                                >{{ t('nav.applications') }}</span
                            >
                            <span class="font-semibold text-gray-900">{{
                                quickStats.total_applications
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600"
                                >{{ t('nav.categories') }}</span
                            >
                            <span class="font-semibold text-gray-900">{{
                                quickStats.total_categories
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import AppLayout from "@/Layouts/AppLayout.vue";
import AnimatedNumber from "@/Components/UI/AnimatedNumber.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import LineChart from "@/Components/Charts/LineChart.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import UserInitials from "@/Components/UI/UserInitials.vue";
import { useAdminDashboardRefresh } from "@/composables/useDashboardRefresh.js";
import { useNotificationPolling } from "@/composables/usePolling.js";

const { t, locale } = useI18n();

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    statusDistribution: {
        type: Object,
        default: () => ({}),
    },
    unassignedTickets: {
        type: Array,
        default: () => [],
    },
    availableTeknisi: {
        type: Array,
        default: () => [],
    },
    teknisiPerformance: {
        type: Array,
        default: () => [],
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
    systemHealth: {
        type: Object,
        default: () => ({}),
    },
    quickStats: {
        type: Object,
        default: () => ({}),
    },
    chartData: {
        type: Object,
        default: () => ({}),
    },
});

const bulkAssignMode = ref(false);
const selectedTickets = ref([]);
const bulkAssignTeknisi = ref("");
const ticketTeknisiSelection = ref({});
const assigningTickets = ref({});
const bulkAssigning = ref(false);

// Initialize ticket teknisi selection with empty string for all tickets
const initializeTicketSelections = () => {
    if (props.unassignedTickets && props.unassignedTickets.length > 0) {
        const newSelections = {};
        props.unassignedTickets.forEach((ticket) => {
            newSelections[ticket.id] = "";
        });
        ticketTeknisiSelection.value = {
            ...ticketTeknisiSelection.value,
            ...newSelections,
        };
    }
};

// Watch for changes in unassigned tickets and reinitialize
watch(
    () => props.unassignedTickets,
    (newTickets) => {
        if (newTickets && newTickets.length > 0) {
            initializeTicketSelections();
        }
    },
    { immediate: true, deep: true }
);

const chartOptions = {
    plugins: {
        legend: {
            display: true,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};

const barChartOptions = {
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};

const ticketTrendChartData = computed(() => {
    // Data is already in Chart.js format from controller
    if (!props.chartData || !props.chartData.ticketTrend) {
        return { labels: [], datasets: [] };
    }
    return props.chartData.ticketTrend;
});

const priorityChartData = computed(() => {
    // Data is already in Chart.js format from controller
    if (!props.chartData || !props.chartData.priorityData) {
        return { labels: [], datasets: [] };
    }
    return props.chartData.priorityData;
});

const applicationChartData = computed(() => {
    // Data is already in Chart.js format from controller
    if (!props.chartData || !props.chartData.applicationData) {
        return { labels: [], datasets: [] };
    }
    return props.chartData.applicationData;
});

const teknisiWorkloadChartData = computed(() => {
    // Data is already in Chart.js format from controller
    if (!props.chartData || !props.chartData.teknisiWorkload) {
        return { labels: [], datasets: [] };
    }
    return props.chartData.teknisiWorkload;
});

const assignTicket = (ticketId, teknisiId) => {
    if (!teknisiId) return;

    assigningTickets.value[ticketId] = true;

    router.post(
        `/admin/tickets-management/${ticketId}/assign`,
        {
            teknisi_nip: teknisiId,
        },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                // Clear the selection for this ticket
                delete ticketTeknisiSelection.value[ticketId];
                delete assigningTickets.value[ticketId];
                // Refresh the dashboard data
                router.reload({
                    only: ["unassignedTickets", "availableTeknisi", "stats"],
                });
            },
            onError: (errors) => {
                delete assigningTickets.value[ticketId];
                console.error("Failed to assign ticket:", errors);

                // Extract error message
                let errorMessage = t('message.assignTicketFailed');
                if (typeof errors === "object") {
                    if (errors.message) {
                        errorMessage = errors.message;
                    } else if (errors.errors) {
                        const errorList = Object.values(errors.errors).flat();
                        errorMessage = errorList.join(", ");
                    } else {
                        errorMessage = JSON.stringify(errors);
                    }
                }

                alert(errorMessage);
            },
        }
    );
};

const bulkAssignTickets = () => {
    if (!bulkAssignTeknisi.value || selectedTickets.value.length === 0) return;

    bulkAssigning.value = true;

    router.post(
        "/admin/tickets-management/bulk-assign",
        {
            ticket_ids: selectedTickets.value,
            teknisi_nip: bulkAssignTeknisi.value,
        },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                // Clear selections
                selectedTickets.value = [];
                bulkAssignTeknisi.value = "";
                bulkAssignMode.value = false;
                bulkAssigning.value = false;

                // Refresh the dashboard data
                router.reload({
                    only: ["unassignedTickets", "availableTeknisi", "stats"],
                });
            },
            onError: (errors) => {
                bulkAssigning.value = false;
                console.error("Failed to bulk assign tickets:", errors);

                // Extract error message
                let errorMessage = t('message.bulkAssignTicketFailed');
                if (typeof errors === "object") {
                    if (errors.bulk_assign) {
                        errorMessage = errors.bulk_assign;
                    } else if (errors.message) {
                        errorMessage = errors.message;
                    } else if (errors.errors) {
                        const errorList = Object.values(errors.errors).flat();
                        errorMessage = errorList.join(", ");
                    } else {
                        errorMessage = JSON.stringify(errors);
                    }
                }

                alert(errorMessage);
            },
            onFinish: () => {
                bulkAssigning.value = false;
            },
        }
    );
};

const viewTicket = (ticketId) => {
    router.visit(`/admin/tickets-management/${ticketId}`);
};

const getStatusColor = (status) => {
    const colors = {
        open: "bg-yellow-500",
        assigned: "bg-blue-500",
        in_progress: "bg-indigo-500",
        waiting_response: "bg-orange-500",
        resolved: "bg-green-500",
        closed: "bg-gray-500",
        cancelled: "bg-red-500",
    };
    return colors[status] || "bg-gray-500";
};

const getStatusBgColor = (status) => {
    const colors = {
        open: "bg-yellow-500",
        assigned: "bg-blue-500",
        in_progress: "bg-indigo-500",
        waiting_response: "bg-orange-500",
        resolved: "bg-green-500",
        closed: "bg-gray-500",
        cancelled: "bg-red-500",
    };
    return colors[status] || "bg-gray-500";
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

const getActivityColor = (type) => {
    const colors = {
        ticket_created: "bg-blue-500",
        ticket_assigned: "bg-indigo-500",
        ticket_resolved: "bg-green-500",
        user_created: "bg-purple-500",
        system_update: "bg-gray-500",
    };
    return colors[type] || "bg-gray-500";
};

// Manual refresh for dashboard stats
const isRefreshing = ref(false);

const refreshDashboardStats = async () => {
    isRefreshing.value = true;
    try {
        const response = await fetch("/admin/dashboard/refresh-stats", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                // Refresh the page to show updated data
                router.reload({ only: ["stats"] });
            }
        }
    } catch (error) {
        console.error("Failed to refresh dashboard stats:", error);
    } finally {
        isRefreshing.value = false;
    }
};
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

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
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
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

.animate-slideInDown {
    animation: slideInDown 0.5s ease-out;
}

.animate-slideInUp {
    animation: slideInUp 0.5s ease-out;
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

@media (max-width: 380px) {
    /* Extra small screens */
    .px-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .py-8 {
        padding-top: 1.5rem;
        padding-bottom: 1rem;
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
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Firefox scrollbar styling */
.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Smooth scroll behavior */
.scrollbar-thin {
    scroll-behavior: smooth;
}

/* Scroll fade effect - shows content continues below */
.scrollbar-thin {
    position: relative;
}

/* Optional: Add shadow when scrolling to indicate more content */
.scrollbar-thin::-webkit-scrollbar-track {
    box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
}

/* Optimize card spacing and layout */
@media (min-width: 1024px) {
    .lg\:col-span-8 {
        grid-column: span 8 / span 8;
    }
    .lg\:col-span-4 {
        grid-column: span 4 / span 4;
    }
}

/* Better grid alignment */
.grid {
    display: grid;
    align-items: start; /* Prevent stretching */
}

/* Card height consistency */
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-2px);
}

/* Compact spacing for smaller screens */
@media (max-width: 640px) {
    .p-6 {
        padding: 1rem;
    }

    .gap-6 {
        gap: 1rem;
    }
}

/* Improve readability on all screens */
.text-sm {
    line-height: 1.5;
}

/* Better card shadows */
.shadow-md {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>

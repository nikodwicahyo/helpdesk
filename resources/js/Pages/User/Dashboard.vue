<template>
    <AppLayout role="user">
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 animate-slideInDown">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $t('dashboard.user.title') }}</h1>
                            <p class="text-gray-600 text-sm sm:text-base animate-fadeInUp animation-delay-200">
                                {{ $t('dashboard.welcomeBack') }}, <span class="font-semibold text-indigo-600">{{ $page.props.auth.user?.name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ $t('dashboard.user.online') }}
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ new Date().toLocaleString($t('time.locale'), { weekday: 'long', hour: '2-digit', minute: '2-digit' }) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <Link
                        :href="route('user.tickets.create')"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg
                            class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('nav.createTicket') }}</span>
                        <span class="sm:hidden">{{ $t('dashboard.user.newTicket') }}</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Enhanced Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6">
            <div class="animate-fadeInUp animation-delay-100">
                <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div v-if="stats.tickets_trend" class="flex items-center" :class="stats.tickets_trend > 0 ? 'text-green-600' : 'text-red-600'">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" :d="stats.tickets_trend > 0 ? 'M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z' : 'M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z'" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-medium ml-1">{{ Math.abs(stats.tickets_trend) }}%</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber :target="stats.total_tickets" :duration="1500" />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $t('dashboard.totalTickets') }}</p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-200">
                <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber :target="stats.active_tickets" :duration="1500" />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $t('dashboard.user.activeTickets') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $t('dashboard.user.activeTicketsDescription') }}</p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-300">
                <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div v-if="stats.resolved_trend" class="flex items-center" :class="stats.resolved_trend > 0 ? 'text-green-600' : 'text-red-600'">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" :d="stats.resolved_trend > 0 ? 'M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z' : 'M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z'" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-medium ml-1">{{ Math.abs(stats.resolved_trend) }}%</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber :target="stats.resolved_tickets" :duration="1500" />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $t('dashboard.user.resolvedThisMonth') }}</p>
                </div>
            </div>

            <div class="animate-fadeInUp animation-delay-400">
                <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-6 hover-lift transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="text-xs font-medium px-2 py-1 rounded-full" :class="stats.resolution_rate >= 80 ? 'bg-green-100 text-green-800' : stats.resolution_rate >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'">
                            {{ stats.resolution_rate >= 80 ? $t('dashboard.user.excellent') : stats.resolution_rate >= 60 ? $t('dashboard.user.good') : $t('dashboard.user.needsWork') }}
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900">
                        <AnimatedNumber :target="stats.resolution_rate" :duration="1500" suffix="%" />
                    </h3>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $t('dashboard.user.resolutionRate') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $t('dashboard.user.resolutionRateDescription') }}</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Quick Actions & Filters -->
        <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg border border-white/20 p-3 sm:p-4 lg:p-6 mb-6 sm:mb-8 animate-fadeInUp animation-delay-500">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('user.tickets.create')"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span class="hidden sm:inline">{{ $t('nav.createTicket') }}</span>
                        <span class="sm:hidden">{{ $t('dashboard.user.newTicket') }}</span>
                    </Link>
                    <Link
                        :href="route('user.tickets.index')"
                        class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
                    >
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="hidden sm:inline">{{ $t('dashboard.user.viewAllTickets') }}</span>
                        <span class="sm:hidden">{{ $t('dashboard.user.allTickets') }}</span>
                    </Link>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative">
                        <select
                            v-model="statusFilter"
                            @change="filterTickets"
                            class="appearance-none bg-white border-2 border-gray-300 rounded-xl px-4 py-2.5 pr-10 focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 text-sm sm:text-base font-medium"
                        >
                            <option value="">{{ $t('dashboard.user.allStatus') }}</option>
                            <option value="open">{{ $t('status.open') }}</option>
                            <option value="in_progress">{{ $t('status.inProgress') }}</option>
                            <option value="resolved">{{ $t('status.resolved') }}</option>
                            <option value="closed">{{ $t('status.closed') }}</option>
                        </select>
                        <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="relative">
                        <select
                            v-model="priorityFilter"
                            @change="filterTickets"
                            class="appearance-none bg-white border-2 border-gray-300 rounded-xl px-4 py-2.5 pr-10 focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 text-sm sm:text-base font-medium"
                        >
                            <option value="">{{ $t('dashboard.user.allPriority') }}</option>
                            <option value="low">{{ $t('priority.low') }}</option>
                            <option value="medium">{{ $t('priority.medium') }}</option>
                            <option value="high">{{ $t('priority.high') }}</option>
                            <option value="urgent">{{ $t('priority.urgent') }}</option>
                        </select>
                        <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8">
            <!-- Weekly Activity Chart -->
            <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-4 sm:p-6 animate-fadeInUp animation-delay-600 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ $t('dashboard.user.weeklyActivity') }}</h3>
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="relative overflow-hidden">
                    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-lg z-10">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>
                    <div class="w-full overflow-hidden">
                        <LineChart
                            :data="weeklyActivityChartData"
                            :height="280"
                            :options="chartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-4 sm:p-6 animate-fadeInUp animation-delay-700 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ $t('dashboard.user.ticketStatusDistribution') }}</h3>
                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow-md">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                    </div>
                </div>
                <div class="relative overflow-hidden">
                    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-lg z-10">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    </div>
                    <div class="w-full overflow-hidden">
                        <PieChart
                            :data="statusChartData"
                            :height="280"
                            :options="pieChartOptions"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Priority and Application Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8">
            <!-- Priority Distribution -->
            <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-4 sm:p-6 animate-fadeInUp animation-delay-800 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ $t('dashboard.user.priorityDistribution') }}</h3>
                    <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center shadow-md">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="relative overflow-hidden">
                    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-lg z-10">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600"></div>
                    </div>
                    <div class="w-full overflow-hidden">
                        <BarChart
                            :data="priorityChartData"
                            :height="280"
                            :options="barChartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Top Applications -->
            <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-lg hover:shadow-xl border border-white/20 p-4 sm:p-6 animate-fadeInUp animation-delay-900 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ $t('dashboard.user.mostUsedApplications') }}</h3>
                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-teal-600 rounded-lg flex items-center justify-center shadow-md">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                </div>
                <div class="relative overflow-hidden">
                    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-lg z-10">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                    </div>
                    <div class="w-full overflow-hidden">
                        <BarChart
                            :data="applicationChartData"
                            :height="280"
                            :options="barChartOptions"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <!-- Left Column - Recent Tickets & Quick Actions -->
            <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                <!-- Recent Tickets -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ $t('dashboard.user.recentTickets') }}
                            </h2>
                            <Link
                                :href="route('user.tickets.index')"
                                class="text-indigo-600 hover:text-indigo-800 font-medium text-sm"
                            >
                                {{ $t('action.viewAll') }} →
                            </Link>
                        </div>
                    </div>

                    <div
                        v-if="recentTickets.length === 0"
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
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                            />
                        </svg>
                        <p class="mt-2 text-gray-500">{{ $t('dashboard.user.noTicketsFound') }}</p>
                        <p class="text-sm text-gray-400">
                            {{ $t('dashboard.user.createFirstTicket') }}
                        </p>
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <div
                            v-for="ticket in recentTickets"
                            :key="ticket.id"
                            class="p-6 hover:bg-gray-50 transition cursor-pointer"
                            @click="viewTicket(ticket.id)"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <span
                                            class="text-sm font-mono text-gray-500"
                                            >{{ ticket.ticket_number }}</span
                                        >
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                getStatusColor(ticket.status),
                                            ]"
                                        >
                                            {{ ticket.status_label }}
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
                                    <h3
                                        class="text-lg font-medium text-gray-900 mt-2"
                                    >
                                        {{ ticket.title }}
                                    </h3>
                                    <div
                                        class="flex items-center space-x-4 mt-2 text-sm text-gray-500"
                                    >
                                        <span v-if="ticket.aplikasi">{{
                                            ticket.aplikasi.name
                                        }}</span>
                                        <span v-if="ticket.kategori_masalah">{{
                                            ticket.kategori_masalah.name
                                        }}</span>
                                        <span>{{
                                            ticket.formatted_created_at
                                        }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div
                                        v-if="ticket.assigned_teknisi"
                                        class="text-right"
                                    >
                                        <p
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ ticket.assigned_teknisi.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $t('dashboard.user.assignedTeknisi') }}
                                        </p>
                                    </div>
                                    <svg
                                        class="w-5 h-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        {{ $t('dashboard.user.quickActions') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link
                            :href="route('user.tickets.create')"
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
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">
                                    {{ $t('dashboard.user.newTicket') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $t('dashboard.user.reportAnIssue') }}
                                </p>
                            </div>
                        </Link>

                        <Link
                            :href="route('user.tickets.index')"
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
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">
                                    {{ $t('ticket.viewTicket') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $t('dashboard.user.trackProgress') }}
                                </p>
                            </div>
                        </Link>

                        <Link
                            href="/user/applications"
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
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">
                                    {{ $t('nav.applications') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $t('dashboard.user.browseServices') }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar Info -->
            <div class="space-y-6 sm:space-y-8">
                <!-- Application Usage -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $t('dashboard.user.mostUsedApplications') }}
                        </h2>
                    </div>

                    <div
                        v-if="applicationStats.length === 0"
                        class="p-6 text-center"
                    >
                        <svg
                            class="mx-auto h-8 w-8 text-gray-400"
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
                        <p class="mt-2 text-sm text-gray-500">
                            {{ $t('dashboard.user.noApplicationUsageData') }}
                        </p>
                    </div>

                    <div v-else class="p-6 space-y-4">
                        <div
                            v-for="app in applicationStats"
                            :key="app.aplikasi.id"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center"
                                >
                                    <span
                                        class="text-blue-600 text-sm font-semibold"
                                        >{{ app.aplikasi.code }}</span
                                    >
                                </div>
                                <div>
                                    <p
                                        class="font-medium text-gray-900 text-sm"
                                    >
                                        {{ app.aplikasi.name }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">
                                    {{ app.ticket_count }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $t('dashboard.user.tickets') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $t('dashboard.recentActivity') }}
                        </h2>
                    </div>

                    <div
                        v-if="notifications.length === 0"
                        class="p-6 text-center"
                    >
                        <svg
                            class="mx-auto h-8 w-8 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ $t('dashboard.user.noRecentActivity') }}
                        </p>
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="p-4 hover:bg-gray-50 transition"
                        >
                            <div class="flex items-start space-x-3">
                                <div
                                    class="w-2 h-2 bg-blue-600 rounded-full mt-2 flex-shrink-0"
                                ></div>
                                <div class="flex-1">
                                    <p
                                        class="font-medium text-gray-900 text-sm"
                                    >
                                        {{ notification.title }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ notification.message }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ notification.formatted_created_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </AppLayout>
</template>

<script setup>
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import AnimatedNumber from "@/Components/UI/AnimatedNumber.vue";
import LineChart from "@/Components/Charts/LineChart.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import PieChart from "@/Components/Charts/PieChart.vue";
import { useI18n } from 'vue-i18n';

const { t } = useI18n();



const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recentTickets: {
        type: Array,
        default: () => [],
    },
    upcomingDeadlines: {
        type: Array,
        default: () => [],
    },
    applicationStats: {
        type: Array,
        default: () => [],
    },
    notifications: {
        type: Array,
        default: () => [],
    },
});



const chartData = ref({
    status_distribution: {},
    priority_distribution: {},
    weekly_activity: {},
    application_stats: [],
});

const loading = ref(true);

const statusFilter = ref('');
const priorityFilter = ref('');

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

const pieChartOptions = {
    plugins: {
        legend: {
            position: 'bottom',
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

const weeklyActivityChartData = computed(() => {
    const weeklyActivity = chartData.value.weekly_activity || {};
    
    // Generate last 7 days in order
    const labels = [];
    const data = [];
    const today = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        
        // Format date as YYYY-MM-DD using local timezone to match backend format
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const dateKey = `${year}-${month}-${day}`;
        
        // Create label as "Weekday Day" (e.g., "Mon 25")
        const weekday = date.toLocaleDateString(t('time.locale'), { weekday: 'short' });
        labels.push(`${weekday} ${date.getDate()}`);
        
        // Get count for this date, default to 0
        data.push(weeklyActivity[dateKey] || 0);
    }

    return {
        labels,
        datasets: [{
            label: t('dashboard.user.ticketsCreated'),
            data,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
        }],
    };
});

const statusChartData = computed(() => {
    const statusColors = {
        open: '#fbbf24',
        assigned: '#3b82f6',
        in_progress: '#6366f1',
        waiting_response: '#f97316',
        resolved: '#10b981',
        closed: '#6b7280',
        cancelled: '#ef4444',
    };

    const statusDistribution = chartData.value.status_distribution || {};
    const labels = Object.keys(statusDistribution).map(status => {
        switch (status) {
            case 'open': return t('status.open');
            case 'assigned': return t('status.assigned');
            case 'in_progress': return t('status.inProgress');
            case 'waiting_response': return t('status.waiting'); // Assuming waiting_response maps to waiting
            case 'resolved': return t('status.resolved');
            case 'closed': return t('status.closed');
            case 'cancelled': return t('status.cancelled');
            default: return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    });
    const data = Object.values(statusDistribution);
    const backgroundColors = Object.keys(statusDistribution).map(status =>
        statusColors[status] || '#6b7280'
    );

    // Provide default data if empty
    if (labels.length === 0) {
        return {
            labels: [t('dashboard.user.noData')],
            datasets: [{
                data: [1],
                backgroundColor: ['#e5e7eb'],
                borderWidth: 1,
            }],
        };
    }

    return {
        labels,
        datasets: [{
            data,
            backgroundColor: backgroundColors,
            borderWidth: 1,
        }],
    };
});

const priorityChartData = computed(() => {
    const priorityColors = {
        low: '#10b981',
        medium: '#3b82f6',
        high: '#f97316',
        urgent: '#ef4444',
    };

    const priorityDistribution = chartData.value.priority_distribution || {};
    const labels = Object.keys(priorityDistribution).map(priority => {
        switch (priority) {
            case 'low': return t('priority.low');
            case 'medium': return t('priority.medium');
            case 'high': return t('priority.high');
            case 'urgent': return t('priority.urgent');
            default: return priority.charAt(0).toUpperCase() + priority.slice(1);
        }
    });
    const data = Object.values(priorityDistribution);
    const backgroundColors = Object.keys(priorityDistribution).map(priority =>
        priorityColors[priority] || '#6b7280'
    );

    // Provide default data if empty
    if (labels.length === 0) {
        return {
            labels: [t('priority.low'), t('priority.medium'), t('priority.high'), t('priority.urgent')],
            datasets: [{
                label: t('dashboard.user.tickets'),
                data: [0, 0, 0, 0],
                backgroundColor: ['#10b981', '#3b82f6', '#f97316', '#ef4444'],
                borderWidth: 1,
            }],
        };
    }

    return {
        labels,
        datasets: [{
            label: t('dashboard.user.tickets'),
            data,
            backgroundColor: backgroundColors,
            borderWidth: 1,
        }],
    };
});

const applicationChartData = computed(() => {
    const applicationStats = chartData.value.application_stats || [];
    const labels = applicationStats.map(app => app.code || app.name);
    const data = applicationStats.map(app => app.count || app.ticket_count);

    // Provide default data if empty
    if (labels.length === 0) {
        return {
            labels: [t('dashboard.user.noApplications')],
            datasets: [{
                label: t('dashboard.user.tickets'),
                data: [0],
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderWidth: 1,
            }],
        };
    }

    return {
        labels,
        datasets: [{
            label: t('dashboard.user.tickets'),
            data,
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderWidth: 1,
        }],
    };
});

onMounted(async () => {
    try {
        const response = await fetch(route('user.dashboard.stats'));
        const data = await response.json();
        chartData.value = data;
    } catch (error) {
        console.error(t('dashboard.user.failedToLoadChartData'), error);
    } finally {
        loading.value = false;
    }
});

const filterTickets = () => {
    // Build query parameters for filtering
    const params = new URLSearchParams();

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    if (priorityFilter.value) {
        params.append('priority', priorityFilter.value);
    }

    // Navigate to tickets index with filters
    const url = route('user.tickets.index') + (params.toString() ? '?' + params.toString() : '');
    router.visit(url);
};

const viewTicket = (ticketId) => {
    router.visit(route("user.tickets.show", ticketId));
};

const getStatusColor = (status) => {
    const colors = {
        open: "bg-yellow-100 text-yellow-800",
        assigned: "bg-blue-100 text-blue-800",
        in_progress: "bg-indigo-100 text-indigo-800",
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
  outline: 2px solid #4F46E5;
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
</style>

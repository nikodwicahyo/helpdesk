<template>
    <AppLayout role="teknisi">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $t('teknisi.performanceReports') }}</h1>
                        <p class="text-gray-500 text-sm">{{ $t('teknisi.viewPerformanceMetrics') }}</p>
                    </div>
                </div>
                
                <!-- Export Button -->
                <div class="relative">
                    <button
                        @click="showExportMenu = !showExportMenu"
                        class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:from-green-600 hover:to-emerald-700 transition-all shadow-sm hover:shadow-md flex items-center text-m"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ $t('action.export') }}
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-if="showExportMenu" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                        <button
                            @click="exportReport('excel')"
                            class="w-full px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center transition"
                        >
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ $t('action.exportAsExcel') }}
                        </button>
                        <button
                            @click="exportReport('pdf')"
                            class="w-full px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center transition"
                        >
                            <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            {{ $t('action.exportAsPDF') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Performance Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
            <!-- Tickets Resolved -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $t('teknisi.ticketsResolved') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">{{ safePerformanceData.totalResolved }}</p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $t('teknisi.inSelectedPeriod') }}</p>
                </div>
            </div>

            <!-- Average Resolution Time -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ t('teknisiReports.avgResolutionTime') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">{{ safePerformanceData.avgResolutionTime }}</p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ t('teknisiReports.perTicket') }}</p>
                </div>
            </div>

            <!-- Satisfaction Rate -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-amber-100 to-orange-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="safePerformanceData.satisfactionRate >= 80 ? 'bg-green-100 text-green-700' : safePerformanceData.satisfactionRate >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'">
                        {{ safePerformanceData.satisfactionRate >= 80 ? t('teknisiReports.great') : safePerformanceData.satisfactionRate >= 60 ? t('teknisiReports.good') : t('teknisiReports.needsWork') }}
                    </span>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ t('teknisiReports.satisfactionRate') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">{{ safePerformanceData.satisfactionRate }}%</p>
                    </div>
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all duration-500" :class="safePerformanceData.satisfactionRate >= 80 ? 'bg-green-500' : safePerformanceData.satisfactionRate >= 60 ? 'bg-amber-500' : 'bg-red-500'" :style="{ width: `${safePerformanceData.satisfactionRate}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- Productivity Score -->
            <div class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full -mr-12 -mt-12 opacity-50"></div>
                <div class="flex items-start justify-between mb-3 relative z-10">
                    <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="safePerformanceData.productivityScore >= 80 ? 'bg-purple-100 text-purple-700' : safePerformanceData.productivityScore >= 60 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'">
                        {{ safePerformanceData.productivityScore >= 80 ? t('teknisiReports.excellent') : safePerformanceData.productivityScore >= 60 ? t('teknisiReports.good') : t('teknisiReports.average') }}
                    </span>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ t('teknisiReports.productivityScore') }}</h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">{{ safePerformanceData.productivityScore }}</p>
                        <span class="ml-1 text-sm text-gray-500">/100</span>
                    </div>
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500" :style="{ width: `${safePerformanceData.productivityScore}%` }"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">{{ t('teknisiReports.filterByDate') }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Period Presets -->
                    <div class="flex items-center rounded-lg border border-gray-200 overflow-hidden">
                        <button
                            v-for="period in [{ value: 'week', label: t('teknisiReports.week') }, { value: 'month', label: t('teknisiReports.month') }, { value: 'quarter', label: t('teknisiReports.quarter') }, { value: 'year', label: t('teknisiReports.year') }]"
                            :key="period.value"
                            @click="selectedPeriod = period.value; applyPeriodPreset()"
                            :class="[
                                'px-3 py-2 text-sm font-medium transition-colors',
                                selectedPeriod === period.value ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'
                            ]"
                        >
                            {{ period.label }}
                        </button>
                    </div>

                    <!-- Date Range Picker -->
                    <div class="flex items-center space-x-2 bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input
                            v-model="dateRange.start"
                            type="date"
                            class="bg-transparent border-0 focus:ring-0 text-sm p-0 w-28"
                        />
                        <span class="text-gray-400">{{ t('teknisiReports.to') }}</span>
                        <input
                            v-model="dateRange.end"
                            type="date"
                            class="bg-transparent border-0 focus:ring-0 text-sm p-0 w-28"
                        />
                        <button
                            @click="selectedPeriod = ''; loadReports()"
                            :disabled="isLoading"
                            class="bg-indigo-600 text-white px-3 py-1 rounded-md font-medium hover:bg-indigo-700 transition text-sm disabled:opacity-50 flex items-center"
                        >
                            <svg v-if="isLoading" class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            {{ t('teknisiReports.apply') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Performance Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-lg flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.performanceTrend') }}</h3>
                        </div>
                        <span class="text-xs text-gray-500">{{ dateRange.start }} - {{ dateRange.end }}</span>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="hasChartData" class="relative w-full" style="height: 300px;">
                        <LineChart :data="performanceChartData" />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center text-gray-400" style="height: 300px;">
                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm font-medium">{{ t('teknisiReports.noDataAvailable') }}</p>
                        <p class="text-xs mt-1">{{ t('teknisiReports.tryAdjustingDateRange') }}</p>
                    </div>
                </div>
            </div>

            <!-- Achievement Badges -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.recentAchievements') }}</h3>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="props.achievements && props.achievements.length > 0" class="space-y-3">
                        <div
                            v-for="(achievement, index) in props.achievements"
                            :key="index"
                            class="flex items-center p-4 bg-gradient-to-r from-amber-50 to-yellow-50 rounded-xl border border-amber-100 hover:shadow-md transition-all duration-200"
                        >
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center shadow-md">
                                <span class="text-xl">{{ getAchievementIcon(achievement.type) }}</span>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-semibold text-gray-900">{{ achievement.title }}</p>
                                <p class="text-xs text-gray-600 mt-0.5">{{ achievement.description }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">{{ t('teknisiReports.noAchievementsYet') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ t('teknisiReports.keepWorkingToEarnAchievements') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <!-- Category Breakdown -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.ticketsByCategory') }}</h3>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="hasCategoryData" class="relative w-full" style="height: 300px;">
                        <BarChart :data="categoryChartData" />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center text-gray-400" style="height: 300px;">
                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <p class="text-sm font-medium">{{ t('teknisiReports.noCategoryData') }}</p>
                        <p class="text-xs mt-1">{{ t('teknisiReports.resolveTicketsToSeeCategoryBreakdown') }}</p>
                    </div>
                </div>
            </div>

            <!-- Priority Distribution -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.priorityDistribution') }}</h3>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="hasPriorityData" class="flex items-center justify-center" style="height: 300px;">
                        <div style="width: 380px; height: 280px;">
                            <PieChart :data="priorityChartData" type="doughnut" />
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center text-gray-400" style="height: 300px;">
                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        </svg>
                        <p class="text-sm font-medium">{{ t('teknisiReports.noPriorityData') }}</p>
                        <p class="text-xs mt-1">{{ t('teknisiReports.noTicketsInSelectedPeriod') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Details Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <!-- Performance Metrics -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.performanceMetrics') }}</h2>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Resolution Rate -->
                    <div class="group">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-gray-700">{{ t('teknisiReports.resolutionRate') }}</span>
                            <span class="text-sm font-semibold" :class="safePerformance.resolution_rate >= 80 ? 'text-green-600' : safePerformance.resolution_rate >= 60 ? 'text-amber-600' : 'text-red-600'">
                                {{ safePerformance.resolution_rate }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                :class="safePerformance.resolution_rate >= 80 ? 'bg-gradient-to-r from-green-400 to-green-500' : safePerformance.resolution_rate >= 60 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-red-400 to-red-500'"
                                :style="{ width: `${safePerformance.resolution_rate}%` }"
                            ></div>
                        </div>
                    </div>
                    <!-- Avg Resolution Time -->
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">{{ t('teknisiReports.avgResolutionTime') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ safePerformance.avg_resolution_time }}h</span>
                    </div>
                    <!-- Avg Response Time -->
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">{{ t('teknisiReports.avgResponseTime') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ safePerformance.avg_response_time }}h</span>
                    </div>
                    <!-- Tickets This Week -->
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">{{ t('teknisiReports.ticketsThisWeek') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ safePerformance.tickets_this_week }}</span>
                    </div>
                    <!-- User Rating -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">{{ t('teknisiReports.userRating') }}</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <div class="flex">
                                <svg v-for="star in 5" :key="star" :class="['w-4 h-4', star <= safePerformance.avg_rating ? 'text-amber-400' : 'text-gray-200']" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ safePerformance.avg_rating?.toFixed(1) || '0.0' }}</span>
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
                        <h2 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.mySpecializations') }}</h2>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div v-if="props.specializations.length === 0" class="text-center py-8">
                        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <p class="mt-3 text-sm text-gray-500">{{ t('teknisiReports.noSpecializationsYet') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ t('teknisiReports.handleMoreTicketsToBuildExpertise') }}</p>
                    </div>
                    <div v-for="skill in props.specializations" :key="skill.name" class="group">
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
                        <h2 class="text-base font-semibold text-gray-900">{{ t('teknisiReports.recentFeedback') }}</h2>
                    </div>
                </div>
                <div v-if="props.recentFeedback.length === 0" class="p-8 text-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">{{ t('teknisiReports.noFeedbackYet') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ t('teknisiReports.feedbackFromUsersWillAppearHere') }}</p>
                </div>
                <div v-else class="divide-y divide-gray-50">
                    <div v-for="feedback in props.recentFeedback" :key="feedback.id" class="p-4 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-medium">
                                {{ feedback.user_name?.charAt(0)?.toUpperCase() || 'U' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="font-medium text-gray-900 text-sm truncate">{{ feedback.user_name }}</p>
                                    <div class="flex items-center space-x-1">
                                        <div class="flex">
                                            <svg v-for="star in 5" :key="star" :class="['w-3 h-3', star <= feedback.rating ? 'text-amber-400' : 'text-gray-200']" fill="currentColor" viewBox="0 0 20 20">
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
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';

const { t } = useI18n();

const props = defineProps({
    performanceData: {
        type: Object,
        default: () => ({})
    },
    ticketStats: {
        type: Object,
        default: () => ({})
    },
    applicationPerformance: {
        type: Array,
        default: () => []
    },
    categoryPerformance: {
        type: Array,
        default: () => []
    },
    workloadTrends: {
        type: Array,
        default: () => []
    },
    recentActivity: {
        type: Array,
        default: () => []
    },
    achievements: {
        type: Array,
        default: () => []
    },
    specializations: {
        type: Array,
        default: () => []
    },
    recentFeedback: {
        type: Array,
        default: () => []
    },
    performance: {
        type: Object,
        default: () => ({})
    },
    dateRange: {
        type: Object,
        default: () => ({
            start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0]
        })
    }
});

const selectedPeriod = ref('month');
const showExportMenu = ref(false);
const isLoading = ref(false);
const dateRange = ref({
    start: props.dateRange?.start_date || new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    end: props.dateRange?.end_date || new Date().toISOString().split('T')[0]
});

// Safe performance data with defaults
const safePerformanceData = computed(() => ({
    totalResolved: props.performanceData?.totalResolved ?? 0,
    avgResolutionTime: props.performanceData?.avgResolutionTime ?? '0h',
    satisfactionRate: props.performanceData?.satisfactionRate ?? 0,
    productivityScore: props.performanceData?.productivityScore ?? 0
}));

// Safe performance metrics with defaults
const safePerformance = computed(() => ({
    resolution_rate: props.performance?.resolution_rate ?? 0,
    avg_response_time: props.performance?.avg_response_time ?? 0,
    avg_resolution_time: props.performance?.avg_resolution_time ?? 0,
    tickets_this_week: props.performance?.tickets_this_week ?? 0,
    avg_rating: props.performance?.avg_rating ?? 0
}));

// Check if we have chart data
const hasChartData = computed(() => {
    const trends = props.workloadTrends || [];
    return trends.length > 0 && trends.some(t => (t.tickets_assigned || 0) > 0 || (t.tickets_resolved || 0) > 0);
});

// Check if we have category data
const hasCategoryData = computed(() => {
    const categories = props.ticketStats?.by_category || {};
    const validCategories = Object.keys(categories).filter(k => k && k !== 'null' && k !== 'undefined');
    return validCategories.length > 0 && validCategories.some(k => categories[k] > 0);
});

// Check if we have priority data
const hasPriorityData = computed(() => {
    const priorities = props.ticketStats?.by_priority || {};
    return Object.values(priorities).some(v => v > 0);
});

// Watch for props changes to update local date range
watch(() => props.dateRange, (newRange) => {
    if (newRange?.start_date) dateRange.value.start = newRange.start_date;
    if (newRange?.end_date) dateRange.value.end = newRange.end_date;
}, { immediate: true });

// Close export menu when clicking outside
const handleClickOutside = (e) => {
    if (showExportMenu.value && !e.target.closest('.relative')) {
        showExportMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Format date for chart labels
const formatDateLabel = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

// Chart data - using actual props data
const performanceChartData = computed(() => {
    const trends = props.workloadTrends || [];
    
    if (trends.length === 0) {
        return {
            labels: ['No Data'],
            datasets: [{
                label: 'Tickets Resolved',
                data: [0],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };
    }

    return {
        labels: trends.map(t => formatDateLabel(t.date)),
        datasets: [
            {
                label: 'Tickets Assigned',
                data: trends.map(t => t.tickets_assigned || 0),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: false
            },
            {
                label: 'Tickets Resolved',
                data: trends.map(t => t.tickets_resolved || 0),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    };
});

const categoryChartData = computed(() => {
    const categories = props.ticketStats?.by_category || {};
    const categoryLabels = Object.keys(categories).filter(k => k && k !== 'null');
    const categoryData = categoryLabels.map(k => categories[k]);

    if (categoryLabels.length === 0) {
        return {
            labels: ['No Data'],
            datasets: [{
                label: 'Tickets by Category',
                data: [0],
                backgroundColor: ['rgba(156, 163, 175, 0.8)']
            }]
        };
    }

    const colors = [
        'rgba(59, 130, 246, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(251, 191, 36, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(20, 184, 166, 0.8)',
        'rgba(245, 158, 11, 0.8)'
    ];

    return {
        labels: categoryLabels,
        datasets: [{
            label: 'Tickets by Category',
            data: categoryData,
            backgroundColor: colors.slice(0, categoryLabels.length)
        }]
    };
});

const priorityChartData = computed(() => {
    const priorities = props.ticketStats?.by_priority || {};
    
    return {
        labels: ['Low', 'Medium', 'High', 'Urgent'],
        datasets: [{
            data: [
                priorities.low || 0,
                priorities.medium || 0,
                priorities.high || 0,
                priorities.urgent || 0
            ],
            backgroundColor: ['#9CA3AF', '#3B82F6', '#F59E0B', '#EF4444']
        }]
    };
});

const applicationChartData = computed(() => {
    const apps = props.applicationPerformance || [];
    
    if (apps.length === 0) {
        return {
            labels: ['No Data'],
            datasets: [{
                label: 'Tickets by Application',
                data: [0],
                backgroundColor: ['rgba(156, 163, 175, 0.8)']
            }]
        };
    }

    return {
        labels: apps.map(a => a.application?.name || 'Unknown'),
        datasets: [{
            label: 'Total Tickets',
            data: apps.map(a => a.total_tickets || 0),
            backgroundColor: 'rgba(99, 102, 241, 0.8)'
        }, {
            label: 'Resolved',
            data: apps.map(a => a.resolved_tickets || 0),
            backgroundColor: 'rgba(16, 185, 129, 0.8)'
        }]
    };
});

const applyPeriodPreset = () => {
    const today = new Date();
    let startDate;
    
    switch (selectedPeriod.value) {
        case 'week':
            startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            break;
        case 'month':
            startDate = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
            break;
        case 'quarter':
            startDate = new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000);
            break;
        case 'year':
            startDate = new Date(today.getTime() - 365 * 24 * 60 * 60 * 1000);
            break;
        default:
            return;
    }
    
    dateRange.value.start = startDate.toISOString().split('T')[0];
    dateRange.value.end = today.toISOString().split('T')[0];
    loadReports();
};

const loadReports = () => {
    isLoading.value = true;
    router.get(route('teknisi.reports.index'), {
        start_date: dateRange.value.start,
        end_date: dateRange.value.end
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const exportReport = (format) => {
    showExportMenu.value = false;
    
    const params = new URLSearchParams({
        period: selectedPeriod.value || 'month',
        format: format,
        start_date: dateRange.value.start,
        end_date: dateRange.value.end
    });
    
    window.open(route('teknisi.reports.export') + '?' + params.toString(), '_blank');
};

// Get achievement icon
const getAchievementIcon = (type) => {
    const icons = {
        resolution_milestone: '🏆',
        performance: '📈',
        satisfaction: '❤️',
        expertise: '🎓',
        default: '⭐'
    };
    return icons[type] || icons.default;
};
</script>

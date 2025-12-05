<template>
    <AppLayout role="admin-aplikasi">
        <template #header>
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 animate-slideInDown"
            >
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
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
                                    d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1
                                class="text-3xl sm:text-4xl font-bold text-gray-900"
                            >
                                {{ $t("dashboard.adminAplikasi.title") }}
                            </h1>
                            <p
                                class="text-gray-600 text-sm sm:text-base animate-fadeInUp animation-delay-200"
                            >
                                {{ $t("dashboard.adminAplikasi.description") }}
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
                            <span>{{
                                $t("dashboard.adminAplikasi.online", {
                                    active: stats.active_applications || 0,
                                    total: stats.total_applications || 0,
                                })
                            }}</span>
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
                                new Date().toLocaleString(t("time.locale"), {
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
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                />
                            </svg>
                            <span>{{
                                $t("dashboard.adminAplikasi.categoriesCount", {
                                    count: stats.total_categories || 0,
                                })
                            }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin-aplikasi/categories"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
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
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                        <span class="hidden sm:inline">{{
                            $t("dashboard.adminAplikasi.categories")
                        }}</span>
                        <span class="sm:hidden">🏷️</span>
                    </Link>
                    <Link
                        href="/admin-aplikasi/applications"
                        class="bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
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
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                        <span class="hidden sm:inline">{{
                            $t("dashboard.adminAplikasi.applications")
                        }}</span>
                        <span class="sm:hidden">➕</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Application Overview Stats -->
        <div
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6"
        >
            <!-- Total Applications -->
            <div
                class="stat-card bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 animate-slideInUp relative overflow-hidden"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg"
                    >
                        <svg
                            class="w-6 h-6 text-white drop-shadow-lg"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex items-center bg-white/20 backdrop-blur-sm rounded-full px-2.5 py-1"
                        v-if="stats.applications_trend !== 0"
                    >
                        <svg
                            class="w-3.5 h-3.5 mr-1"
                            :class="
                                stats.applications_trend > 0
                                    ? 'text-green-200'
                                    : 'text-red-200'
                            "
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                :d="
                                    stats.applications_trend > 0
                                        ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
                                        : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'
                                "
                            />
                        </svg>
                        <span
                            class="text-xs font-bold"
                            :class="
                                stats.applications_trend > 0
                                    ? 'text-white'
                                    : 'text-white'
                            "
                        >
                            {{ Math.abs(stats.applications_trend) }}%
                        </span>
                    </div>
                </div>
                <h3
                    class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90"
                >
                    {{ $t("dashboard.adminAplikasi.totalApplications") }}
                </h3>
                <AnimatedNumber
                    :target="stats.total_applications"
                    :duration="2000"
                    :delay="200"
                    class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg"
                />
            </div>

            <!-- Active Applications -->
            <div
                class="stat-card bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 animate-slideInUp animation-delay-200 relative overflow-hidden"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg"
                    >
                        <svg
                            class="w-6 h-6 text-white drop-shadow-lg"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                    </div>
                    <div
                        class="w-2.5 h-2.5 bg-white rounded-full animate-pulse shadow-lg"
                    ></div>
                </div>
                <h3
                    class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90"
                >
                    {{ $t("dashboard.adminAplikasi.activeApplications") }}
                </h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">
                    <AnimatedNumber
                        :target="stats.active_applications"
                        :duration="2000"
                        :delay="400"
                    />
                </div>
                <p class="text-white text-xs mt-2 font-medium opacity-90">
                    {{ $t("dashboard.adminAplikasi.currentlyAvailable") }}
                </p>
            </div>

            <!-- Categories -->
            <div
                class="stat-card bg-gradient-to-br from-purple-600 to-pink-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 animate-slideInUp animation-delay-400 relative overflow-hidden"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg"
                    >
                        <svg
                            class="w-6 h-6 text-white drop-shadow-lg"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex items-center space-x-1 bg-white/20 backdrop-blur-sm rounded-full px-2 py-1"
                    >
                        <div
                            class="w-1.5 h-1.5 bg-white rounded-full animate-bounce animation-delay-200"
                        ></div>
                        <div
                            class="w-1.5 h-1.5 bg-white rounded-full animate-bounce animation-delay-400"
                        ></div>
                        <div
                            class="w-1.5 h-1.5 bg-white rounded-full animate-bounce animation-delay-600"
                        ></div>
                    </div>
                </div>
                <h3
                    class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90"
                >
                    {{ $t("dashboard.adminAplikasi.categories") }}
                </h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">
                    <AnimatedNumber
                        :target="stats.total_categories"
                        :duration="2000"
                        :delay="600"
                    />
                </div>
                <p class="text-white text-xs mt-2 font-medium opacity-90">
                    {{ $t("dashboard.adminAplikasi.problemCategories") }}
                </p>
            </div>

            <!-- Tickets This Month -->
            <div
                class="stat-card bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 animate-slideInUp animation-delay-600 relative overflow-hidden"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg"
                    >
                        <svg
                            class="w-6 h-6 text-white drop-shadow-lg"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex items-center bg-white/20 backdrop-blur-sm rounded-full px-2.5 py-1"
                        v-if="stats.tickets_trend !== 0"
                    >
                        <svg
                            class="w-3.5 h-3.5 mr-1"
                            :class="
                                stats.tickets_trend > 0
                                    ? 'text-green-200'
                                    : 'text-red-200'
                            "
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                :d="
                                    stats.tickets_trend > 0
                                        ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
                                        : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'
                                "
                            />
                        </svg>
                        <span
                            class="text-xs font-bold"
                            :class="
                                stats.tickets_trend > 0
                                    ? 'text-white'
                                    : 'text-white'
                            "
                        >
                            {{ Math.abs(stats.tickets_trend) }}%
                        </span>
                    </div>
                </div>
                <h3
                    class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90"
                >
                    {{ $t("dashboard.adminAplikasi.ticketsThisMonth") }}
                </h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">
                    <AnimatedNumber
                        :target="stats.tickets_this_month"
                        :duration="2000"
                        :delay="800"
                    />
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <!-- Left Column - Applications Management -->
            <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                <!-- Applications List -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 glass"
                >
                    <div class="p-6 border-b border-gray-100/50">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5 text-white"
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
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">
                                        {{ $t("nav.applications") }}
                                    </h2>
                                    <p class="text-sm text-gray-500">
                                        {{
                                            $t(
                                                "dashboard.adminAplikasi.manageApplicationCatalog"
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3"
                            >
                                <div class="relative">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        :placeholder="
                                            $t(
                                                'dashboard.adminAplikasi.searchApplicationsPlaceholder'
                                            )
                                        "
                                        class="w-full sm:w-64 pl-10 pr-4 py-2.5 bg-gray-50/50 border border-gray-200/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent focus:bg-white transition-all duration-200"
                                    />
                                    <svg
                                        class="absolute left-3 top-3 w-4 h-4 text-gray-400"
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
                                <Link
                                    href="/admin-aplikasi/applications"
                                    class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 group"
                                >
                                    {{ $t("action.viewAll") }}
                                    <svg
                                        class="w-4 h-4 ml-2 group-hover:translate-x-0.5 transition-transform duration-200"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3"
                                        />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="filteredApplications.length === 0"
                        class="p-12 text-center"
                    >
                        <div
                            class="w-20 h-20 bg-gray-100/50 rounded-2xl flex items-center justify-center mx-auto mb-4"
                        >
                            <svg
                                class="w-10 h-10 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{
                                $t(
                                    "dashboard.adminAplikasi.noApplicationsFound"
                                )
                            }}
                        </h3>
                        <p class="text-gray-500 mb-4">
                            {{
                                $t(
                                    "dashboard.adminAplikasi.createFirstApplication"
                                )
                            }}
                        </p>
                        <Link
                            href="/admin-aplikasi/applications/create"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
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
                            {{
                                $t("dashboard.adminAplikasi.createApplication")
                            }}
                        </Link>
                    </div>

                    <div v-else class="divide-y divide-gray-100/50">
                        <div
                            v-for="(app, index) in filteredApplications"
                            :key="app.id"
                            class="p-6 hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/50 transition-all duration-300 group animate-slideInUp"
                            :style="`animation-delay: ${index * 100}ms`"
                        >
                            <div
                                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300"
                                    >
                                        <span
                                            class="text-white font-bold text-lg"
                                            >{{ app.kode_aplikasi }}</span
                                        >
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 gap-2 mb-2"
                                        >
                                            <h3
                                                class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200"
                                            >
                                                {{ app.nama_aplikasi }}
                                            </h3>
                                            <span
                                                :class="[
                                                    'px-3 py-1 text-xs font-medium rounded-full inline-flex items-center',
                                                    getStatusColor(app.status),
                                                ]"
                                            >
                                                <div
                                                    class="w-1.5 h-1.5 rounded-full mr-1.5"
                                                    :class="
                                                        app.status === 'aktif'
                                                            ? 'bg-green-400'
                                                            : app.status ===
                                                              'maintenance'
                                                            ? 'bg-yellow-400'
                                                            : 'bg-red-400'
                                                    "
                                                ></div>
                                                {{ app.status_label }}
                                            </span>
                                        </div>
                                        <p
                                            class="text-sm text-gray-600 mb-3 line-clamp-2"
                                        >
                                            {{ app.deskripsi }}
                                        </p>
                                        <div
                                            class="flex flex-wrap items-center gap-4 text-sm"
                                        >
                                            <div
                                                class="flex items-center text-gray-500"
                                            >
                                                <svg
                                                    class="w-4 h-4 mr-1.5 text-gray-400"
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
                                                {{ app.kategori_label }}
                                            </div>
                                            <div
                                                class="flex items-center text-gray-500"
                                            >
                                                <svg
                                                    class="w-4 h-4 mr-1.5 text-gray-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-1M3 13l3 3m-3 4l3-3m-3-4l3 3"
                                                    />
                                                </svg>
                                                {{ app.categories_count }}
                                                {{ $t("common.categories") }}
                                            </div>
                                            <div
                                                class="flex items-center text-gray-500"
                                            >
                                                <svg
                                                    class="w-4 h-4 mr-1.5 text-gray-400"
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
                                                {{ app.tickets_count }}
                                                {{ $t("common.tickets") }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center space-x-2 ml-0 lg:ml-4"
                                >
                                    <Link
                                        :href="`/admin-aplikasi/applications/${app.id}/edit`"
                                        class="group/btn p-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 hover:text-blue-700 rounded-xl transition-all duration-200 hover:scale-110"
                                        :title="
                                            $t(
                                                'dashboard.adminAplikasi.editApplication'
                                            )
                                        "
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
                                    </Link>
                                    <button
                                        @click="viewApplicationStats(app.id)"
                                        class="group/btn p-2.5 bg-green-50 hover:bg-green-100 text-green-600 hover:text-green-700 rounded-xl transition-all duration-200 hover:scale-110"
                                        :title="
                                            $t(
                                                'dashboard.adminAplikasi.viewStatistics'
                                            )
                                        "
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
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Management -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 glass"
                >
                    <div class="p-6 border-b border-gray-100/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5 text-white"
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
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">
                                        {{
                                            $t(
                                                "dashboard.adminAplikasi.problemCategories"
                                            )
                                        }}
                                    </h2>
                                    <p class="text-sm text-gray-500">
                                        {{
                                            $t(
                                                "dashboard.adminAplikasi.manageProblemTypes"
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="showAddCategoryModal = true"
                                class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-5 py-2.5 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center group"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                {{ $t("dashboard.adminAplikasi.addCategory") }}
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="(category, index) in recentCategories"
                                :key="category.id"
                                class="bg-gradient-to-br from-purple-50/50 to-pink-50/50 border border-purple-200/50 rounded-xl p-5 hover:from-purple-100/50 hover:to-pink-100/50 hover:border-purple-300/50 hover:shadow-lg transition-all duration-300 group animate-slideInUp"
                                :style="`animation-delay: ${index * 150}ms`"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex items-center space-x-2 mb-2"
                                        >
                                            <div
                                                class="w-2 h-2 bg-purple-500 rounded-full"
                                            ></div>
                                            <h4
                                                class="font-semibold text-gray-900 group-hover:text-purple-700 transition-colors duration-200"
                                            >
                                                {{ category.nama_kategori }}
                                            </h4>
                                        </div>
                                        <p
                                            class="text-sm text-gray-600 mb-3 line-clamp-2"
                                        >
                                            {{ category.deskripsi }}
                                        </p>
                                        <div
                                            class="flex flex-wrap items-center gap-3 text-xs"
                                        >
                                            <div
                                                class="flex items-center text-gray-500 bg-white/50 px-2 py-1 rounded-lg"
                                            >
                                                <svg
                                                    class="w-3 h-3 mr-1.5 text-purple-500"
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
                                                {{
                                                    category.aplikasi
                                                        .nama_aplikasi
                                                }}
                                            </div>
                                            <div
                                                class="flex items-center text-gray-500 bg-white/50 px-2 py-1 rounded-lg"
                                            >
                                                <svg
                                                    class="w-3 h-3 mr-1.5 text-indigo-500"
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
                                                {{ category.tickets_count }}
                                                {{ $t("common.tickets") }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center space-x-1 ml-3"
                                    >
                                        <button
                                            @click="editCategory(category)"
                                            class="p-2 bg-purple-100 hover:bg-purple-200 text-purple-600 hover:text-purple-700 rounded-lg transition-all duration-200 hover:scale-110"
                                        >
                                            <svg
                                                class="w-4 h-4"
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
                                            @click="deleteCategory(category.id)"
                                            class="p-2 bg-red-100 hover:bg-red-200 text-red-600 hover:text-red-700 rounded-lg transition-all duration-200 hover:scale-110"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="recentCategories.length === 0"
                            class="text-center py-8"
                        >
                            <div
                                class="w-16 h-16 bg-purple-100/50 rounded-2xl flex items-center justify-center mx-auto mb-4"
                            >
                                <svg
                                    class="w-8 h-8 text-purple-400"
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
                            </div>
                            <h3
                                class="text-lg font-semibold text-gray-900 mb-2"
                            >
                                {{
                                    $t(
                                        "dashboard.adminAplikasi.noCategoriesYet"
                                    )
                                }}
                            </h3>
                            <p class="text-gray-500 mb-4">
                                {{
                                    $t(
                                        "dashboard.adminAplikasi.createFirstCategory"
                                    )
                                }}
                            </p>
                            <button
                                @click="showAddCategoryModal = true"
                                class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
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
                                {{ $t("dashboard.adminAplikasi.addCategory") }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Statistics & Insights -->
            <div class="space-y-4 sm:space-y-6">
                <!-- Application Statistics -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 glass"
                >
                    <div class="p-6 border-b border-gray-100/50">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
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
                                {{
                                    $t(
                                        "dashboard.adminAplikasi.topApplications"
                                    )
                                }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-6 space-y-3">
                        <div
                            v-for="(app, index) in topApplications"
                            :key="app.id"
                            class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl hover:from-blue-100/50 hover:to-indigo-100/50 transition-all duration-300 group animate-slideInUp"
                            :style="`animation-delay: ${index * 100}ms`"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                                >
                                    <span
                                        class="text-white font-bold text-sm"
                                        >{{ app.kode_aplikasi }}</span
                                    >
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="font-semibold text-gray-900 text-sm group-hover:text-blue-600 transition-colors duration-200 truncate"
                                    >
                                        {{ app.nama_aplikasi }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ app.kategori_label }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-200"
                                >
                                    <AnimatedNumber
                                        :target="app.tickets_count"
                                        :duration="1500"
                                        :delay="index * 100"
                                    />
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ $t("dashboard.adminAplikasi.tickets") }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teknisi Assignment -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 glass"
                >
                    <div class="p-6 border-b border-gray-100/50">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
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
                            <h2 class="text-lg font-bold text-gray-900">
                                {{ $t("dashboard.adminAplikasi.teknisiTeam") }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-6 space-y-3">
                        <div
                            v-for="(teknisi, index) in teknisiList"
                            :key="teknisi.id"
                            class="flex items-center justify-between p-3 bg-gradient-to-r from-indigo-50/50 to-purple-50/50 rounded-xl hover:from-indigo-100/50 hover:to-purple-100/50 transition-all duration-300 group animate-slideInUp"
                            :style="`animation-delay: ${index * 120}ms`"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                                >
                                    <span class="text-white font-bold text-sm">
                                        {{
                                            teknisi.nama_lengkap
                                                .split(" ")
                                                .map((n) => n[0])
                                                .join("")
                                                .substring(0, 2)
                                        }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="font-semibold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors duration-200 truncate"
                                    >
                                        {{ teknisi.nama_lengkap }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ teknisi.keahlian }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200"
                                >
                                    <AnimatedNumber
                                        :target="
                                            teknisi.active_tickets_count || 0
                                        "
                                        :duration="1500"
                                        :delay="index * 120"
                                    />
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ $t("dashboard.adminAplikasi.tickets") }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 glass"
                >
                    <div class="p-6 border-b border-gray-100/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-4 h-4 text-white"
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
                                <h2 class="text-lg font-bold text-gray-900">
                                    {{
                                        $t(
                                            "dashboard.adminAplikasi.recentActivity"
                                        )
                                    }}
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div
                        class="divide-y divide-gray-100/50 max-h-80 overflow-y-auto custom-scrollbar"
                    >
                        <div
                            v-for="(activity, index) in recentActivity"
                            :key="activity.id"
                            class="p-4 hover:bg-gradient-to-r hover:from-gray-50/50 hover:to-gray-100/50 transition-all duration-300 animate-slideInUp"
                            :style="`animation-delay: ${index * 80}ms`"
                        >
                            <div class="flex items-start space-x-3">
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full mt-2 flex-shrink-0 animate-pulse',
                                        getActivityColor(activity.type),
                                    ]"
                                ></div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm text-gray-900 line-clamp-2"
                                    >
                                        {{ activity.description }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 mt-1 flex items-center"
                                    >
                                        <svg
                                            class="w-3 h-3 mr-1"
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
                                        {{ activity.formatted_created_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 glass"
                >
                    <div class="p-6 border-b border-gray-100/50">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
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
                                {{ $t("dashboard.adminAplikasi.systemStatus") }}
                            </h2>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div
                            class="flex items-center justify-between p-3 bg-green-50/50 rounded-xl"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                                ></div>
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                    >{{
                                        $t(
                                            "dashboard.adminAplikasi.onlineStatus"
                                        )
                                    }}</span
                                >
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-green-600">{{
                                    systemStatus.online_applications
                                }}</span>
                                <span class="text-sm text-gray-500"
                                    >/
                                    {{ systemStatus.total_applications }}</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between p-3 bg-yellow-50/50 rounded-xl"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"
                                ></div>
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                    >{{
                                        $t(
                                            "dashboard.adminAplikasi.maintenanceStatus"
                                        )
                                    }}</span
                                >
                            </div>
                            <span class="font-bold text-yellow-600">{{
                                systemStatus.maintenance_applications
                            }}</span>
                        </div>

                        <div
                            class="flex items-center justify-between p-3 bg-red-50/50 rounded-xl"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-2 h-2 bg-red-500 rounded-full animate-pulse"
                                ></div>
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                    >{{
                                        $t(
                                            "dashboard.adminAplikasi.deprecatedStatus"
                                        )
                                    }}</span
                                >
                            </div>
                            <span class="font-bold text-red-600">{{
                                systemStatus.deprecated_applications
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Category Modal -->
        <Modal
            v-model:show="showAddCategoryModal"
            :title="$t('dashboard.adminAplikasi.addNewCategory')"
            size="md"
        >
            <form @submit.prevent="addCategory">
                <div class="space-y-5">
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2 flex items-center"
                        >
                            <div
                                class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-600 rounded flex items-center justify-center mr-2"
                            >
                                <svg
                                    class="w-3 h-3 text-white"
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
                            {{ $t("dashboard.adminAplikasi.applicationLabel") }}
                        </label>
                        <select
                            v-model="newCategory.aplikasi_id"
                            required
                            class="w-full border-2 border-gray-200/50 bg-gray-50/50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:bg-white transition-all duration-200"
                        >
                            <option value="">
                                {{
                                    $t(
                                        "dashboard.adminAplikasi.selectApplication"
                                    )
                                }}
                            </option>
                            <option
                                v-for="app in props.applications"
                                :key="app.id"
                                :value="app.id"
                            >
                                {{ app.nama_aplikasi }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2 flex items-center"
                        >
                            <div
                                class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-600 rounded flex items-center justify-center mr-2"
                            >
                                <svg
                                    class="w-3 h-3 text-white"
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
                            </div>
                            {{
                                $t("dashboard.adminAplikasi.categoryNameLabel")
                            }}
                        </label>
                        <input
                            v-model="newCategory.nama_kategori"
                            type="text"
                            required
                            class="w-full border-2 border-gray-200/50 bg-gray-50/50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:bg-white transition-all duration-200"
                            :placeholder="
                                $t(
                                    'dashboard.adminAplikasi.enterCategoryNamePlaceholder'
                                )
                            "
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2 flex items-center"
                        >
                            <div
                                class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-600 rounded flex items-center justify-center mr-2"
                            >
                                <svg
                                    class="w-3 h-3 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            {{ $t("dashboard.adminAplikasi.descriptionLabel") }}
                        </label>
                        <textarea
                            v-model="newCategory.deskripsi"
                            rows="4"
                            class="w-full border-2 border-gray-200/50 bg-gray-50/50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:bg-white transition-all duration-200 resize-none"
                            :placeholder="
                                $t(
                                    'dashboard.adminAplikasi.enterCategoryDescriptionPlaceholder'
                                )
                            "
                        ></textarea>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showAddCategoryModal = false"
                        class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-all duration-200 hover:scale-105"
                    >
                        {{ $t("dashboard.adminAplikasi.cancel") }}
                    </button>
                    <button
                        @click="addCategory"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center"
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
                        {{ $t("dashboard.adminAplikasi.addCategory") }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Edit Category Modal -->
        <Modal
            v-model:show="showEditCategoryModal"
            :title="$t('dashboard.adminAplikasi.editCategoryModalTitle')"
            size="md"
        >
            <form @submit.prevent="updateCategory" v-if="editingCategory">
                <div class="space-y-5">
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2 flex items-center"
                        >
                            <div
                                class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-600 rounded flex items-center justify-center mr-2"
                            >
                                <svg
                                    class="w-3 h-3 text-white"
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
                            {{ $t("dashboard.adminAplikasi.applicationLabel") }}
                        </label>
                        <select
                            v-model="editingCategory.aplikasi_id"
                            required
                            class="w-full border-2 border-gray-200/50 bg-gray-50/50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:bg-white transition-all duration-200"
                        >
                            <option value="">
                                {{
                                    $t(
                                        "dashboard.adminAplikasi.selectApplication"
                                    )
                                }}
                            </option>
                            <option
                                v-for="app in props.applications"
                                :key="app.id"
                                :value="app.id"
                            >
                                {{ app.nama_aplikasi }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2 flex items-center"
                        >
                            <div
                                class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-600 rounded flex items-center justify-center mr-2"
                            >
                                <svg
                                    class="w-3 h-3 text-white"
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
                            </div>
                            {{
                                $t("dashboard.adminAplikasi.categoryNameLabel")
                            }}
                        </label>
                        <input
                            v-model="editingCategory.nama_kategori"
                            type="text"
                            required
                            class="w-full border-2 border-gray-200/50 bg-gray-50/50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:bg-white transition-all duration-200"
                            :placeholder="
                                $t(
                                    'dashboard.adminAplikasi.enterCategoryNamePlaceholder'
                                )
                            "
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2 flex items-center"
                        >
                            <div
                                class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-600 rounded flex items-center justify-center mr-2"
                            >
                                <svg
                                    class="w-3 h-3 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            {{ $t("dashboard.adminAplikasi.descriptionLabel") }}
                        </label>
                        <textarea
                            v-model="editingCategory.deskripsi"
                            rows="4"
                            class="w-full border-2 border-gray-200/50 bg-gray-50/50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:bg-white transition-all duration-200 resize-none"
                            :placeholder="
                                $t(
                                    'dashboard.adminAplikasi.enterCategoryDescriptionPlaceholder'
                                )
                            "
                        ></textarea>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="closeEditModal"
                        class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-all duration-200 hover:scale-105"
                    >
                        {{ $t("dashboard.adminAplikasi.cancel") }}
                    </button>
                    <button
                        @click="updateCategory"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center"
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
                        {{ $t("dashboard.adminAplikasi.updateCategory") }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import Modal from "@/Components/Common/Modal.vue";
import AnimatedNumber from "@/Components/UI/AnimatedNumber.vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_applications: 0,
            active_applications: 0,
            applications_trend: 0,
            total_categories: 0,
            tickets_this_month: 0,
            tickets_trend: 0,
        }),
    },
    applications: {
        type: Array,
        default: () => [],
    },
    recentCategories: {
        type: Array,
        default: () => [],
    },
    topApplications: {
        type: Array,
        default: () => [],
    },
    teknisiList: {
        type: Array,
        default: () => [],
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
    systemStatus: {
        type: Object,
        default: () => ({
            total_applications: 0,
            online_applications: 0,
            maintenance_applications: 0,
            deprecated_applications: 0,
        }),
    },
});

// Initialize real-time dashboard for admin aplikasi
// Real-time functionality has been removed
onMounted(() => {
    // Initialization code if needed
});

// Cleanup on unmount
onUnmounted(() => {
    // Cleanup code if needed
});

const searchQuery = ref("");
const showAddCategoryModal = ref(false);
const showEditCategoryModal = ref(false);
const newCategory = ref({
    aplikasi_id: "",
    nama_kategori: "",
    deskripsi: "",
});
const editingCategory = ref(null);

const filteredApplications = computed(() => {
    if (!searchQuery.value) return props.applications;

    return props.applications.filter(
        (app) =>
            app.nama_aplikasi
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            app.kode_aplikasi
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase())
    );
});

const addCategory = () => {
    router.post("/admin-aplikasi/categories", newCategory.value, {
        onSuccess: () => {
            showAddCategoryModal.value = false;
            newCategory.value = {
                aplikasi_id: "",
                nama_kategori: "",
                deskripsi: "",
            };
        },
    });
};

const editCategory = (category) => {
    editingCategory.value = { ...category };
    showEditCategoryModal.value = true;
};

const updateCategory = () => {
    if (!editingCategory.value) return;
    router.put(
        `/admin-aplikasi/categories/${editingCategory.value.id}`,
        editingCategory.value,
        {
            onSuccess: () => {
                closeEditModal();
            },
        }
    );
};

const closeEditModal = () => {
    showEditCategoryModal.value = false;
    editingCategory.value = null;
};

const deleteCategory = (categoryId) => {
    if (confirm(t("dashboard.adminAplikasi.confirmDeleteCategory"))) {
        router.delete(`/admin-aplikasi/categories/${categoryId}`);
    }
};

const viewApplicationStats = (appId) => {
    router.visit(`/admin-aplikasi/applications/${appId}`);
};

const manageTeknisiApps = (teknisiId) => {
    router.visit(`/admin-aplikasi/teknisi/${teknisiId}/manage`);
};

const getStatusColor = (status) => {
    const colors = {
        aktif: "bg-green-100 text-green-800",
        maintenance: "bg-yellow-100 text-yellow-800",
        deprecated: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getActivityColor = (type) => {
    const colors = {
        application_created: "bg-blue-500",
        category_created: "bg-purple-500",
        teknisi_assigned: "bg-indigo-500",
        application_updated: "bg-green-500",
    };
    return colors[type] || "bg-gray-500";
};
</script>

<style scoped>
/* ==================== Professional Animations ==================== */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes pulse-soft {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes glow {
    0%,
    100% {
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(99, 102, 241, 0.5);
    }
}

/* ==================== Animation Classes ==================== */
.animate-slideInDown {
    animation: slideInDown 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-slideInUp {
    animation: slideInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-fadeInUp {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animate-shimmer {
    animation: shimmer 2s infinite;
    background: linear-gradient(
        to right,
        #f6f7f8 0%,
        #edeef1 20%,
        #f6f7f8 40%,
        #f6f7f8 100%
    );
    background-size: 1000px 100%;
}

.animate-glow {
    animation: glow 2s ease-in-out infinite;
}

/* ==================== Delay Classes ==================== */
.animation-delay-200 {
    animation-delay: 0.2s;
}

.animation-delay-400 {
    animation-delay: 0.4s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-800 {
    animation-delay: 0.8s;
}

/* ==================== Glass Morphism ==================== */
.glass {
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    background-color: rgba(255, 255, 255, 0.75);
}

.glass-dark {
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    background-color: rgba(0, 0, 0, 0.3);
}

/* ==================== Professional Scrollbar ==================== */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #cbd5e1 0%, #94a3b8 100%);
    border-radius: 10px;
    border: 2px solid #f1f5f9;
    transition: background 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
}

.custom-scrollbar::-webkit-scrollbar-corner {
    background: transparent;
}

/* ==================== Professional Card Shadows ==================== */
.shadow-professional {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.shadow-professional-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
        0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.shadow-professional-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.shadow-inner-soft {
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
}

/* ==================== Hover Effects ==================== */
.hover-lift {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15),
        0 10px 10px -5px rgba(0, 0, 0, 0.08);
}

.hover-scale {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-scale:hover {
    transform: scale(1.05);
}

/* ==================== Stat Card Enhancements ==================== */
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.1) 0%,
        rgba(255, 255, 255, 0) 100%
    );
    pointer-events: none;
}

.stat-card::after {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.1) 0%,
        transparent 70%
    );
    opacity: 0;
    transition: opacity 0.5s ease;
    pointer-events: none;
}

.stat-card:hover::after {
    opacity: 1;
}

/* ==================== Professional Button Styles ==================== */
.btn-professional {
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-professional::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn-professional:hover::before {
    width: 300px;
    height: 300px;
}

/* ==================== Card Hover Glow ==================== */
.card-glow {
    position: relative;
    transition: all 0.3s ease;
}

.card-glow:hover {
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.3),
        0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* ==================== Professional Input ==================== */
.input-professional {
    transition: all 0.2s ease;
}

.input-professional:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* ==================== Badge Styles ==================== */
.badge-modern {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.025em;
    transition: all 0.2s ease;
}

.badge-modern:hover {
    transform: scale(1.05);
}

/* ==================== Professional Focus States ==================== */
.focus-professional:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1),
        0 0 0 1px rgba(99, 102, 241, 0.3);
}

/* ==================== Text Gradient ==================== */
.text-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ==================== Professional Divider ==================== */
.divider-professional {
    height: 1px;
    background: linear-gradient(
        to right,
        transparent 0%,
        #e5e7eb 50%,
        transparent 100%
    );
}

/* ==================== Loading Skeleton ==================== */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 0.5rem;
}

/* ==================== Smooth Transitions ==================== */
* {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* ==================== Professional Gradients ==================== */
.gradient-overlay-blue {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-overlay-green {
    background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
}

.gradient-overlay-purple {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.gradient-overlay-indigo {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

/* ==================== Enhanced Line Clamp ==================== */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    line-clamp: 3;
    overflow: hidden;
}

/* ==================== Professional Spacing ==================== */
.section-spacing {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

@media (min-width: 768px) {
    .section-spacing {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
}

/* ==================== Enhanced Mobile Responsiveness ==================== */
@media (max-width: 640px) {
    .animate-slideInUp,
    .animate-slideInDown,
    .animate-fadeInUp {
        animation: none;
        opacity: 1;
        transform: none;
    }

    .hover-lift:hover,
    .hover-scale:hover {
        transform: none;
    }
}

/* ==================== Print Styles ==================== */
@media print {
    .glass,
    .shadow-professional,
    .shadow-professional-lg,
    .shadow-professional-xl {
        background: white !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
    }
}
</style>

<template>
    <AppLayout role="teknisi">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
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
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                    </div>
                    <div>
                        <h1
                            class="text-2xl sm:text-3xl font-bold text-gray-900"
                        >
                            {{ $t("nav.knowledgeBase") }}
                        </h1>
                        <p class="text-gray-500 text-sm">
                            {{ $t("teknisi.knowledgeBase") }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="showCreateModal = true"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition-all shadow-sm hover:shadow-md flex items-center"
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
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        {{ $t("common.create") }} {{ $t("common.articles") }}
                    </button>
                    <button
                        @click="exportKnowledgeBase"
                        class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:from-green-600 hover:to-emerald-700 transition-all shadow-sm hover:shadow-md flex items-center"
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
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        {{ $t("common.export") }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Stats Cards -->
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8"
        >
            <!-- Total Articles -->
            <div
                class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden"
            >
                <div
                    class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full -mr-12 -mt-12 opacity-50"
                ></div>
                <div
                    class="flex items-start justify-between mb-3 relative z-10"
                >
                    <div
                        class="w-11 h-11 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
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
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        {{ $t("common.management") }}
                        {{ $t("common.articles") }}
                    </h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            {{ stats.total_articles || 0 }}
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $t("teknisi.knowledgeBase") }}
                    </p>
                </div>
            </div>

            <!-- This Month -->
            <div
                class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden"
            >
                <div
                    class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full -mr-12 -mt-12 opacity-50"
                ></div>
                <div
                    class="flex items-start justify-between mb-3 relative z-10"
                >
                    <div
                        class="w-11 h-11 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
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
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        {{ $t("dashboard.adminAplikasi.ticketsThisMonth") }}
                    </h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            {{ stats.articles_this_month || 0 }}
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $t("common.create") }} {{ $t("common.articles") }}
                    </p>
                </div>
            </div>

            <!-- My Published -->
            <div
                class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden"
            >
                <div
                    class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full -mr-12 -mt-12 opacity-50"
                ></div>
                <div
                    class="flex items-start justify-between mb-3 relative z-10"
                >
                    <div
                        class="w-11 h-11 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
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
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        {{ $t("common.management") }}
                        {{ $t("status.published") }}
                    </h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            {{ stats.my_published || 0 }}
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">published articles</p>
                </div>
            </div>

            <!-- My Drafts -->
            <div
                class="bg-white rounded-xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 group relative overflow-hidden"
            >
                <div
                    class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-amber-100 to-orange-100 rounded-full -mr-12 -mt-12 opacity-50"
                ></div>
                <div
                    class="flex items-start justify-between mb-3 relative z-10"
                >
                    <div
                        class="w-11 h-11 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
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
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3
                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        My Drafts
                    </h3>
                    <div class="mt-1 flex items-baseline">
                        <p class="text-3xl font-bold text-gray-900">
                            {{ stats.my_drafts || 0 }}
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">in progress</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div
            class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6"
        >
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Search Knowledge Base</label
                    >
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            @input="debouncedSearch"
                            type="text"
                            placeholder="Search articles, tags, or content..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50 hover:bg-white transition-colors"
                        />
                        <svg
                            class="absolute left-3 top-3 w-5 h-5 text-gray-400"
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
                        <div v-if="loading" class="absolute right-3 top-3">
                            <svg
                                class="animate-spin h-5 w-5 text-indigo-500"
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
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Category</label
                    >
                    <select
                        v-model="filters.category"
                        @change="applyFilters"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        >Status</label
                    >
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Status</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>

            <!-- My Articles Toggle & Advanced Filters -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-4">
                        <!-- My Articles Toggle -->
                        <label class="inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="filters.my_articles"
                                @change="applyFilters"
                                class="sr-only peer"
                            />
                            <div
                                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                            ></div>
                            <span class="ms-3 text-sm font-medium text-gray-700"
                                >My Articles Only</span
                            >
                        </label>
                        <button
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            class="text-indigo-600 hover:text-indigo-800 font-medium text-sm"
                        >
                            {{ showAdvancedFilters ? "Hide" : "Show" }} Advanced
                            Filters
                        </button>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-600">
                            {{ articles.total || 0 }} articles found
                        </span>
                        <button
                            @click="clearFilters"
                            class="text-gray-600 hover:text-gray-800 font-medium text-sm"
                        >
                            Clear All
                        </button>
                    </div>
                </div>

                <div
                    v-show="showAdvancedFilters"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4"
                >
                    <!-- Application Filter -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Application</label
                        >
                        <select
                            v-model="filters.aplikasi_id"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">All Applications</option>
                            <option
                                v-for="app in applications"
                                :key="app.id"
                                :value="app.id"
                            >
                                {{ app.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Priority</label
                        >
                        <select
                            v-model="filters.priority"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">All Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Sort By</label
                        >
                        <select
                            v-model="filters.sort_by"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="updated_at">Last Updated</option>
                            <option value="created_at">Created Date</option>
                            <option value="title">Title</option>
                            <option value="views">Most Viewed</option>
                            <option value="helpful">Most Helpful</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Tags -->
        <div
            v-if="popularTags.length > 0"
            class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6"
        >
            <div class="flex items-center justify-between mb-4">
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
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">
                        Popular Tags
                    </h3>
                </div>
                <span
                    v-if="selectedTags.length > 0"
                    class="text-xs text-indigo-600 font-medium"
                >
                    {{ selectedTags.length }} selected
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tag in popularTags"
                    :key="tag.name"
                    @click="toggleTag(tag.name)"
                    :class="[
                        'px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center space-x-1',
                        selectedTags.includes(tag.name)
                            ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md transform scale-105'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-sm',
                    ]"
                >
                    <span>{{ tag.name }}</span>
                    <span
                        :class="[
                            'text-xs px-1.5 py-0.5 rounded-full',
                            selectedTags.includes(tag.name)
                                ? 'bg-white/20'
                                : 'bg-gray-200',
                        ]"
                        >{{ tag.count }}</span
                    >
                </button>
            </div>
            <div
                v-if="selectedTags.length > 0"
                class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between"
            >
                <span class="text-sm text-gray-500"
                    >Filtering by {{ selectedTags.length }} tag(s)</span
                >
                <button
                    @click="
                        selectedTags = [];
                        applyFilters();
                    "
                    class="text-sm text-red-600 hover:text-red-800 font-medium"
                >
                    Clear Tags
                </button>
            </div>
        </div>

        <!-- Articles Grid/List -->
        <div
            class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
        >
            <div
                class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow-md"
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
                        <h2 class="text-base font-semibold text-gray-900">
                            Knowledge Articles
                        </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div
                            class="flex items-center space-x-1 bg-gray-100 rounded-lg p-1"
                        >
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200 flex items-center space-x-1',
                                    viewMode === 'grid'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-900',
                                ]"
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
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">Grid</span>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200 flex items-center space-x-1',
                                    viewMode === 'list'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-900',
                                ]"
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
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                    />
                                </svg>
                                <span class="hidden sm:inline">List</span>
                            </button>
                        </div>
                        <select
                            v-model="perPage"
                            @change="changePerPage"
                            class="text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50"
                        >
                            <option :value="12">12 per page</option>
                            <option :value="24">24 per page</option>
                            <option :value="48">48 per page</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="p-12 text-center">
                <div
                    class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
                ></div>
                <p class="mt-2 text-gray-500">Loading articles...</p>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="!articles.data || articles.data.length === 0"
                class="p-12 text-center"
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
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    No articles found
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{
                        hasActiveFilters
                            ? "Try adjusting your filters to find more knowledge base articles"
                            : "No resolved tickets with solutions available yet"
                    }}
                </p>
                <div v-if="hasActiveFilters" class="mt-6">
                    <button
                        @click="clearFilters"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="p-6">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div
                        v-for="article in articles.data"
                        :key="article.id"
                        class="bg-gray-50 rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer border border-gray-200"
                        @click="viewArticle(article.id)"
                    >
                        <!-- Article Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-4 h-4 text-purple-600"
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
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getStatusColor(article.status),
                                    ]"
                                >
                                    {{ article.status }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-xs text-gray-500"
                                    >👁️ {{ article.views || 0 }}</span
                                >
                                <span
                                    v-if="article.helpful_count > 0"
                                    class="text-xs text-green-600"
                                    >👍 {{ article.helpful_count }}</span
                                >
                            </div>
                        </div>

                        <!-- Article Title -->
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2"
                        >
                            {{ article.title }}
                        </h3>

                        <!-- Article Excerpt -->
                        <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                            {{ article.excerpt || "No summary available" }}
                        </p>

                        <!-- Article Meta -->
                        <div class="space-y-2 mb-4">
                            <div
                                v-if="article.kategori_masalah"
                                class="flex items-center text-sm text-gray-500"
                            >
                                <svg
                                    class="w-4 h-4 mr-1"
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
                                {{ article.kategori_masalah.name }}
                            </div>
                            <div
                                v-if="article.aplikasi"
                                class="flex items-center text-sm text-gray-500"
                            >
                                <svg
                                    class="w-4 h-4 mr-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
                                    />
                                </svg>
                                {{ article.aplikasi.name }}
                            </div>
                            <div
                                class="flex items-center text-sm text-gray-500"
                            >
                                <svg
                                    class="w-4 h-4 mr-1"
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
                                {{ formatDate(article.updated_at) }}
                            </div>
                        </div>

                        <!-- Tags -->
                        <div
                            v-if="article.tags && article.tags.length > 0"
                            class="mb-4"
                        >
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="tag in article.tags.slice(0, 3)"
                                    :key="tag"
                                    class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs rounded-full"
                                >
                                    {{ tag }}
                                </span>
                                <span
                                    v-if="article.tags.length > 3"
                                    class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
                                >
                                    +{{ article.tags.length - 3 }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="flex items-center justify-between pt-4 border-t border-gray-200"
                        >
                            <button
                                @click.stop="viewArticle(article.id)"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center"
                            >
                                View Details
                                <svg
                                    class="w-4 h-4 ml-1"
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
                            </button>
                            <div
                                v-if="article.can_edit"
                                class="flex items-center space-x-2"
                            >
                                <button
                                    @click.stop="editArticle(article)"
                                    class="text-green-600 hover:text-green-800 text-sm font-medium"
                                >
                                    Edit
                                </button>
                                <button
                                    @click.stop="deleteArticle(article)"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Article
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Category
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Views
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Helpful
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Updated
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="article in articles.data"
                            :key="article.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ article.title }}
                                    </p>
                                    <p
                                        v-if="article.excerpt"
                                        class="text-xs text-gray-500 mt-1"
                                    >
                                        {{ article.excerpt }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    v-if="article.kategori_masalah"
                                    class="text-sm text-gray-900"
                                    >{{ article.kategori_masalah.name }}</span
                                >
                                <span v-else class="text-sm text-gray-400"
                                    >-</span
                                >
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getStatusColor(article.status),
                                    ]"
                                >
                                    {{ article.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ article.views || 0 }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ article.helpful_count || 0 }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ formatDate(article.updated_at) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <button
                                        @click="viewArticle(article.id)"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium text-sm"
                                    >
                                        View
                                    </button>
                                    <button
                                        v-if="article.can_edit"
                                        @click="editArticle(article)"
                                        class="text-green-600 hover:text-green-900 font-medium text-sm"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="article.can_edit"
                                        @click="deleteArticle(article)"
                                        class="text-red-600 hover:text-red-900 font-medium text-sm"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                <SimplePagination
                    :data="articles"
                    label="articles"
                    @page-changed="handlePageChange"
                />
            </div>
        </div>

        <!-- Create/Edit Article Modal -->
        <KnowledgeArticleModal
            :show="showCreateModal || showEditModal"
            :article="editingArticle"
            :categories="categories"
            :applications="applications"
            :mode="showCreateModal ? 'create' : 'edit'"
            @close="closeArticleModal"
            @saved="onArticleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { debounce } from "lodash";
import AppLayout from "@/Layouts/AppLayout.vue";
import SimplePagination from "@/Components/Common/SimplePagination.vue";
import KnowledgeArticleModal from "@/Components/Modals/KnowledgeArticleModal.vue";

const props = defineProps({
    articles: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    applications: {
        type: Array,
        default: () => [],
    },
    popularTags: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const loading = ref(false);
const viewMode = ref("grid");
const perPage = ref(props.articles.per_page || 12);
const showAdvancedFilters = ref(false);
const selectedTags = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingArticle = ref(null);

const filters = ref({
    search: props.filters.search || "",
    category: props.filters.category || "",
    status: props.filters.status || "",
    aplikasi_id: props.filters.aplikasi_id || "",
    priority: props.filters.priority || "",
    sort_by: props.filters.sort_by || "updated_at",
    tags: props.filters.tags || [],
    my_articles: props.filters.my_articles || false,
});

const hasActiveFilters = computed(() => {
    const hasFilter = Object.entries(filters.value).some(([key, value]) => {
        if (key === "sort_by") return false; // sort_by is not considered a filter
        if (typeof value === "boolean") return value === true;
        if (Array.isArray(value)) return value.length > 0;
        return value !== "" && value !== null && value !== undefined;
    });
    return hasFilter || selectedTags.value.length > 0;
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const applyFilters = () => {
    loading.value = true;
    const params = {};

    Object.entries(filters.value).forEach(([key, value]) => {
        if (
            value !== "" &&
            value !== false &&
            value !== null &&
            value !== undefined
        ) {
            if (Array.isArray(value) && value.length > 0) {
                params[key] = value.join(",");
            } else if (typeof value === "boolean") {
                if (value) params[key] = "1";
            } else if (!Array.isArray(value)) {
                params[key] = value;
            }
        }
    });

    // Add selected tags
    if (selectedTags.value.length > 0) {
        params.tags = selectedTags.value.join(",");
    }

    params.per_page = perPage.value;

    router.get(route("teknisi.knowledge-base.index"), params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const clearFilters = () => {
    filters.value = {
        search: "",
        category: "",
        status: "",
        aplikasi_id: "",
        priority: "",
        sort_by: "updated_at",
        tags: [],
        my_articles: false,
    };
    selectedTags.value = [];
    applyFilters();
};

const changePerPage = () => {
    applyFilters();
};

const toggleTag = (tagName) => {
    const index = selectedTags.value.indexOf(tagName);
    if (index > -1) {
        selectedTags.value.splice(index, 1);
    } else {
        selectedTags.value.push(tagName);
    }
    applyFilters();
};

const viewArticle = (articleId) => {
    router.visit(route("teknisi.knowledge-base.show", articleId));
};

const editArticle = (article) => {
    editingArticle.value = article;
    showEditModal.value = true;
};

const deleteArticle = (article) => {
    if (
        confirm(
            `Are you sure you want to delete "${article.title}"? This action cannot be undone.`
        )
    ) {
        router.delete(route("teknisi.knowledge-base.destroy", article.id));
    }
};

const closeArticleModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingArticle.value = null;
};

const onArticleSaved = () => {
    closeArticleModal();
    router.reload();
};

const exportKnowledgeBase = () => {
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
        if (
            value !== "" &&
            value !== false &&
            (!Array.isArray(value) || value.length > 0)
        ) {
            if (Array.isArray(value)) {
                params.set(key, value.join(","));
            } else if (typeof value === "boolean") {
                params.set(key, value ? "1" : "0");
            } else {
                params.set(key, value);
            }
        }
    });

    if (selectedTags.value.length > 0) {
        params.set("tags", selectedTags.value.join(","));
    }

    window.open(
        route("teknisi.knowledge-base.export-all") + "?" + params.toString(),
        "_blank"
    );
};

const handlePageChange = (page) => {
    loading.value = true;
    const params = {};

    Object.entries(filters.value).forEach(([key, value]) => {
        if (
            value !== "" &&
            value !== false &&
            value !== null &&
            value !== undefined
        ) {
            if (Array.isArray(value) && value.length > 0) {
                params[key] = value.join(",");
            } else if (typeof value === "boolean") {
                if (value) params[key] = "1";
            } else if (!Array.isArray(value)) {
                params[key] = value;
            }
        }
    });

    if (selectedTags.value.length > 0) {
        params.tags = selectedTags.value.join(",");
    }

    params.page = page;
    params.per_page = perPage.value;

    router.get(route("teknisi.knowledge-base.index"), params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        published: "bg-green-100 text-green-800",
        draft: "bg-yellow-100 text-yellow-800",
        archived: "bg-gray-100 text-gray-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

onMounted(() => {
    // Initialize selected tags from filters
    if (props.filters.tags) {
        selectedTags.value = Array.isArray(props.filters.tags)
            ? props.filters.tags
            : props.filters.tags.split(",");
    }
});
</script>

<style scoped>
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
</style>

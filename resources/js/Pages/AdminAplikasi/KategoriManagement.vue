<template>
    <AppLayout role="admin-aplikasi">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">
                        {{ t('nav.categories') }} {{ t('common.management') }}
                    </h1>
                    <p class="text-gray-600 mt-2 text-sm">{{ t('modal.categoryModal.createDescription') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 bg-white rounded-xl shadow-md px-4 py-2 border border-purple-100">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <select
                            v-model="currentApplicationId"
                            @change="loadCategories"
                            class="text-sm font-medium text-gray-700 border-none focus:ring-0 cursor-pointer bg-transparent"
                        >
                            <option value="">{{ t('modal.categoryModal.selectApplication') }}</option>
                            <option v-for="app in applications" :key="app.id" :value="app.id">
                                {{ app.name }}
                            </option>
                        </select>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        :disabled="!currentApplicationId"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 group"
                    >
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        {{ t('common.create') }}
                    </button>
                    <button
                        @click="exportCategories"
                        :disabled="!currentApplicationId"
                        class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ t('common.export') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Application Info Card -->
        <div v-if="selectedApplication" class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl shadow-xl p-6 mb-6 border border-purple-100 backdrop-blur-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ selectedApplication.name }}</h2>
                        <p class="text-gray-600 mt-1 text-sm">{{ selectedApplication.description }}</p>
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <div class="flex items-center gap-1.5 bg-white/70 backdrop-blur-sm rounded-lg px-3 py-1.5 shadow-sm">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ selectedApplication.category_count || 0 }} {{ t('nav.categories') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 bg-white/70 backdrop-blur-sm rounded-lg px-3 py-1.5 shadow-sm">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ selectedApplication.ticket_count || 0 }} {{ t('ticket.totalTickets') }}
                                </span>
                            </div>
                            <span :class="['px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm', getStatusColor(selectedApplication.status)]">
                                {{ selectedApplication.status?.toUpperCase() }}
                            </span>
                        </div>
                    </div>
                </div>
                <button
                    @click="manageApplication"
                    class="text-purple-600 hover:text-purple-800 font-semibold text-sm flex items-center gap-2 bg-white rounded-lg px-4 py-2 shadow-md hover:shadow-lg transition-all duration-300 group"
                >
                    <span>Manage Application</span>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div v-if="selectedApplication" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <!-- Total Categories -->
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90">Total Categories</h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">{{ stats.total_categories || 0 }}</div>
            </div>

            <!-- Active Categories -->
            <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="w-2.5 h-2.5 bg-white rounded-full animate-pulse shadow-lg"></div>
                </div>
                <h3 class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90">Active Categories</h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">{{ stats.active_categories || 0 }}</div>
            </div>

            <!-- Total Tickets -->
            <div class="bg-gradient-to-br from-yellow-600 to-orange-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90">Total Tickets</h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">{{ stats.total_tickets || 0 }}</div>
            </div>

            <!-- Open Tickets -->
            <div class="bg-gradient-to-br from-red-600 to-rose-700 rounded-2xl p-5 text-white shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-white/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-white text-xs font-semibold mb-2 uppercase tracking-wide opacity-90">Open Tickets</h3>
                <div class="text-3xl sm:text-4xl font-extrabold drop-shadow-lg">{{ stats.open_tickets || 0 }}</div>
            </div>
        </div>

        <!-- No Application Selected -->
        <div v-if="!selectedApplication" class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl shadow-xl p-12 sm:p-16 text-center border border-purple-100">
            <div class="mx-auto w-24 h-24 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center shadow-2xl mb-6">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Application Selected</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">Please select an application from the dropdown above to view and manage its problem categories</p>
            <div class="inline-flex items-center gap-2 text-purple-600 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                <span>Select an application to get started</span>
            </div>
        </div>

        <!-- Categories Management (when application is selected) -->
        <div v-else class="space-y-6">
            <!-- Search and Filters -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-purple-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Search & Filters</h3>
                        <p class="text-xs text-gray-500">Find and filter categories</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Search Categories</label>
                        <div class="relative group">
                            <input
                                v-model="filters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Type to search..."
                                class="w-full pl-11 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 group-hover:border-purple-300"
                            >
                            <svg class="absolute left-3.5 top-3 w-5 h-5 text-gray-400 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <div v-if="filters.search" class="absolute right-3 top-3">
                                <button @click="filters.search = ''; applyFilters()" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ t('ticket.status') }}
                            </span>
                        </label>
                        <select
                            v-model="filters.status"
                            @change="applyFilters"
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 hover:border-purple-300 cursor-pointer"
                        >
                            <option value="">{{ t('activityLog.allEntities') }}</option>
                            <option value="active">{{ t('status.active') }}</option>
                            <option value="inactive">{{ t('status.inactive') }}</option>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                </svg>
                                {{ t('common.sortBy') }}
                            </span>
                        </label>
                        <select
                            v-model="filters.sort_by"
                            @change="applyFilters"
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 hover:border-purple-300 cursor-pointer"
                        >
                            <option value="name">{{ t('common.name') }}</option>
                            <option value="ticket_count">{{ t('common.ticketCount') }}</option>
                            <option value="created_at">{{ t('time.createdDate') }}</option>
                            <option value="status">{{ t('ticket.status') }}</option>
                        </select>
                    </div>
                </div>
                
                <!-- Clear Filters Button -->
                <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
                    <button
                        @click="clearFilters"
                        class="text-sm text-purple-600 hover:text-purple-800 font-semibold flex items-center gap-2 bg-purple-50 hover:bg-purple-100 px-4 py-2 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Clear All Filters
                    </button>
                </div>
            </div>

            <!-- Categories List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Categories ({{ categories.total }})
                        </h2>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Select All</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="p-12 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-500">Loading categories...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="categories.data.length === 0" class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('common.noData') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating your first category' }}
                    </p>
                    <div class="mt-6">
                        <button
                            v-if="!hasActiveFilters"
                            @click="showCreateModal = true"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition"
                        >
                            Create New Category
                        </button>
                        <button
                            v-else
                            @click="clearFilters"
                            class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="selectedCategories.length > 0" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 m-6">
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-800 font-medium">
                            {{ selectedCategories.length }} categories selected
                        </span>
                        <div class="flex items-center space-x-3">
                            <select
                                v-model="bulkAction"
                                class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Select Action</option>
                                <option value="activate">{{ t('modal.categoryModal.activate') }} {{ t('nav.categories') }}</option>
                                <option value="deactivate">{{ t('modal.categoryModal.deactivate') }} {{ t('nav.categories') }}</option>
                                <option value="delete">{{ t('common.delete') }} {{ t('nav.categories') }}</option>
                            </select>
                            <button
                                @click="executeBulkAction"
                                :disabled="!bulkAction"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Execute Action
                            </button>
                            <button
                                @click="clearSelection"
                                class="text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('modal.categoryModal.categoryName') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('common.description') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('ticket.priority') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('ticket.status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('nav.tickets') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('common.created') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ t('action.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="category in categories.data"
                                :key="category.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        v-model="selectedCategories"
                                        :value="category.id"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ category.name }}</p>
                                            <p v-if="category.code" class="text-xs text-gray-500">Code: {{ category.code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <p class="text-sm text-gray-900 truncate">{{ category.description }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(category.default_priority)]">
                                        {{ t(`priority.${category.default_priority}`) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div :class="['w-2 h-2 rounded-full', category.status === 'active' ? 'bg-green-500' : 'bg-gray-500']"></div>
                                        <span class="text-sm text-gray-900">{{ t(`status.${category.status}`) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <p>{{ category.ticket_count || 0 }} total</p>
                                        <p v-if="category.open_tickets > 0" class="text-xs text-yellow-600">
                                            {{ category.open_tickets }} open
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ formatDate(category.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="viewCategory(category.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="View Details"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="editCategory(category)"
                                            class="text-green-600 hover:text-green-900"
                                            :title="t('common.edit')"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="toggleCategoryStatus(category)"
                                            :class="category.status === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                                            :title="category.status === 'active' ? t('modal.categoryModal.deactivate') : t('modal.categoryModal.activate')"
                                        >
                                            <svg v-if="category.status === 'active'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteCategory(category)"
                                            class="text-red-600 hover:text-red-900"
                                            :title="t('common.delete')"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                    :data="categories"
                    label="kategori"
                    @page-changed="handlePageChange"
                />
            </div>
        </div>

        <!-- Create/Edit Category Modal -->
        <AdminAplikasiCategoryModal
            v-if="showCreateModal || showEditModal"
            :category="editingCategory"
            :applications="applications"
            :application-id="currentApplicationId"
            :mode="showCreateModal ? 'create' : 'edit'"
            @close="closeCategoryModal"
            @saved="onCategorySaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n'
import { route } from 'ziggy-js';
import { debounce } from 'lodash';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import SimplePagination from '@/Components/Common/SimplePagination.vue';
import AdminAplikasiCategoryModal from '@/Components/Modals/AdminAplikasiCategoryModal.vue';

const { t } = useI18n()

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_categories: 0,
            active_categories: 0,
            total_tickets: 0,
            open_tickets: 0,
        }),
    },
    selectedApplicationId: {
        type: [String, Number],
        default: null,
    },
    selectedApplication: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const loading = ref(false);
const currentApplicationId = ref(props.selectedApplicationId);
const selectedCategories = ref([]);
const bulkAction = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCategory = ref(null);

const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    sort_by: props.filters.sort_by || 'name',
});

const selectedApplication = computed(() => {
    // Use backend-provided selectedApplication if available, otherwise find from applications list
    if (props.selectedApplication) {
        return props.selectedApplication;
    }
    return props.applications.find(app => app.id == currentApplicationId.value);
});

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const allSelected = computed(() => {
    return props.categories.data.length > 0 && selectedCategories.value.length === props.categories.data.length;
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const loadCategories = () => {
    if (!currentApplicationId.value) {
        // Clear categories when no application is selected
        router.get(route('admin-aplikasi.categories.index'), {}, {
            preserveState: false,
            preserveScroll: true,
        });
        return;
    }

    // Reset filters when changing application
    filters.value = {
        search: '',
        status: '',
        sort_by: 'name',
    };
    selectedCategories.value = [];

    router.get(route('admin-aplikasi.categories.index'), {
        aplikasi_id: currentApplicationId.value,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    if (!currentApplicationId.value) return;

    const data = { aplikasi_id: currentApplicationId.value };
    
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            data[key] = value;
        }
    });

    router.get(route('admin-aplikasi.categories.index'), data, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = {
        search: '',
        status: '',
        sort_by: 'name',
    };
    applyFilters();
};

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedCategories.value = [];
    } else {
        selectedCategories.value = props.categories.data.map(category => category.id);
    }
};

const clearSelection = () => {
    selectedCategories.value = [];
    bulkAction.value = '';
};

const executeBulkAction = () => {
    if (selectedCategories.value.length === 0 || !bulkAction.value) return;

    const data = {
        category_ids: selectedCategories.value,
        action: bulkAction.value,
        aplikasi_id: currentApplicationId.value,
    };

    if (bulkAction.value === 'delete') {
        if (confirm(`Are you sure you want to delete ${selectedCategories.value.length} categories? This action cannot be undone.`)) {
            router.post(route('admin-aplikasi.categories.bulk-action'), data, {
                onSuccess: () => {
                    clearSelection();
                    router.reload();
                },
            });
        }
    } else {
        router.post(route('admin-aplikasi.categories.bulk-action'), data, {
            onSuccess: () => {
                clearSelection();
                router.reload();
            },
        });
    }
};

const viewCategory = (categoryId) => {
    router.visit(route('admin-aplikasi.categories.show', categoryId));
};

const editCategory = (category) => {
    editingCategory.value = category;
    showEditModal.value = true;
};

const toggleCategoryStatus = (category) => {
    const newStatus = category.status === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';

    if (confirm(`Are you sure you want to ${action} this category?`)) {
        router.post(route('admin-aplikasi.categories.update-status', category.id), {
            status: newStatus
        });
    }
};

const deleteCategory = (category) => {
    if (confirm(`Are you sure you want to delete "${category.name}"? This action cannot be undone.`)) {
        router.delete(route('admin-aplikasi.categories.destroy', category.id));
    }
};

const manageApplication = () => {
    router.visit(route('admin-aplikasi.applications.index'));
};

const exportCategories = () => {
    if (!currentApplicationId.value) return;

    const params = new URLSearchParams();
    params.set('aplikasi_id', currentApplicationId.value);

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    if (selectedCategories.value.length > 0) {
        params.set('category_ids', selectedCategories.value.join(','));
    }

    window.open(route('admin-aplikasi.categories.export') + '?' + params.toString(), '_blank');
};

const closeCategoryModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingCategory.value = null;
};

const onCategorySaved = () => {
    closeCategoryModal();
    router.reload();
};

const handlePageChange = (page) => {
    const data = { page: page };

    if (currentApplicationId.value) {
        data.aplikasi_id = currentApplicationId.value;
    }

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) {
            data[key] = value;
        }
    });

    router.get(route('admin-aplikasi.categories.index'), data, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        maintenance: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Watch for changes in categories data to clear selection if current selections no longer exist
watch(() => props.categories.data, (newCategories) => {
    const validCategoryIds = newCategories.map(category => category.id);
    selectedCategories.value = selectedCategories.value.filter(id => validCategoryIds.includes(id));
});

// Watch for changes in selectedApplicationId from backend to sync with local state
watch(() => props.selectedApplicationId, (newAppId) => {
    currentApplicationId.value = newAppId;
});

// Watch for changes in filters from backend to sync with local state
watch(() => props.filters, (newFilters) => {
    filters.value = {
        search: newFilters.search || '',
        status: newFilters.status || '',
        sort_by: newFilters.sort_by || 'name',
    };
}, { deep: true });

onMounted(() => {
    // Initialize component-specific logic here
});
</script>
<template>
    <AppLayout role="admin">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
                    <p class="text-gray-600 mt-1">Generate and export system reports</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="scheduleReport"
                        class="bg-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-purple-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Add Schedule
                    </button>
                </div>
            </div>
        </template>

        <!-- Report Type Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Report Type</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label
                    v-for="reportType in reportTypes"
                    :key="reportType.key"
                    class="relative flex items-center p-4 border rounded-lg cursor-pointer transition-colors"
                    :class="[
                        'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50',
                        selectedReportType === reportType.key ? 'border-indigo-500 bg-indigo-50' : ''
                    ]"
                >
                    <input
                        v-model="selectedReportType"
                        :value="reportType.key"
                        type="radio"
                        class="sr-only"
                    >
                    <div class="flex items-center">
                        <div :class="['w-10 h-10 rounded-lg flex items-center justify-center mr-3', reportType.color]">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ reportType.label }}</p>
                            <p class="text-sm text-gray-500">{{ reportType.description }}</p>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Date Range Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Date Range</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Preset Ranges -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preset Range</label>
                    <select
                        v-model="selectedPreset"
                        @change="applyPresetRange"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="custom">Custom Range</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="this_week">This Week</option>
                        <option value="last_week">Last Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="last_quarter">Last Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="last_year">Last Year</option>
                    </select>
                </div>

                <!-- Custom Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input
                        v-model="dateRange.from"
                        @change="selectedPreset = 'custom'"
                        type="date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input
                        v-model="dateRange.to"
                        @change="selectedPreset = 'custom'"
                        type="date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                </div>

                <div class="flex items-end">
                    <button
                        @click="generatePreview"
                        :disabled="!canGenerateReport"
                        class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Preview Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Additional Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Additional Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select
                        v-model="filters.status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="waiting_response">Waiting Response</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                    <select
                        v-model="filters.priority"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Priority</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <!-- Application Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                    <select
                        v-model="filters.aplikasi_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Applications</option>
                        <option v-for="app in applications" :key="app.id" :value="app.id">
                            {{ app.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Include Options -->
            <div class="mt-6">
                <h3 class="text-md font-medium text-gray-900 mb-3">Include in Report</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <label class="flex items-center">
                        <input
                            v-model="includeOptions.charts"
                            type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Charts</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            v-model="includeOptions.details"
                            type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Detailed Data</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            v-model="includeOptions.comments"
                            type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Comments</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            v-model="includeOptions.attachments"
                            type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Attachments</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Report Preview -->
        <div v-if="reportPreview" class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Report Preview</h2>
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-500">
                            {{ reportPreview.total_records }} records found
                        </span>
                        <button
                            @click="exportReport('pdf')"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition flex items-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export PDF
                        </button>
                        <button
                            @click="exportReport('excel')"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition flex items-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div v-if="reportPreview.summary" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary Statistics</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Total Tickets</p>
                        <p class="text-2xl font-bold text-gray-900">{{ reportPreview.summary.total_tickets }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Resolved</p>
                        <p class="text-2xl font-bold text-green-600">{{ reportPreview.summary.resolved_tickets }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold text-blue-600">{{ reportPreview.summary.in_progress_tickets }}</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Avg Resolution Time</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ reportPreview.summary.avg_resolution_time }}h</p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div v-if="includeOptions.charts && reportPreview.charts" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Visual Analytics</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Status Distribution Chart -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Status Distribution</h4>
                        <PieChart
                            :data="reportPreview.charts.status_distribution"
                            :height="250"
                            :options="pieChartOptions"
                        />
                    </div>

                    <!-- Priority Distribution Chart -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Priority Distribution</h4>
                        <BarChart
                            :data="reportPreview.charts.priority_distribution"
                            :height="250"
                            :options="barChartOptions"
                        />
                    </div>

                    <!-- Trend Chart -->
                    <div class="bg-gray-50 rounded-lg p-4 lg:col-span-2">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Ticket Trends</h4>
                        <LineChart
                            :data="reportPreview.charts.trend_data"
                            :height="300"
                            :options="lineChartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Report Type Specific Sections -->

            <!-- Performance Report Section -->
            <div v-if="selectedReportType === 'performance' && reportPreview.performance" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Teknisi Performance</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teknisi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolved</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Assigned</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolution Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="teknisi in reportPreview.performance" :key="teknisi.nip" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ teknisi.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ teknisi.resolved_tickets }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ teknisi.total_assigned }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ teknisi.resolution_rate }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SLA Compliance Section -->
            <div v-if="selectedReportType === 'sla' && reportPreview.sla_compliance" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">SLA Compliance Analysis</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Within SLA</p>
                        <p class="text-2xl font-bold text-green-600">{{ reportPreview.sla_compliance.within_sla }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">SLA Breached</p>
                        <p class="text-2xl font-bold text-red-600">{{ reportPreview.sla_compliance.sla_breached }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Compliance Rate</p>
                        <p class="text-2xl font-bold text-blue-600">{{ reportPreview.sla_compliance.compliance_rate }}%</p>
                    </div>
                </div>

                <!-- SLA by Priority -->
                <div v-if="reportPreview.sla_compliance.priority_breakdown" class="mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-3">SLA by Priority</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="(data, priority) in reportPreview.sla_compliance.priority_breakdown" :key="priority" class="bg-gray-50 rounded-lg p-4">
                            <h5 class="font-medium text-gray-900 mb-2">{{ priority }}</h5>
                            <p class="text-sm text-gray-600">Total: {{ data.total }}</p>
                            <p class="text-sm text-green-600">Within SLA: {{ data.within_sla }}</p>
                            <p class="text-sm text-red-600">SLA Breached: {{ data.sla_breached }}</p>
                            <p class="text-sm font-medium">Rate: {{ data.compliance_rate }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Usage Section -->
            <div v-if="selectedReportType === 'application' && reportPreview.application_breakdown" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Usage</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="app in reportPreview.application_breakdown" :key="app.aplikasi_id" class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900">{{ app.aplikasi }}</h4>
                        <p class="text-2xl font-bold text-indigo-600 mt-2">{{ app.total_tickets }}</p>
                        <p class="text-sm text-gray-600">Total Tickets</p>
                    </div>
                </div>
            </div>

            <!-- User Activity Section -->
            <div v-if="selectedReportType === 'user_activity' && reportPreview.user_activity" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">User Activity Analysis</h3>
                <div class="mb-6">
                    <p class="text-sm text-gray-600">Total Active Users: <span class="font-bold text-gray-900">{{ reportPreview.user_activity.total_users_active }}</span></p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets Resolved</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolution Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in reportPreview.user_activity.user_activity" :key="user.user?.nama_lengkap" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ user.user?.nama_lengkap || 'Unknown' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ user.tickets_created }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ user.tickets_resolved }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ user.resolution_rate }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Executive Summary Section -->
            <div v-if="selectedReportType === 'summary' && reportPreview.executive_summary" class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Executive Summary</h3>

                <!-- Key Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">SLA Compliance</p>
                        <p class="text-2xl font-bold text-blue-600">{{ reportPreview.executive_summary.overview.sla_compliance_rate }}%</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-green-600">{{ reportPreview.executive_summary.overview.active_users }}</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Resolution Rate</p>
                        <p class="text-2xl font-bold text-purple-600">{{ reportPreview.executive_summary.overview.resolution_rate }}%</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Avg Resolution Time</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ reportPreview.executive_summary.overview.avg_resolution_time }}h</p>
                    </div>
                </div>

                <!-- Top Applications -->
                <div v-if="reportPreview.executive_summary.top_applications" class="mb-6">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Top Applications</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div v-for="app in reportPreview.executive_summary.top_applications" :key="app.aplikasi" class="bg-gray-50 rounded-lg p-4">
                            <h5 class="font-medium text-gray-900">{{ app.aplikasi }}</h5>
                            <p class="text-xl font-bold text-indigo-600">{{ app.count }}</p>
                        </div>
                    </div>
                </div>

                <!-- Top Teknisi -->
                <div v-if="reportPreview.executive_summary.top_teknisi" class="mb-6">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Top Performing Teknisi</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div v-for="teknisi in reportPreview.executive_summary.top_teknisi" :key="teknisi.nip" class="bg-gray-50 rounded-lg p-4">
                            <h5 class="font-medium text-gray-900">{{ teknisi.name }}</h5>
                            <p class="text-sm text-gray-600">Resolved: {{ teknisi.resolved_tickets }}</p>
                            <p class="text-sm font-medium text-green-600">{{ teknisi.resolution_rate }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Data Table -->
            <div v-if="includeOptions.details" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ticket Number
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Priority
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Resolved
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="ticket in reportPreview.data" :key="ticket.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ ticket.ticket_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.title }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.user?.nama_lengkap }}</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(ticket.status)]">
                                    {{ ticket.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(ticket.priority)]">
                                    {{ ticket.priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(ticket.created_at) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(ticket.resolved_at) || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scheduled Reports -->
        <div class="bg-white rounded-lg shadow-md mt-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Scheduled Reports</h2>
            </div>
            <div class="p-6">
                <div v-if="scheduledReports.length === 0" class="text-center text-gray-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2">No scheduled reports</p>
                </div>
                <div v-else class="space-y-3">
                    <div v-for="report in scheduledReports" :key="report.id" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ report.name }}</p>
                            <p class="text-sm text-gray-500">{{ report.schedule }} - Next run: {{ formatDate(report.next_run) }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="editScheduledReport(report)"
                                class="text-indigo-600 hover:text-indigo-900"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteScheduledReport(report)"
                                class="text-red-600 hover:text-red-900"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schedule Report Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showScheduleModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @click="handleBackdropClick"
                >
                    <!-- Backdrop with blur effect -->
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

                        <!-- Modal Panel -->
                        <Transition
                            enter-active-class="transition-all ease-out duration-300"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition-all ease-in duration-200"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <div
                                class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all z-10 w-full sm:max-w-lg"
                                @click.stop
                            >
                                <form @submit.prevent="confirmSchedule">
                                    <!-- Header -->
                                    <div class="px-6 py-4 border-b border-gray-200">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-purple-100">
                                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        {{ editingScheduleId ? 'Edit Scheduled Report' : 'Schedule Report' }}
                                                    </h3>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        {{ editingScheduleId ? 'Update the scheduled report settings' : 'Set up automatic report generation and delivery' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- Close button -->
                                            <button
                                                type="button"
                                                @click="closeScheduleModal"
                                                class="flex-shrink-0 p-2 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                                            >
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Body -->
                                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                                        <div class="space-y-6">
                                            <div>
                                                <label for="schedule_title" class="block text-sm font-medium text-gray-700 mb-2">Report Title</label>
                                                <input
                                                    id="schedule_title"
                                                    v-model="scheduleData.title"
                                                    type="text"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    placeholder="Enter report title"
                                                />
                                            </div>

                                            <div>
                                                <label for="schedule_description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                                                <textarea
                                                    id="schedule_description"
                                                    v-model="scheduleData.description"
                                                    rows="2"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                                                    placeholder="Enter a brief description of this scheduled report"
                                                ></textarea>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="schedule_frequency" class="block text-sm font-medium text-gray-700 mb-2">Schedule Frequency</label>
                                                    <select
                                                        id="schedule_frequency"
                                                        v-model="scheduleData.schedule_frequency"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    >
                                                        <option value="daily">Daily</option>
                                                        <option value="weekly">Weekly</option>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="schedule_time" class="block text-sm font-medium text-gray-700 mb-2">Schedule Time</label>
                                                    <input
                                                        id="schedule_time"
                                                        v-model="scheduleData.schedule_time"
                                                        type="time"
                                                        required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    />
                                                </div>
                                            </div>

                                            <div>
                                                <label for="schedule_recipients" class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
                                                <textarea
                                                    id="schedule_recipients"
                                                    v-model="scheduleData.recipients"
                                                    rows="3"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                                                    placeholder="Enter email addresses separated by commas"
                                                ></textarea>
                                                <p class="text-xs text-gray-500 mt-1">Separate multiple email addresses with commas</p>
                                            </div>

                                            <!-- Current Settings Summary -->
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <h4 class="text-sm font-medium text-gray-900 mb-3">Report Settings Summary</h4>
                                                <div class="space-y-2 text-sm text-gray-600">
                                                    <div class="flex justify-between">
                                                        <span class="font-medium">Report Type:</span>
                                                        <span>{{ getReportTypeLabel(selectedReportType) }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="font-medium">Date Range:</span>
                                                        <span>{{ dateRange.from }} to {{ dateRange.to }}</span>
                                                    </div>
                                                    <div v-if="filters.status" class="flex justify-between">
                                                        <span class="font-medium">Status:</span>
                                                        <span>{{ filters.status }}</span>
                                                    </div>
                                                    <div v-if="filters.priority" class="flex justify-between">
                                                        <span class="font-medium">Priority:</span>
                                                        <span>{{ filters.priority }}</span>
                                                    </div>
                                                    <div v-if="filters.aplikasi_id" class="flex justify-between">
                                                        <span class="font-medium">Application:</span>
                                                        <span>{{ getApplicationName(filters.aplikasi_id) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-row-reverse gap-3">
                                        <button
                                            type="submit"
                                            :disabled="scheduling"
                                            class="flex-1 sm:flex-initial inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg v-if="scheduling" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ scheduling ? 'Saving...' : (editingScheduleId ? 'Update Report' : 'Schedule Report') }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="closeScheduleModal"
                                            class="flex-1 sm:flex-initial inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import { formatDate } from '@/Utils/dateFormatter.js';

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
    scheduledReports: {
        type: Array,
        default: () => [],
    },
});

const selectedReportType = ref('tickets');
const selectedPreset = ref('this_month');
const dateRange = ref({
    from: '',
    to: '',
});

const filters = ref({
    status: '',
    priority: '',
    aplikasi_id: '',
});

const includeOptions = ref({
    charts: true,
    details: true,
    comments: false,
    attachments: false,
});

const reportPreview = ref(null);

const reportTypes = [
    {
        key: 'tickets',
        label: 'Ticket Report',
        description: 'Comprehensive ticket analysis and statistics',
        color: 'bg-blue-500',
    },
    {
        key: 'performance',
        label: 'Performance Report',
        description: 'Teknisi and system performance metrics',
        color: 'bg-green-500',
    },
    {
        key: 'user_activity',
        label: 'User Activity Report',
        description: 'User engagement and activity patterns',
        color: 'bg-purple-500',
    },
    {
        key: 'sla',
        label: 'SLA Compliance Report',
        description: 'Service Level Agreement compliance analysis',
        color: 'bg-orange-500',
    },
    {
        key: 'application',
        label: 'Application Usage Report',
        description: 'Application-specific ticket statistics',
        color: 'bg-indigo-500',
    },
    {
        key: 'summary',
        label: 'Executive Summary',
        description: 'High-level overview for management',
        color: 'bg-red-500',
    },
];

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

const pieChartOptions = {
    ...chartOptions,
    plugins: {
        legend: {
            position: 'right',
        },
    },
};

const barChartOptions = {
    ...chartOptions,
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

const lineChartOptions = {
    ...chartOptions,
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};

const canGenerateReport = computed(() => {
    return selectedReportType.value && dateRange.value.from && dateRange.value.to;
});

const applyPresetRange = () => {
    const today = new Date();
    let from = new Date();
    let to = new Date();

    switch (selectedPreset.value) {
        case 'today':
            from = today;
            to = today;
            break;
        case 'yesterday':
            from = new Date(today);
            from.setDate(today.getDate() - 1);
            to = from;
            break;
        case 'this_week':
            from = new Date(today);
            from.setDate(today.getDate() - today.getDay());
            to = today;
            break;
        case 'last_week':
            from = new Date(today);
            from.setDate(today.getDate() - today.getDay() - 7);
            to = new Date(today);
            to.setDate(today.getDate() - today.getDay() - 1);
            break;
        case 'this_month':
            from = new Date(today.getFullYear(), today.getMonth(), 1);
            to = today;
            break;
        case 'last_month':
            from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            to = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'this_quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            from = new Date(today.getFullYear(), quarter * 3, 1);
            to = today;
            break;
        case 'last_quarter':
            const lastQuarter = Math.floor(today.getMonth() / 3) - 1;
            from = new Date(today.getFullYear(), lastQuarter * 3, 1);
            to = new Date(today.getFullYear(), lastQuarter * 3 + 3, 0);
            break;
        case 'this_year':
            from = new Date(today.getFullYear(), 0, 1);
            to = today;
            break;
        case 'last_year':
            from = new Date(today.getFullYear() - 1, 0, 1);
            to = new Date(today.getFullYear() - 1, 11, 31);
            break;
    }

    dateRange.value.from = from.toISOString().split('T')[0];
    dateRange.value.to = to.toISOString().split('T')[0];
};

const generatePreview = async () => {
    if (!canGenerateReport.value) return;

    const params = {
        type: selectedReportType.value,
        date_from: dateRange.value.from,
        date_to: dateRange.value.to,
        ...filters.value,
        include: includeOptions.value,
    };

    try {
        const response = await fetch(route('admin.reports.preview') + '?' + new URLSearchParams(params).toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            if (response.status === 403) {
                alert('You do not have permission to access reports.');
                return;
            }
            throw new Error('Failed to generate report preview');
        }

        const data = await response.json();
        reportPreview.value = data;
    } catch (error) {
        console.error('Error generating report preview:', error);
        alert('Failed to generate report preview. Please try again.');
    }
};

const exportReport = async (format) => {
    try {
        // Validate required fields
        if (!dateRange.value.from || !dateRange.value.to) {
            alert('Please select date range for the report');
            return;
        }

        const includeOptionsClean = {};
        Object.keys(includeOptions.value).forEach(key => {
            if (includeOptions.value[key]) {
                includeOptionsClean[key] = includeOptions.value[key];
            }
        });

        const params = new URLSearchParams({
            type: selectedReportType.value,
            date_from: dateRange.value.from,
            date_to: dateRange.value.to,
            format: format,
            ...filters.value,
            include: JSON.stringify(includeOptionsClean),
        });

        console.log('Export request params:', Object.fromEntries(params));

        const response = await fetch(route('admin.reports.export') + '?' + params.toString(), {
            method: 'GET',
            headers: {
                'Accept': format === 'excel' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Export failed with status:', response.status, errorText);
            throw new Error(`Export failed: ${errorText}`);
        }

        if (format === 'excel') {
            // Create download link for Excel
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${selectedReportType.value}_report_${dateRange.value.from}_to_${dateRange.value.to}.xlsx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } else {
            // For PDF, open in new window
            const pdfUrl = route('admin.reports.export') + '?' + params.toString();
            window.open(pdfUrl, '_blank');
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export report: ' + error.message);
    }
};

// Schedule report state
const showScheduleModal = ref(false);
const scheduling = ref(false);
const editingScheduleId = ref(null);
const scheduleData = ref({
    report_type: 'tickets',
    title: '',
    description: '',
    schedule_frequency: 'daily',
    schedule_time: '09:00',
    recipients: '',
    parameters: {},
    filters: {},
});

const scheduleReport = () => {
    // Build parameters to pass to schedule
    scheduleData.value.report_type = selectedReportType.value;
    scheduleData.value.title = `${selectedReportType.value.replace('_', ' ')} report`;
    scheduleData.value.filters = { ...filters.value };
    scheduleData.value.parameters = {
        date_from: dateRange.value.from,
        date_to: dateRange.value.to,
        include: { ...includeOptions.value },
    };
    showScheduleModal.value = true;
};

const confirmSchedule = async () => {
    try {
        // Process recipients into an array
        const recipientsArray = scheduleData.value.recipients
            ? scheduleData.value.recipients
                .split(',')
                .map(email => email.trim())
                .filter(email => email.length > 0)
            : [];

        const payload = {
            title: scheduleData.value.title,
            report_type: scheduleData.value.report_type || selectedReportType.value,
            schedule_frequency: scheduleData.value.schedule_frequency,
            schedule_time: scheduleData.value.schedule_time,
            recipients: recipientsArray,
            description: scheduleData.value.description,
            // Include current report settings
            filters: { ...filters.value },
            parameters: {
                date_from: dateRange.value.from,
                date_to: dateRange.value.to,
                include: { ...includeOptions.value },
            },
        };

        let response;

        if (editingScheduleId.value) {
            // Update existing schedule
            response = await fetch(route('admin.reports.scheduled-reports.update', editingScheduleId.value), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
        } else {
            // Create new schedule
            response = await fetch(route('admin.reports.scheduled-reports.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
        }

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to save scheduled report');
        }

        const result = await response.json();
        alert(editingScheduleId.value ? 'Report updated successfully!' : 'Report scheduled successfully!');
        showScheduleModal.value = false;

        // Use Inertia to refresh the page data
        router.reload({
            only: ['scheduledReports'],
            onSuccess: () => {
                console.log('Scheduled reports refreshed');
            }
        });
    } catch (error) {
        console.error('Error saving scheduled report:', error);
        alert('Failed to save scheduled report: ' + error.message);
    }
};

const closeScheduleModal = () => {
    showScheduleModal.value = false;
    editingScheduleId.value = null;
    // Reset schedule data
    scheduleData.value = {
        report_type: 'tickets',
        title: '',
        description: '',
        schedule_frequency: 'daily',
        schedule_time: '09:00',
        recipients: '',
        parameters: {},
        filters: {},
    };
};

const editScheduledReport = (report) => {
    // Populate the schedule data with the existing report data
    scheduleData.value = {
        title: report.title || report.name,
        description: report.description || '',
        schedule_frequency: report.schedule_frequency?.toLowerCase() || 'daily',
        schedule_time: report.schedule_time || '09:00',
        recipients: report.recipients ? report.recipients.join(', ') : '',
        parameters: report.parameters || {},
        filters: report.filters || {},
    };

    // Update the selected report settings to match the report being edited
    if (report.parameters?.date_from && report.parameters?.date_to) {
        dateRange.value = {
            from: report.parameters.date_from,
            to: report.parameters.date_to,
        };
    }

    if (report.filters) {
        filters.value = { ...filters.value, ...report.filters };
    }

    if (report.parameters?.include) {
        includeOptions.value = { ...includeOptions.value, ...report.parameters.include };
    }

    if (report.report_type) {
        selectedReportType.value = report.report_type;
    }

    // Set editing mode
    editingScheduleId.value = report.id;
    showScheduleModal.value = true;
};

const getReportTypeLabel = (type) => {
    const reportType = reportTypes.find(rt => rt.key === type);
    return reportType ? reportType.label : type;
};

const getApplicationName = (appId) => {
    const app = props.applications.find(a => a.id == appId);
    return app ? app.name : 'Unknown Application';
};

const deleteScheduledReport = (report) => {
    if (confirm(`Are you sure you want to delete the scheduled report "${report.name}"?`)) {
        router.delete(route('admin.reports.schedule.delete', report.id), {
            onSuccess: () => {
                alert('Scheduled report deleted successfully!');
            },
            onError: (error) => {
                alert('Failed to delete scheduled report: ' + error);
            }
        });
    }
};


// formatDate is imported from @Utils/dateFormatter.js

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-yellow-100 text-yellow-800',
        assigned: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-indigo-100 text-indigo-800',
        waiting_response: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
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

// Initialize date range on mount
onMounted(() => {
    applyPresetRange();

    // Initialize scheduleData with reset for new report
    scheduleData.value = {
        report_type: 'tickets',
        title: '',
        description: '',
        schedule_frequency: 'daily',
        schedule_time: '09:00',
        recipients: '',
        parameters: {},
        filters: {},
    };

    // Add Escape key listener to close modal
    document.addEventListener('keydown', handleEscapeKey);
});

// Handle Escape key to close modal
const handleEscapeKey = (event) => {
    if (event.key === 'Escape' && showScheduleModal.value) {
        closeScheduleModal();
    }
};

// Optimized backdrop click handler
const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        closeScheduleModal();
    }
};

// Cleanup event listener
onUnmounted(() => {
    document.removeEventListener('keydown', handleEscapeKey);
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
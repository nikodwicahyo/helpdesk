<template>
    <Head :title="getPageTitle('System Settings')" />
    
    <AppLayout role="admin">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
                    <p class="text-gray-600 mt-1">Configure system-wide settings and preferences</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="resetToDefaults"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset to Defaults
                    </button>
                    <button
                        @click="saveSettings"
                        :disabled="!hasChanges"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V2" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </template>

        <!-- Settings Navigation -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button
                        v-for="tab in settingsTabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'py-4 px-6 text-sm font-medium border-b-2 transition-colors',
                            activeTab === tab.key
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                            </svg>
                            <span>{{ tab.label }}</span>
                        </div>
                    </button>
                </nav>
            </div>
        </div>

        <!-- General Settings -->
        <div v-if="activeTab === 'general'" class="space-y-6">
            <!-- Basic Configuration -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Basic Configuration</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- System Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">System Name</label>
                        <input
                            v-model="settings.general.system_name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="HelpDesk System"
                        >
                        <p class="mt-1 text-sm text-gray-500">Name displayed in the system header and emails</p>
                    </div>

                    <!-- System Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">System Email</label>
                        <input
                            v-model="settings.general.system_email"
                            type="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="support@kemlu.go.id"
                        >
                        <p class="mt-1 text-sm text-gray-500">Email address for system notifications</p>
                    </div>

                    <!-- Default Language -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Language</label>
                        <select
                            v-model="settings.general.default_language"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="id">Bahasa Indonesia</option>
                            <option value="en">English</option>
                        </select>
                    </div>

                    <!-- Timezone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                        <select
                            v-model="settings.general.timezone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                            <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                        </select>
                    </div>

                    <!-- Items Per Page -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Items Per Page</label>
                        <select
                            v-model="settings.general.items_per_page"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <!-- Session Timeout -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (minutes)</label>
                        <input
                            v-model.number="settings.general.session_timeout"
                            type="number"
                            min="15"
                            max="480"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                        <p class="mt-1 text-sm text-gray-500">User session timeout in minutes</p>
                    </div>
                </div>
            </div>

            <!-- File Upload Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">File Upload Settings</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Max File Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max File Size (MB)</label>
                        <input
                            v-model.number="settings.general.max_file_size"
                            type="number"
                            min="1"
                            max="50"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Max Files Per Ticket -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Files Per Ticket</label>
                        <input
                            v-model.number="settings.general.max_files_per_ticket"
                            type="number"
                            min="1"
                            max="20"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Allowed File Types -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allowed File Types</label>
                        <input
                            v-model="settings.general.allowed_file_types"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="pdf,doc,docx,jpg,png"
                        >
                        <p class="mt-1 text-sm text-gray-500">Comma-separated file extensions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Settings -->
        <div v-if="activeTab === 'email'" class="space-y-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Email Configuration</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mail Driver -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Driver</label>
                        <select
                            v-model="settings.email.mail_driver"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="smtp">SMTP</option>
                            <option value="mail">Mail</option>
                            <option value="sendmail">Sendmail</option>
                        </select>
                    </div>

                    <!-- Mail Host -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Host</label>
                        <input
                            v-model="settings.email.mail_host"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="smtp.gmail.com"
                        >
                    </div>

                    <!-- Mail Port -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Port</label>
                        <input
                            v-model.number="settings.email.mail_port"
                            type="number"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="587"
                        >
                    </div>

                    <!-- Mail Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Username</label>
                        <input
                            v-model="settings.email.mail_username"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Mail Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Password</label>
                        <div class="relative">
                            <input
                                v-model="settings.email.mail_password"
                                :type="showMailPassword ? 'text' : 'password'"
                                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                            <button
                                @click="showMailPassword = !showMailPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            >
                                <svg v-if="showMailPassword" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Mail Encryption -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Encryption</label>
                        <select
                            v-model="settings.email.mail_encryption"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">None</option>
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                    </div>
                </div>

                <!-- Email Notifications -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Email Notifications</h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input
                                v-model="settings.email.notify_new_ticket"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-sm text-gray-700">Notify admins on new ticket creation</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="settings.email.notify_ticket_assigned"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-sm text-gray-700">Notify teknisi on ticket assignment</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="settings.email.notify_status_change"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-sm text-gray-700">Notify users on ticket status changes</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="settings.email.notify_comment_added"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-sm text-gray-700">Notify relevant parties on new comments</span>
                        </label>
                    </div>
                </div>

                <!-- Test Email -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Test Configuration</h3>
                    <div class="flex items-center space-x-4">
                        <input
                            v-model="testEmail"
                            type="email"
                            placeholder="Enter email address to test"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                        <button
                            @click="sendTestEmail"
                            :disabled="!testEmail"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Send Test Email
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Settings -->
        <div v-if="activeTab === 'tickets'" class="space-y-6">
            <!-- Auto-assignment Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Auto-assignment Settings</h2>
                    <div class="flex items-center space-x-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="settings.tickets.auto_assign_enabled"
                                class="sr-only peer"
                                @change="toggleAutoAssignment"
                            >
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">
                                {{ settings.tickets.auto_assign_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div v-if="settings.tickets.auto_assign_enabled" class="space-y-4">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-indigo-700">
                                    Auto-assignment automatically assigns new tickets to teknisi based on the selected algorithm.
                                    Tickets will be distributed according to workload and availability.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Assignment Algorithm -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Algorithm</label>
                            <select
                                v-model="settings.tickets.auto_assign_algorithm"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="load_balanced">Load Balanced (Least Busy)</option>
                                <option value="round_robin">Round Robin</option>
                                <option value="random">Random Assignment</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                How tickets are distributed among available teknisi
                            </p>
                        </div>

                        <!-- Max Concurrent Tickets -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Concurrent Tickets per Teknisi</label>
                            <input
                                v-model.number="settings.tickets.max_concurrent_tickets"
                                type="number"
                                min="1"
                                max="50"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                            <p class="mt-1 text-sm text-gray-500">
                                Maximum active tickets a teknisi can handle
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">
                                Auto-assignment is currently disabled. New tickets will need to be manually assigned by admin helpdesk staff.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Ticket Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">General Ticket Configuration</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Default Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Priority</label>
                        <select
                            v-model="settings.tickets.default_priority"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Auto-close Resolved Tickets -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Auto-close Resolved Tickets (days)</label>
                        <input
                            v-model.number="settings.tickets.auto_close_resolved_days"
                            type="number"
                            min="1"
                            max="30"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                        <p class="mt-1 text-sm text-gray-500">Days after resolution before auto-closing</p>
                    </div>

                    <!-- Allow Reopening -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allow Reopening Closed Tickets</label>
                        <select
                            v-model="settings.tickets.allow_reopen"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="disabled">Disabled</option>
                            <option value="within_24h">Within 24 hours</option>
                            <option value="within_7d">Within 7 days</option>
                            <option value="always">Always</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Enhanced SLA Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Service Level Agreement (SLA) Settings</h2>
                    <div class="text-sm text-gray-500">
                        Configure response and resolution time targets
                    </div>
                </div>

                <!-- Working Hours Configuration -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Working Hours</h3>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                                <input
                                    v-model="settings.tickets.working_hours_start"
                                    type="time"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                                <input
                                    v-model="settings.tickets.working_hours_end"
                                    type="time"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Working Days</label>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="day in workingDays" :key="day.value" class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :value="day.value"
                                            v-model="settings.tickets.working_days"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-1"
                                        >
                                        <span class="text-sm text-gray-700">{{ day.label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-gray-600">
                            SLA calculations will only consider working hours and days. Time outside these periods will not count towards SLA.
                        </p>
                    </div>
                </div>

                <!-- Response Time SLA -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Response Time SLA (hours)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100 text-red-600 text-sm font-medium">U</span>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Urgent</h4>
                                    <p class="text-xs text-gray-500">Critical issues</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Response Time</label>
                                <div class="flex items-center">
                                    <input
                                        v-model.number="settings.tickets.sla_urgent_response"
                                        type="number"
                                        min="0.5"
                                        max="24"
                                        step="0.5"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    >
                                    <span class="ml-2 text-sm text-gray-600">hours</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-orange-100 text-orange-600 text-sm font-medium">H</span>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">High</h4>
                                    <p class="text-xs text-gray-500">Important issues</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Response Time</label>
                                <div class="flex items-center">
                                    <input
                                        v-model.number="settings.tickets.sla_high_response"
                                        type="number"
                                        min="1"
                                        max="48"
                                        step="1"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    >
                                    <span class="ml-2 text-sm text-gray-600">hours</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100 text-yellow-600 text-sm font-medium">M</span>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Medium</h4>
                                    <p class="text-xs text-gray-500">Standard issues</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Response Time</label>
                                <div class="flex items-center">
                                    <input
                                        v-model.number="settings.tickets.sla_medium_response"
                                        type="number"
                                        min="2"
                                        max="72"
                                        step="1"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    >
                                    <span class="ml-2 text-sm text-gray-600">hours</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100 text-green-600 text-sm font-medium">L</span>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Low</h4>
                                    <p class="text-xs text-gray-500">Minor issues</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Response Time</label>
                                <div class="flex items-center">
                                    <input
                                        v-model.number="settings.tickets.sla_low_response"
                                        type="number"
                                        min="4"
                                        max="168"
                                        step="1"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    >
                                    <span class="ml-2 text-sm text-gray-600">hours</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resolution Time SLA -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Resolution Time SLA (hours)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Urgent Resolution</label>
                            <div class="flex items-center">
                                <input
                                    v-model.number="settings.tickets.sla_urgent_resolution"
                                    type="number"
                                    min="1"
                                    max="48"
                                    step="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <span class="ml-2 text-sm text-gray-600">hours</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">High Resolution</label>
                            <div class="flex items-center">
                                <input
                                    v-model.number="settings.tickets.sla_high_resolution"
                                    type="number"
                                    min="4"
                                    max="96"
                                    step="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <span class="ml-2 text-sm text-gray-600">hours</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Medium Resolution</label>
                            <div class="flex items-center">
                                <input
                                    v-model.number="settings.tickets.sla_medium_resolution"
                                    type="number"
                                    min="8"
                                    max="168"
                                    step="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <span class="ml-2 text-sm text-gray-600">hours</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Low Resolution</label>
                            <div class="flex items-center">
                                <input
                                    v-model.number="settings.tickets.sla_low_resolution"
                                    type="number"
                                    min="24"
                                    max="336"
                                    step="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <span class="ml-2 text-sm text-gray-600">hours</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Escalation Settings -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Escalation Rules</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escalate Unassigned Urgent Tickets</label>
                            <div class="flex items-center">
                                <input
                                    v-model.number="settings.tickets.escalation_urgent_hours"
                                    type="number"
                                    min="0.5"
                                    max="8"
                                    step="0.5"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <span class="ml-2 text-sm text-gray-600">hours after creation</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Automatically escalate if not assigned within this timeframe</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escalate Unassigned High Priority Tickets</label>
                            <div class="flex items-center">
                                <input
                                    v-model.number="settings.tickets.escalation_high_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    step="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <span class="ml-2 text-sm text-gray-600">hours after creation</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Automatically escalate if not assigned within this timeframe</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div v-if="activeTab === 'security'" class="space-y-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Security Configuration</h2>

                <div class="space-y-6">
                    <!-- Password Policy -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Password Policy</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Password Length</label>
                                <input
                                    v-model.number="settings.security.min_password_length"
                                    type="number"
                                    min="6"
                                    max="20"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password Expiry (days)</label>
                                <input
                                    v-model.number="settings.security.password_expiry"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                <p class="mt-1 text-sm text-gray-500">0 for never expires</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3">
                            <label class="flex items-center">
                                <input
                                    v-model="settings.security.require_uppercase"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Require uppercase letters</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="settings.security.require_lowercase"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Require lowercase letters</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="settings.security.require_numbers"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Require numbers</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="settings.security.require_symbols"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Require special symbols</span>
                            </label>
                        </div>
                    </div>

                    <!-- Login Security -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Login Security</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Max Login Attempts</label>
                                <input
                                    v-model.number="settings.security.max_login_attempts"
                                    type="number"
                                    min="3"
                                    max="10"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lockout Duration (minutes)</label>
                                <input
                                    v-model.number="settings.security.lockout_duration"
                                    type="number"
                                    min="1"
                                    max="60"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>
                        </div>
                        <div class="mt-4 space-y-3">
                            <label class="flex items-center">
                                <input
                                    v-model="settings.security.enable_two_factor"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Enable Two-Factor Authentication</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="settings.security.login_notifications"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Send login notifications</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Settings -->
        <div v-if="activeTab === 'backup'" class="space-y-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Backup Configuration</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Auto Backup -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Automatic Backup</label>
                        <select
                            v-model="settings.backup.auto_backup"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="disabled">Disabled</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <!-- Backup Retention -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Backup Retention (days)</label>
                        <input
                            v-model.number="settings.backup.retention_days"
                            type="number"
                            min="7"
                            max="365"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Backup Location -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Backup Location</label>
                        <select
                            v-model="settings.backup.location"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="local">Local Storage</option>
                            <option value="s3">Amazon S3</option>
                            <option value="google_drive">Google Drive</option>
                        </select>
                    </div>
                </div>

                <!-- Manual Backup -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Manual Backup</h3>
                    <div class="flex items-center space-x-4">
                        <button
                            @click="createBackup"
                            :disabled="creatingBackup"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="creatingBackup">Creating Backup...</span>
                            <span v-else>Create Backup Now</span>
                        </button>
                        <button
                            @click="downloadBackup"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition"
                        >
                            Download Latest Backup
                        </button>
                    </div>
                </div>

                <!-- Backup History -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Backup History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="backup in backupHistory" :key="backup.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(backup.created_at) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ backup.type }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ backup.size }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getBackupStatusColor(backup.status)]">
                                            {{ backup.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <button
                                            @click="downloadBackupFile(backup)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Download
                                        </button>
                                        <button
                                            @click="deleteBackup(backup)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="message" class="fixed bottom-4 right-4 max-w-md">
            <div :class="[
                'p-4 rounded-lg shadow-lg border',
                messageType === 'success'
                    ? 'bg-green-50 border-green-200 text-green-800'
                    : 'bg-red-50 border-red-200 text-red-800'
            ]">
                <div class="flex items-center">
                    <svg v-if="messageType === 'success'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ message }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSystemSettings } from '@/composables/useSystemSettings';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    backupHistory: {
        type: Array,
        default: () => [],
    },
});

// Use system settings composable for page title and utilities
const { getPageTitle } = useSystemSettings();

const activeTab = ref('general');
const showMailPassword = ref(false);
const testEmail = ref('');
const creatingBackup = ref(false);
const message = ref('');
const messageType = ref('success');

const settings = ref({
    general: { ...props.settings.general },
    email: { ...props.settings.email },
    tickets: { ...props.settings.tickets },
    security: { ...props.settings.security },
    backup: { ...props.settings.backup },
});

// Working days for SLA calculations
const workingDays = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 0, label: 'Sun' },
];

const settingsTabs = [
    {
        key: 'general',
        label: 'General',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        key: 'email',
        label: 'Email',
        icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    },
    {
        key: 'tickets',
        label: 'Tickets',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        key: 'security',
        label: 'Security',
        icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    },
    {
        key: 'backup',
        label: 'Backup',
        icon: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4',
    },
];

const hasChanges = computed(() => {
    return JSON.stringify(settings.value) !== JSON.stringify(props.settings);
});

const toggleAutoAssignment = () => {
    router.post(route('admin.system.auto-assignment.toggle'), {
        enabled: settings.value.tickets.auto_assign_enabled,
    }, {
        onSuccess: () => {
            const status = settings.value.tickets.auto_assign_enabled ? 'enabled' : 'disabled';
            showMessage(`Auto-assignment ${status} successfully`, 'success');
        },
        onError: () => {
            showMessage('Failed to update auto-assignment settings', 'error');
            // Revert the toggle on error
            settings.value.tickets.auto_assign_enabled = !settings.value.tickets.auto_assign_enabled;
        },
    });
};

const saveSettings = () => {
    router.put(route('admin.system.update'), settings.value, {
        onSuccess: () => {
            showMessage('Settings saved successfully', 'success');
        },
        onError: () => {
            showMessage('Failed to save settings', 'error');
        },
    });
};

const resetToDefaults = () => {
    if (confirm('Are you sure you want to reset all settings to default values?')) {
        router.post(route('admin.system.reset'), {}, {
            onSuccess: () => {
                showMessage('Settings reset to defaults', 'success');
                router.reload();
            },
            onError: () => {
                showMessage('Failed to reset settings', 'error');
            },
        });
    }
};

const sendTestEmail = () => {
    if (!testEmail.value) return;

    router.post(route('admin.system.test-email'), {
        email: testEmail.value,
        settings: settings.value.email,
    }, {
        onSuccess: () => {
            showMessage('Test email sent successfully', 'success');
        },
        onError: () => {
            showMessage('Failed to send test email', 'error');
        },
    });
};

const createBackup = () => {
    creatingBackup.value = true;
    router.post(route('admin.backup.create'), {}, {
        onSuccess: () => {
            showMessage('Backup created successfully', 'success');
            router.reload({ only: ['backupHistory'] });
        },
        onError: () => {
            showMessage('Failed to create backup', 'error');
        },
        onFinish: () => {
            creatingBackup.value = false;
        },
    });
};

const downloadBackup = () => {
    window.open(route('admin.backup.download.latest'), '_blank');
};

const downloadBackupFile = (backup) => {
    window.open(route('admin.backup.download', backup.id), '_blank');
};

const deleteBackup = (backup) => {
    if (confirm(`Are you sure you want to delete this backup from ${formatDate(backup.created_at)}?`)) {
        router.delete(route('admin.backup.delete', backup.id), {
            onSuccess: () => {
                showMessage('Backup deleted successfully', 'success');
                router.reload({ only: ['backupHistory'] });
            },
            onError: () => {
                showMessage('Failed to delete backup', 'error');
            },
        });
    }
};

const showMessage = (msg, type) => {
    message.value = msg;
    messageType.value = type;
    setTimeout(() => {
        message.value = '';
    }, 5000);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
};

const getBackupStatusColor = (status) => {
    const colors = {
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    // Initialize any settings-specific logic here
});
</script>
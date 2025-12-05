<template>
  <AppLayout role="admin">
    <template #header>
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center space-x-3 mb-2">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div>
              <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ t('activityLog.title') }}</h1>
              <p class="text-gray-600 text-sm sm:text-base">
                {{ t('activityLog.description') }}
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-4 text-sm text-gray-500">
            <div class="flex items-center">
              <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
              <span>{{ t('activityLog.liveMonitoring') }}</span>
            </div>
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>{{ new Date().toLocaleString(locale, { timeStyle: 'short', dateStyle: 'short' }) }}</span>
            </div>
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span>{{ logs.total || 0 }} {{ t('activityLog.totalActivities') }}</span>
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="refreshLogs"
            :disabled="refreshing"
            class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center"
          >
            <svg v-if="refreshing" class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ t('action.refresh') }}
          </button>
          <button
            @click="showExportMenu = !showExportMenu"
            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center relative"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ t('common.export') }}
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>

            <!-- Export Dropdown -->
            <div v-if="showExportMenu" class="absolute top-full right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
              <button
                @click="exportLogs('csv')"
                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 transition flex items-center gap-2"
              >
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                </svg>
                {{ t('activityLog.exportAsCSV') }}
              </button>
              <button
                @click="exportLogs('excel')"
                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 transition flex items-center gap-2"
              >
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                </svg>
                {{ t('activityLog.exportAsExcel') }}
              </button>
            </div>
          </button>
        </div>
      </div>
    </template>

    <!-- Filters Panel -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ t('activityLog.filters') }}</h3>
        <button
          @click="resetFilters"
          :disabled="loading || !hasActiveFilters"
          class="text-sm text-gray-500 hover:text-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ t('activityLog.resetAll') }}
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Start Date Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.startDate') }}</label>
          <input
            v-model="filters.start_date"
            type="date"
            :disabled="loading"
            @keyup.enter="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
          />
        </div>

        <!-- End Date Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.endDate') }}</label>
          <input
            v-model="filters.end_date"
            type="date"
            :disabled="loading"
            @keyup.enter="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
          />
        </div>

        <!-- User/Actor Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.actor') }}</label>
          <select
            v-model="filters.user_id"
            :disabled="loading"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
          >
            <option value="">{{ t('activityLog.allUsers') }}</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.type }})
            </option>
          </select>
        </div>

        <!-- Action Type Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.action') }}</label>
          <select
            v-model="filters.action"
            :disabled="loading"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
          >
            <option value="">{{ t('activityLog.allActions') }}</option>
            <option v-for="action in actionTypes" :key="action.value" :value="action.value">
              {{ action.label }}
            </option>
          </select>
        </div>

        <!-- Entity Type Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.entityType') }}</label>
          <select
            v-model="filters.entity_type"
            :disabled="loading"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
          >
            <option value="">{{ t('activityLog.allEntities') }}</option>
            <option value="Ticket">{{ t('activityLog.entityTypes.ticket') }}</option>
            <option value="User">{{ t('activityLog.entityTypes.user') }}</option>
            <option value="AdminHelpdesk">{{ t('activityLog.entityTypes.adminHelpdesk') }}</option>
            <option value="AdminAplikasi">{{ t('activityLog.entityTypes.adminAplikasi') }}</option>
            <option value="Teknisi">{{ t('activityLog.entityTypes.teknisi') }}</option>
            <option value="Aplikasi">{{ t('activityLog.entityTypes.aplikasi') }}</option>
            <option value="KategoriMasalah">{{ t('activityLog.entityTypes.kategoriMasalah') }}</option>
            <option value="TicketComment">{{ t('activityLog.entityTypes.ticketComment') }}</option>
            <option value="Report">{{ t('activityLog.entityTypes.report') }}</option>
            <option value="ScheduledReport">{{ t('activityLog.entityTypes.scheduledReport') }}</option>
            <option value="SystemSetting">{{ t('activityLog.entityTypes.systemSetting') }}</option>
          </select>
        </div>
      </div>

      <div class="mt-4 flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <!-- Quick Date Ranges -->
          <div class="flex space-x-2">
            <button
              @click="setQuickDateRange('today')"
              :disabled="loading"
              class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('time.today') }}
            </button>
            <button
              @click="setQuickDateRange('yesterday')"
              :disabled="loading"
              class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('time.yesterday') }}
            </button>
            <button
              @click="setQuickDateRange('week')"
              :disabled="loading"
              class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('time.thisWeek') }}
            </button>
            <button
              @click="setQuickDateRange('month')"
              :disabled="loading"
              class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('time.thisMonth') }}
            </button>
          </div>
        </div>

        <button
          @click="applyFilters"
          :disabled="loading"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          {{ loading ? t('activityLog.applying') : t('activityLog.applyFilters') }}
        </button>
      </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
      <DataTable
        :data="logs && logs.data ? logs.data : []"
        :columns="tableColumns"
        :loading="loading"
        :show-pagination="false"
        :search-placeholder="t('activityLog.searchPlaceholder')"
        @search="handleSearch"
      >
        <template #cell-timestamp="{ row }">
          <div class="text-sm">
            <div class="font-medium text-gray-900">{{ formatDate(row.created_at) }}</div>
            <div class="text-gray-500">{{ formatTime(row.created_at) }}</div>
          </div>
        </template>

        <template #cell-actor="{ row }">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
              {{ getInitials(row.actor_name) }}
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ row.actor_name }}</div>
              <div class="text-xs text-gray-500">{{ row.actor_type }}</div>
              <div v-if="row.actor_id" class="text-xs text-gray-400">{{ row.actor_id }}</div>
            </div>
          </div>
        </template>

        <template #cell-action="{ row }">
          <div class="flex items-center space-x-2">
            <span :class="getActionBadgeClass(row.action)" class="px-2 py-1 text-xs font-medium rounded-full">
              {{ formatAction(row.action) }}
            </span>
            <span v-if="row.entity_type" class="text-xs text-gray-500">
              {{ row.entity_type }}
            </span>
          </div>
        </template>

        <template #cell-description="{ row }">
          <div class="text-sm text-gray-900 max-w-xs">
            <div class="font-medium">{{ row.description || generateDescription(row) }}</div>
            <div v-if="row.entity_id" class="text-xs text-gray-500 mt-1">
              ID: {{ row.entity_id }}
            </div>
          </div>
        </template>

        <template #actions="{ row }">
          <button
            @click="viewDetails(row)"
            class="text-indigo-600 hover:text-indigo-900 transition"
            :title="t('common.viewDetails')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </template>
      </DataTable>

      <!-- Pagination -->
      <div v-if="logs && logs.data && logs.data.length > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <Pagination
          :data="logs"
          label="activities"
          @page-changed="handlePageChange"
        />
      </div>
    </div>

    <!-- Details Modal -->
    <Modal
      v-model:show="showDetailsModal"
      :title="t('activityLog.details')"
      size="lg"
    >
      <div v-if="selectedLog" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.timestamp') }}</label>
            <p class="text-sm text-gray-900">{{ formatFullDateTime(selectedLog.created_at) }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.action') }}</label>
            <span :class="getActionBadgeClass(selectedLog.action)" class="px-2 py-1 text-xs font-medium rounded-full">
              {{ formatAction(selectedLog.action) }}
            </span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.actor') }}</label>
            <p class="text-sm text-gray-900">{{ selectedLog.actor_name }} ({{ selectedLog.actor_type }})</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.entity') }}</label>
            <p class="text-sm text-gray-900">{{ selectedLog.entity_type }} {{ selectedLog.entity_id ? `#${selectedLog.entity_id}` : '' }}</p>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.details') }}</label>
          <p class="text-sm text-gray-900">{{ selectedLog.description || generateDescription(selectedLog) }}</p>
        </div>

        <!-- Request Information -->
        <div v-if="selectedLog.ip_address || selectedLog.user_agent || selectedLog.route_name">
          <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ t('activityLog.requestInfo') }}</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="selectedLog.ip_address">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.ipAddress') }}</label>
              <p class="text-sm text-gray-900 font-mono">{{ selectedLog.ip_address }}</p>
            </div>
            <div v-if="selectedLog.route_name">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.route') }}</label>
              <p class="text-sm text-gray-900 font-mono">{{ selectedLog.route_name }}</p>
            </div>
            <div v-if="selectedLog.http_method">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.httpMethod') }}</label>
              <span :class="getHttpMethodClass(selectedLog.http_method)" class="px-2 py-1 text-xs font-medium rounded">
                {{ selectedLog.http_method }}
              </span>
            </div>
            <div v-if="selectedLog.user_agent">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('activityLog.userAgent') }}</label>
              <p class="text-sm text-gray-900 break-all">{{ selectedLog.user_agent }}</p>
            </div>
          </div>
        </div>

        <!-- Metadata -->
        <div v-if="selectedLog.metadata && Object.keys(selectedLog.metadata).length > 0">
          <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ t('activityLog.additionalInfo') }}</h4>
          <pre class="bg-gray-50 p-4 rounded-lg text-xs text-gray-700 overflow-x-auto">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/Common/DataTable.vue'
import Modal from '@/Components/Common/Modal.vue'
import Pagination from '@/Components/Common/SimplePagination.vue'

const { t, locale } = useI18n()

// Props
const props = defineProps({
  logs: {
    type: Object,
    default: () => ({ data: [], total: 0 })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  users: {
    type: Array,
    default: () => []
  }
})

// Debug: Log the data structure
console.log('Activity Log Props:', {
  logs: props.logs,
  logsType: typeof props.logs,
  hasData: !!props.logs?.data,
  dataLength: props.logs?.data?.length,
  users: props.users?.length
})

// Reactive state
const loading = ref(false)
const refreshing = ref(false)
const showDetailsModal = ref(false)
const showExportMenu = ref(false)
const selectedLog = ref(null)
const searchQuery = ref('')

// Filters
const filters = reactive({
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
  user_id: props.filters.user_id || '',
  action: props.filters.action || '',
  entity_type: props.filters.entity_type || '',
  search: props.filters.search || ''
})

// Action types for dropdown - updated to match backend
const actionTypes = [
  // Basic CRUD
  { value: 'created', label: t('actions.created') },
  { value: 'updated', label: t('actions.updated') },
  { value: 'deleted', label: t('actions.deleted') },
  
  // Ticket Actions
  { value: 'assigned', label: t('actions.assigned') },
  { value: 'unassigned', label: t('actions.unassigned') },
  { value: 'reassigned', label: t('actions.reassigned') },
  { value: 'resolved', label: t('actions.resolved') },
  { value: 'closed', label: t('actions.closed') },
  { value: 'reopened', label: t('actions.reopened') },
  { value: 'escalated', label: t('actions.escalated') },
  { value: 'commented', label: t('actions.commented') },
  { value: 'status_changed', label: t('actions.status_changed') },
  { value: 'priority_changed', label: t('actions.priority_changed') },
  
  // Bulk Actions
  { value: 'bulk_assigned', label: t('actions.bulk_assigned') },
  { value: 'bulk_updated', label: t('actions.bulk_updated') },
  { value: 'bulk_deleted', label: t('actions.bulk_deleted') },
  { value: 'bulk_activated', label: t('actions.bulk_activated') },
  { value: 'bulk_deactivated', label: t('actions.bulk_deactivated') },
  
  // User Actions
  { value: 'profile_updated', label: t('actions.profile_updated') },
  { value: 'password_changed', label: t('actions.password_changed') },
  { value: 'email_changed', label: t('actions.email_changed') },
  { value: 'account_locked', label: t('actions.account_locked') },
  { value: 'account_unlocked', label: t('actions.account_unlocked') },
  
  // Authentication
  { value: 'login', label: t('actions.login') },
  { value: 'logout', label: t('actions.logout') },
  { value: 'login_failed', label: t('actions.login_failed') },
  
  // Data Operations
  { value: 'exported', label: t('actions.exported') },
  { value: 'imported', label: t('actions.imported') },
  
  // Reports & Settings
  { value: 'report_generated', label: t('actions.report_generated') },
  { value: 'setting_changed', label: t('actions.setting_changed') },
  { value: 'config_changed', label: t('actions.config_changed') }
]

// Table columns
const tableColumns = [
  { key: 'timestamp', label: t('activityLog.timestamp'), sortable: true },
  { key: 'actor', label: t('activityLog.actor'), sortable: false },
  { key: 'action', label: t('activityLog.action'), sortable: true },
  { key: 'description', label: t('activityLog.details'), sortable: false }
]

// Computed properties
const hasActiveFilters = computed(() => {
  return Object.values(filters).some(value => value !== '')
})

// Methods
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID')
}

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFullDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('id-ID')
}

const formatAction = (action) => {
  if (!action) return t('common.unknown') || 'Unknown'

  // Map actions to proper labels
  const actionLabels = {
    'created': t('actions.created'),
    'updated': t('actions.updated'),
    'deleted': t('actions.deleted'),
    'assigned': t('actions.assigned'),
    'unassigned': t('actions.unassigned'),
    'reassigned': t('actions.reassigned'),
    'resolved': t('actions.resolved'),
    'closed': t('actions.closed'),
    'reopened': t('actions.reopened'),
    'escalated': t('actions.escalated'),
    'commented': t('actions.commented'),
    'status_changed': t('actions.status_changed'),
    'priority_changed': t('actions.priority_changed'),
    'bulk_assigned': t('actions.bulk_assigned'),
    'bulk_updated': t('actions.bulk_updated'),
    'bulk_deleted': t('actions.bulk_deleted'),
    'bulk_activated': t('actions.bulk_activated'),
    'bulk_deactivated': t('actions.bulk_deactivated'),
    'profile_updated': t('actions.profile_updated'),
    'password_changed': t('actions.password_changed'),
    'email_changed': t('actions.email_changed'),
    'account_locked': t('actions.account_locked'),
    'account_unlocked': t('actions.account_unlocked'),
    'login': t('actions.login'),
    'logout': t('actions.logout'),
    'login_failed': t('actions.login_failed'),
    'exported': t('actions.exported'),
    'imported': t('actions.imported'),
    'report_generated': t('actions.report_generated'),
    'setting_changed': t('actions.setting_changed'),
    'config_changed': t('actions.config_changed')
  }

  return actionLabels[action] || action.split('_').map(word =>
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const getInitials = (name) => {
  return name ? name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : 'UN'
}

const getActionBadgeClass = (action) => {
  const baseClasses = 'px-2 py-1 text-xs font-medium rounded-full'

  switch (action) {
    // Basic CRUD
    case 'created':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'updated':
    case 'profile_updated':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'deleted':
      return `${baseClasses} bg-red-100 text-red-800`
    
    // Ticket Assignments
    case 'assigned':
    case 'reassigned':
      return `${baseClasses} bg-purple-100 text-purple-800`
    case 'unassigned':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    
    // Ticket Status
    case 'resolved':
      return `${baseClasses} bg-emerald-100 text-emerald-800`
    case 'closed':
      return `${baseClasses} bg-gray-100 text-gray-800`
    case 'reopened':
      return `${baseClasses} bg-orange-100 text-orange-800`
    case 'escalated':
      return `${baseClasses} bg-red-200 text-red-900`
    case 'commented':
      return `${baseClasses} bg-indigo-100 text-indigo-800`
    
    // Changes
    case 'status_changed':
    case 'priority_changed':
      return `${baseClasses} bg-amber-100 text-amber-800`
    
    // Authentication
    case 'login':
    case 'logout':
      return `${baseClasses} bg-cyan-100 text-cyan-800`
    case 'login_failed':
      return `${baseClasses} bg-red-200 text-red-800`
    
    // Data Operations
    case 'exported':
    case 'imported':
      return `${baseClasses} bg-teal-100 text-teal-800`
    
    // User Security Actions
    case 'password_changed':
    case 'email_changed':
      return `${baseClasses} bg-amber-100 text-amber-800`
    case 'account_locked':
      return `${baseClasses} bg-red-200 text-red-900`
    case 'account_unlocked':
      return `${baseClasses} bg-green-200 text-green-900`
    
    // Bulk Operations
    case 'bulk_assigned':
    case 'bulk_updated':
      return `${baseClasses} bg-purple-100 text-purple-800`
    case 'bulk_deleted':
      return `${baseClasses} bg-red-100 text-red-800`
    case 'bulk_activated':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'bulk_deactivated':
      return `${baseClasses} bg-gray-100 text-gray-800`
    
    // Reports & Settings
    case 'report_generated':
      return `${baseClasses} bg-indigo-100 text-indigo-800`
    case 'setting_changed':
    case 'config_changed':
      return `${baseClasses} bg-purple-100 text-purple-800`
    
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
}

const getHttpMethodClass = (method) => {
  const baseClasses = 'px-2 py-1 text-xs font-medium rounded'

  switch (method?.toUpperCase()) {
    case 'GET':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'POST':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'PUT':
    case 'PATCH':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    case 'DELETE':
      return `${baseClasses} bg-red-100 text-red-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
}

const generateDescription = (log) => {
  if (!log.action) return t('common.unknownAction')

  const action = formatAction(log.action)
  const entity = log.entity_type || t('common.item')
  const actor = log.actor_name || t('common.unknownUser')

  return `${actor} ${action.toLowerCase()} ${entity}${log.entity_id ? ` #${log.entity_id}` : ''}`
}

const viewDetails = (log) => {
  selectedLog.value = log
  showDetailsModal.value = true
}

const applyFilters = () => {
  loading.value = true
  
  // Build filter data object, only include non-empty values
  const filterData = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      filterData[key] = value
    }
  })

  // Use Inertia router with proper data object
  router.get(route('admin.activity-log.index'), filterData, {
    preserveState: true,
    preserveScroll: false,
    only: ['logs', 'filters'],
    onFinish: () => {
      loading.value = false
    }
  })
}

const resetFilters = () => {
  loading.value = true
  
  // Clear all filter values
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  
  // Navigate to page without any query parameters
  router.get(route('admin.activity-log.index'), {}, {
    preserveState: false,
    preserveScroll: false,
    only: ['logs', 'filters', 'users'],
    onFinish: () => {
      loading.value = false
    }
  })
}

const setQuickDateRange = (range) => {
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  switch (range) {
    case 'today':
      filters.start_date = today.toISOString().split('T')[0]
      filters.end_date = today.toISOString().split('T')[0]
      break
    case 'yesterday':
      filters.start_date = yesterday.toISOString().split('T')[0]
      filters.end_date = yesterday.toISOString().split('T')[0]
      break
    case 'week':
      const weekStart = new Date(today)
      weekStart.setDate(today.getDate() - today.getDay())
      filters.start_date = weekStart.toISOString().split('T')[0]
      filters.end_date = today.toISOString().split('T')[0]
      break
    case 'month':
      const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
      filters.start_date = monthStart.toISOString().split('T')[0]
      filters.end_date = today.toISOString().split('T')[0]
      break
  }
  
  // Automatically apply filters after setting quick date range
  applyFilters()
}

const handleSearch = (query) => {
  searchQuery.value = query
  filters.search = query
  applyFilters()
}

const handlePageChange = (page) => {
  loading.value = true
  
  // Build filter data with current filters + page number
  const filterData = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      filterData[key] = value
    }
  })
  filterData.page = page

  // Navigate with filters preserved
  router.get(route('admin.activity-log.index'), filterData, {
    preserveState: true,
    preserveScroll: true,
    only: ['logs'],
    onFinish: () => {
      loading.value = false
    }
  })
}

const refreshLogs = () => {
  refreshing.value = true
  router.reload({
    onFinish: () => {
      refreshing.value = false
    }
  })
}

const exportLogs = (format) => {
  showExportMenu.value = false

  const params = new URLSearchParams()
  params.append('format', format)

  Object.entries(filters).forEach(([key, value]) => {
    if (value) {
      params.append(key, value)
    }
  })

  window.open(`${window.location.pathname}/export?${params.toString()}`, '_blank')
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
  if (!e.target.closest('.relative')) {
    showExportMenu.value = false
  }
})
</script>

<style scoped>
.animate-slideInDown {
  animation: slideInDown 0.5s ease-out;
}

.animate-fadeInUp {
  animation: fadeInUp 0.5s ease-out;
}

.animation-delay-200 {
  animation-delay: 200ms;
}

.hover-lift {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-lift:hover {
  transform: translateY(-4px);
}

@keyframes slideInDown {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes fadeInUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>

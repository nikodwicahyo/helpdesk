<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900 flex items-center">
        <ClockIcon class="h-5 w-5 mr-2 text-gray-400" />
        Recent Searches
      </h3>
      <button
        v-if="history.length > 0"
        @click="clearHistory"
        class="text-sm text-gray-500 hover:text-red-600"
      >
        Clear all
      </button>
    </div>

    <div v-if="history.length === 0" class="text-center py-8">
      <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No recent searches</h3>
      <p class="mt-1 text-xs text-gray-500">
        Your recent searches will appear here.
      </p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="(item, index) in history"
        :key="index"
        @click="$emit('search', item)"
        class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors duration-150"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2 mb-1">
              <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
              <span class="text-sm font-medium text-gray-900 truncate">
                {{ item.query || 'Filter search' }}
              </span>
            </div>
            <p class="text-xs text-gray-500">
              {{ formatSearchTime(item.searched_at) }}
            </p>
            <div v-if="item.filters && hasActiveFilters(item.filters)" class="mt-2">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="filter in getActiveFilters(item.filters)"
                  :key="filter.label"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ filter.label }}
                </span>
              </div>
            </div>
          </div>
          <ChevronRightIcon class="h-4 w-4 text-gray-400 mt-1 flex-shrink-0" />
        </div>
      </div>
    </div>

    <div v-if="history.length > 0" class="mt-4 pt-4 border-t border-gray-200">
      <button
        @click="$emit('clear')"
        class="w-full text-center text-sm text-gray-500 hover:text-gray-700"
      >
        View all search history
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ClockIcon, MagnifyingGlassIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  history: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['search', 'clear'])

const formatSearchTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))

  if (diffInMinutes < 1) {
    return 'Just now'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`
  } else if (diffInMinutes < 1440) {
    const hours = Math.floor(diffInMinutes / 60)
    return `${hours} hour${hours > 1 ? 's' : ''} ago`
  } else if (diffInMinutes < 10080) {
    const days = Math.floor(diffInMinutes / 1440)
    return `${days} day${days > 1 ? 's' : ''} ago`
  } else {
    return date.toLocaleDateString()
  }
}

const hasActiveFilters = (filters) => {
  const filterKeys = Object.keys(filters).filter(key =>
    !['query', 'page', 'per_page', 'sort', 'direction'].includes(key)
  )

  return filterKeys.some(key => {
    const value = filters[key]
    if (Array.isArray(value)) return value.length > 0
    return value !== '' && value !== null && value !== undefined
  })
}

const getActiveFilters = (filters) => {
  const activeFilters = []

  // Status filters
  if (filters.status && filters.status.length > 0) {
    activeFilters.push({
      label: `Status: ${filters.status.length} selected`
    })
  }

  // Priority filters
  if (filters.priority && filters.priority.length > 0) {
    activeFilters.push({
      label: `Priority: ${filters.priority.length} selected`
    })
  }

  // Date range
  if (filters.date_from || filters.date_to) {
    activeFilters.push({
      label: 'Date range'
    })
  }

  // Application
  if (filters.aplikasi_id) {
    activeFilters.push({
      label: 'Application'
    })
  }

  // Advanced filters
  const advancedFilters = ['is_escalated', 'has_attachments', 'has_rating', 'is_overdue', 'is_unassigned']
  const activeAdvanced = advancedFilters.filter(key => filters[key] === true)

  if (activeAdvanced.length > 0) {
    activeFilters.push({
      label: `${activeAdvanced.length} advanced filter${activeAdvanced.length > 1 ? 's' : ''}`
    })
  }

  return activeFilters.slice(0, 3) // Limit to 3 filters for display
}
</script>
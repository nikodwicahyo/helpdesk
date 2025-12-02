<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900 flex items-center">
        <BookmarkIcon class="h-5 w-5 mr-2 text-gray-400" />
        Saved Searches
      </h3>
      <span class="text-sm text-gray-500">{{ savedSearches.length }} saved</span>
    </div>

    <div v-if="savedSearches.length === 0" class="text-center py-8">
      <BookmarkIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No saved searches</h3>
      <p class="mt-1 text-xs text-gray-500">
        Save your frequently used search filters for quick access.
      </p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="search in savedSearches"
        :key="search.id"
        class="group relative"
      >
        <div
          @click="$emit('search', search)"
          class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors duration-150"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-2 mb-1">
                <BookmarkIcon class="h-4 w-4 text-blue-500" />
                <span class="text-sm font-medium text-gray-900 truncate">
                  {{ search.name }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mb-2">
                Created {{ formatCreationTime(search.created_at) }}
              </p>
              <div class="flex flex-wrap gap-1">
                <span
                  v-if="search.filters.query"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                >
                  "{{ search.filters.query }}"
                </span>
                <span
                  v-for="filter in getActiveFilters(search.filters)"
                  :key="filter.label"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ filter.label }}
                </span>
              </div>
            </div>
            <div class="flex items-center space-x-1 ml-2">
              <ChevronRightIcon class="h-4 w-4 text-gray-400" />
            </div>
          </div>
        </div>

        <!-- Delete button (visible on hover) -->
        <button
          @click.stop="deleteSearch(search.id)"
          class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150 p-1 text-red-500 hover:text-red-700 bg-white rounded-full shadow-sm"
          title="Delete saved search"
        >
          <TrashIcon class="h-4 w-4" />
        </button>
      </div>
    </div>

    <div v-if="savedSearches.length > 0" class="mt-4 pt-4 border-t border-gray-200">
      <p class="text-xs text-gray-500 text-center">
        Click on any saved search to apply its filters
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { BookmarkIcon, ChevronRightIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useDateFormatter } from '@/composables/useDateFormatter'

const props = defineProps({
  savedSearches: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['search', 'delete'])

// Use centralized date formatter
const { formatTimeAgo: formatCreationTime } = useDateFormatter()

const getActiveFilters = (filters) => {
  const activeFilters = []

  // Status filters
  if (filters.status && filters.status.length > 0) {
    activeFilters.push({
      label: `Status: ${filters.status.length}`
    })
  }

  // Priority filters
  if (filters.priority && filters.priority.length > 0) {
    activeFilters.push({
      label: `Priority: ${filters.priority.length}`
    })
  }

  // Date range
  if (filters.date_from || filters.date_to) {
    activeFilters.push({
      label: 'Date range'
    })
  }

  // Due date range
  if (filters.due_date_from || filters.due_date_to) {
    activeFilters.push({
      label: 'Due date range'
    })
  }

  // Application
  if (filters.aplikasi_id) {
    activeFilters.push({
      label: 'Application'
    })
  }

  // Category
  if (filters.kategori_masalah_id) {
    activeFilters.push({
      label: 'Category'
    })
  }

  // User
  if (filters.user_nip) {
    activeFilters.push({
      label: 'User'
    })
  }

  // Technician
  if (filters.assigned_teknisi_nip) {
    activeFilters.push({
      label: 'Technician'
    })
  }

  // Advanced filters
  const advancedFilters = ['is_escalated', 'has_attachments', 'has_rating', 'is_overdue', 'is_unassigned']
  const activeAdvanced = advancedFilters.filter(key => filters[key] === true)

  if (activeAdvanced.length > 0) {
    activeFilters.push({
      label: `${activeAdvanced.length} filter${activeAdvanced.length > 1 ? 's' : ''}`
    })
  }

  return activeFilters.slice(0, 4) // Limit to 4 filters for display
}

const deleteSearch = (searchId) => {
  if (confirm('Are you sure you want to delete this saved search?')) {
    emit('delete', searchId)
  }
}
</script>
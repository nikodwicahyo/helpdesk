<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900 flex items-center">
        <ChartBarIcon class="h-5 w-5 mr-2 text-gray-400" />
        Search Insights
      </h3>
      <button
        @click="refresh"
        class="text-sm text-gray-500 hover:text-gray-700"
      >
        <ArrowPathIcon class="h-4 w-4" />
      </button>
    </div>

    <!-- Popular Searches -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 mb-3">Popular Searches</h4>
      <div v-if="popularTerms.length === 0" class="text-center py-4">
        <p class="text-xs text-gray-500">No popular searches yet</p>
      </div>
      <div v-else class="flex flex-wrap gap-2">
        <button
          v-for="(term, index) in popularTerms"
          :key="term"
          @click="$emit('search-term', term)"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors duration-150"
        >
          <span class="w-4 h-4 bg-indigo-200 rounded-full flex items-center justify-center mr-1 text-xs">
            {{ index + 1 }}
          </span>
          {{ term }}
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 mb-3">Quick Stats</h4>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-lg p-3">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="h-5 w-5 text-blue-500" />
            </div>
            <div class="ml-3">
              <p class="text-xs font-medium text-gray-500">Recent Searches</p>
              <p class="text-lg font-semibold text-gray-900">{{ searchHistoryCount }}</p>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <BookmarkIcon class="h-5 w-5 text-green-500" />
            </div>
            <div class="ml-3">
              <p class="text-xs font-medium text-gray-500">Saved Searches</p>
              <p class="text-lg font-semibold text-gray-900">{{ savedSearchesCount }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Tips -->
    <div>
      <h4 class="text-sm font-medium text-gray-700 mb-3">Search Tips</h4>
      <div class="space-y-2">
        <div class="flex items-start space-x-2">
          <InformationCircleIcon class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
          <p class="text-xs text-gray-600">
            Use quotes for exact phrases: <span class="font-mono bg-gray-100 px-1">"login issue"</span>
          </p>
        </div>
        <div class="flex items-start space-x-2">
          <InformationCircleIcon class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
          <p class="text-xs text-gray-600">
            Combine filters for precise results
          </p>
        </div>
        <div class="flex items-start space-x-2">
          <InformationCircleIcon class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
          <p class="text-xs text-gray-600">
            Save frequently used searches for quick access
          </p>
        </div>
        <div class="flex items-start space-x-2">
          <InformationCircleIcon class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
          <p class="text-xs text-gray-600">
            Search across ticket numbers, titles, descriptions, and comments
          </p>
        </div>
      </div>
    </div>

    <!-- Keyboard Shortcuts -->
    <div class="mt-6 pt-6 border-t border-gray-200">
      <h4 class="text-sm font-medium text-gray-700 mb-3">Keyboard Shortcuts</h4>
      <div class="space-y-1">
        <div class="flex items-center justify-between text-xs">
          <span class="text-gray-600">Search</span>
          <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">Ctrl + K</kbd>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-gray-600">Clear filters</span>
          <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">Ctrl + R</kbd>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-gray-600">Toggle filters</span>
          <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">Ctrl + F</kbd>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  ChartBarIcon,
  ArrowPathIcon,
  ClockIcon,
  BookmarkIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  popularTerms: {
    type: Array,
    default: () => []
  },
  searchHistoryCount: {
    type: Number,
    default: 0
  },
  savedSearchesCount: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['refresh', 'search-term'])

const isRefreshing = ref(false)

const refresh = async () => {
  if (isRefreshing.value) return

  isRefreshing.value = true
  try {
    await emit('refresh')
  } finally {
    isRefreshing.value = false
  }
}

// Add keyboard shortcuts
if (typeof window !== 'undefined') {
  window.addEventListener('keydown', (event) => {
    // Ctrl + K for search focus
    if (event.ctrlKey && event.key === 'k') {
      event.preventDefault()
      const searchInput = document.querySelector('input[type="text"]')
      if (searchInput) {
        searchInput.focus()
      }
    }

    // Ctrl + F for toggle filters
    if (event.ctrlKey && event.key === 'f') {
      event.preventDefault()
      // Emit toggle filters event or call toggle method
    }

    // Ctrl + R for reset filters (prevent browser refresh)
    if (event.ctrlKey && event.key === 'r') {
      event.preventDefault()
      // Emit reset event or call reset method
    }
  })
}
</script>
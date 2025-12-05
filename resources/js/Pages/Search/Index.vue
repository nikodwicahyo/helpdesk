<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <!-- Header Section -->
      <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <SearchIcon class="h-6 w-6 mr-2 text-indigo-600" />
                {{ t('search.title') }}
              </h1>
              <p class="mt-1 text-sm text-gray-600">
                {{ t('search.subtitle') }}
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <button
                @click="showFilters = !showFilters"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <AdjustmentsHorizontalIcon class="h-4 w-4 mr-2" />
                {{ showFilters ? t('search.hideFilters') : t('search.showFilters') }}
                <span v-if="activeFiltersCount > 0" class="ml-2 px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">
                  {{ activeFiltersCount }}
                </span>
              </button>
              <button
                @click="exportResults"
                :disabled="!hasResults"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <DocumentArrowDownIcon class="h-4 w-4 mr-2" />
                {{ t('search.export') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Filters Sidebar -->
          <div v-show="showFilters" class="lg:col-span-1">
            <SearchFilters
              v-model:filters="filters"
              :filter-options="filterOptions"
              @search="performSearch"
              @reset="resetFilters"
              @save-search="openSaveSearchModal"
            />
          </div>

          <!-- Main Content -->
          <div :class="showFilters ? 'lg:col-span-3' : 'lg:col-span-4'">
            <!-- Search Bar -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  v-model="searchQuery"
                  @input="handleSearchInput"
                  @keydown.enter="performSearch"
                  type="text"
                  class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-lg"
                  :placeholder="t('search.searchPlaceholder')"
                />
                <div class="absolute inset-y-0 right-0 flex items-center">
                  <button
                    @click="clearSearch"
                    v-if="searchQuery"
                    :title="t('search.clearSearch')"
                    class="p-1 mr-2 text-gray-400 hover:text-gray-600"
                  >
                    <XMarkIcon class="h-5 w-5" />
                  </button>
                  <button
                    @click="performSearch"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-r-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    {{ t('search.searchButton') }}
                  </button>
                </div>
              </div>

              <!-- Search Suggestions -->
              <div v-if="showSuggestions && suggestions.length > 0" class="mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50">
                <ul class="py-1">
                  <li
                    v-for="suggestion in suggestions"
                    :key="suggestion"
                    @click="selectSuggestion(suggestion)"
                    class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700"
                  >
                    {{ suggestion }}
                  </li>
                </ul>
              </div>

              <!-- Popular Searches -->
              <div v-if="!searchQuery && popularSearches.length > 0" class="mt-4">
                <p class="text-sm text-gray-600 mb-2">{{ t('search.popularSearches') }}</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="term in popularSearches"
                    :key="term"
                    @click="selectSuggestion(term)"
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200"
                  >
                    {{ term }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Active Filters Display -->
            <div v-if="appliedFilters.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
              <div class="flex items-center justify-between">
                <div class="flex flex-wrap gap-2">
                  <span class="text-sm font-medium text-blue-900">{{ t('search.activeFilters') }}</span>
                  <span
                    v-for="filter in appliedFilters"
                    :key="filter.label"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ filter.label }}: {{ filter.value }}
                  </span>
                </div>
                <button
                  @click="resetFilters"
                  class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                  {{ t('search.clearAll') }}
                </button>
              </div>
            </div>

            <!-- Search Results -->
            <div v-if="searchPerformed" class="bg-white rounded-lg shadow-sm">
              <!-- Results Header -->
              <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-700">
                      <span class="font-medium">{{ pagination.total }}</span>
                      {{ t('search.resultsFound') }}
                      <span v-if="searchQuery"> {{ t('search.resultsFor') }} "{{ searchQuery }}"</span>
                    </p>
                  </div>
                  <div class="flex items-center space-x-4">
                    <select
                      v-model="filters.per_page"
                      @change="performSearch"
                      class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="10">{{ t('search.perPage.10') }}</option>
                      <option value="15">{{ t('search.perPage.15') }}</option>
                      <option value="25">{{ t('search.perPage.25') }}</option>
                      <option value="50">{{ t('search.perPage.50') }}</option>
                    </select>
                    <select
                      v-model="filters.sort"
                      @change="performSearch"
                      class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="created_at">{{ t('search.sortBy.created_at') }}</option>
                      <option value="updated_at">{{ t('search.sortBy.updated_at') }}</option>
                      <option value="priority">{{ t('search.sortBy.priority') }}</option>
                      <option value="status">{{ t('search.sortBy.status') }}</option>
                      <option value="ticket_number">{{ t('search.sortBy.ticket_number') }}</option>
                      <option value="due_date">{{ t('search.sortBy.due_date') }}</option>
                    </select>
                    <button
                      @click="filters.direction = filters.direction === 'asc' ? 'desc' : 'asc'"
                      @change="performSearch"
                      class="p-1 text-gray-400 hover:text-gray-600"
                    >
                      <ArrowsUpDownIcon class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Results List -->
              <div v-if="tickets.data.length > 0" class="divide-y divide-gray-200">
                <SearchResultItem
                  v-for="ticket in tickets.data"
                  :key="ticket.id"
                  :ticket="ticket"
                  :search-query="searchQuery"
                  @view-ticket="viewTicket"
                />
              </div>

              <!-- No Results -->
              <div v-else class="px-6 py-12 text-center">
                <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('search.noResultsFound') }}</h3>
                <p class="mt-1 text-sm text-gray-500">
                  {{ t('search.adjustSearch') }}
                </p>
              </div>

              <!-- Pagination -->
              <div v-if="tickets.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <SimplePagination :data="pagination" :label="t('pagination.tickets')" @page-changed="changePage" />
              </div>
            </div>

            <!-- Initial State -->
            <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center">
              <SearchIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-lg font-medium text-gray-900">{{ t('search.searchForTickets') }}</h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ t('search.enterSearchTerm') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Sidebar Sections -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Search History -->
          <div class="lg:col-span-1">
            <SearchHistory
              :history="searchHistory"
              @search="searchFromHistory"
              @clear="clearHistory"
            />
          </div>

          <!-- Saved Searches -->
          <div class="lg:col-span-1">
            <SavedSearches
              :saved-searches="savedSearches"
              @search="searchFromSaved"
              @delete="deleteSavedSearch"
            />
          </div>

          <!-- Search Statistics -->
          <div class="lg:col-span-1">
            <SearchStatistics @refresh="loadStatistics" />
          </div>
        </div>
      </div>
    </div>

    <!-- Save Search Modal -->
    <SaveSearchModal
      v-if="showSaveSearchModal"
      v-model="showSaveSearchModal"
      @save="handleSaveSearch"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash-es'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchFilters from '@/Components/Search/SearchFilters.vue'
import SearchResultItem from '@/Components/Search/SearchResultItem.vue'
import SearchHistory from '@/Components/Search/SearchHistory.vue'
import SavedSearches from '@/Components/Search/SavedSearches.vue'
import SearchStatistics from '@/Components/Search/SearchStatistics.vue'
import SaveSearchModal from '@/Components/Search/SaveSearchModal.vue'
import SimplePagination from '@/Components/Common/SimplePagination.vue'
import {
  MagnifyingGlassIcon as SearchIcon,
  MagnifyingGlassIcon,
  AdjustmentsHorizontalIcon,
  DocumentArrowDownIcon,
  XMarkIcon,
  ArrowsUpDownIcon
} from '@heroicons/vue/24/outline'

const { t } = useI18n()

const page = usePage()

// State
const searchQuery = ref('')
const searchPerformed = ref(false)
const showFilters = ref(true)
const showSuggestions = ref(false)
const showSaveSearchModal = ref(false)
const isSearching = ref(false)

const tickets = ref({ data: [], links: [] })
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
})

const filters = reactive({
  query: '',
  status: [],
  priority: [],
  aplikasi_id: '',
  kategori_masalah_id: '',
  user_nip: '',
  assigned_teknisi_nip: '',
  date_from: '',
  date_to: '',
  due_date_from: '',
  due_date_to: '',
  is_escalated: null,
  has_attachments: null,
  has_rating: null,
  is_overdue: null,
  is_unassigned: null,
  sort: 'created_at',
  direction: 'desc',
  page: 1,
  per_page: 15,
})

const suggestions = ref([])
const appliedFilters = ref([])
const searchHistory = ref([])
const savedSearches = ref([])
const popularSearches = ref([])

// Props from backend
const filterOptions = ref(page.props.filterOptions || {})
const popularSearchesData = ref(page.props.popularSearches || [])
const searchHistoryData = ref(page.props.searchHistory || [])
const savedSearchesData = ref(page.props.savedSearches || [])

// Computed
const hasResults = computed(() => tickets.value.data.length > 0)
const activeFiltersCount = computed(() => {
  return Object.keys(filters).filter(key => {
    const value = filters[key]
    if (Array.isArray(value)) return value.length > 0
    return value !== '' && value !== null && value !== undefined
  }).length - 3 // Exclude query, page, per_page
})

// Methods
const handleSearchInput = debounce(async () => {
  if (searchQuery.value.length >= 2) {
    await fetchSuggestions()
    showSuggestions.value = true
  } else {
    suggestions.value = []
    showSuggestions.value = false
  }
}, 300)

const fetchSuggestions = async () => {
  try {
    const response = await router.get('/search/suggestions', {
      query: searchQuery.value
    }, {
      preserveState: true,
      preserveScroll: true,
      only: ['suggestions']
    })
    suggestions.value = response.props.suggestions || []
  } catch (error) {
    console.error('Error fetching suggestions:', error)
  }
}

const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion
  filters.query = suggestion
  showSuggestions.value = false
  performSearch()
}

const performSearch = async () => {
  if (isSearching.value) return

  isSearching.value = true
  searchPerformed.value = true

  filters.query = searchQuery.value
  filters.page = 1

  try {
    const response = await router.post('/search/tickets', filters, {
      preserveState: true,
      preserveScroll: true,
      only: ['tickets', 'pagination', 'filters', 'applied_filters_display', 'search_performed']
    })

    tickets.value = response.props.tickets
    pagination.value = response.props.pagination
    appliedFilters.value = response.props.applied_filters_display || []
  } catch (error) {
    console.error('Search error:', error)
  } finally {
    isSearching.value = false
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  filters.query = ''
  suggestions.value = []
  showSuggestions.value = false
}

const resetFilters = () => {
  Object.keys(filters).forEach(key => {
    if (key === 'sort') filters[key] = 'created_at'
    else if (key === 'direction') filters[key] = 'desc'
    else if (key === 'page' || key === 'per_page') return
    else if (Array.isArray(filters[key])) filters[key] = []
    else filters[key] = ''
  })
  searchQuery.value = ''
  searchPerformed.value = false
  tickets.value = { data: [], links: [] }
  appliedFilters.value = []
}

const changePage = (page) => {
  filters.page = page
  performSearch()
}

const viewTicket = (ticketId) => {
  // Navigate based on user role
  const userRole = page.props.auth?.user?.role || 'user'
  const route = userRole === 'admin_helpdesk' || userRole === 'admin_aplikasi'
    ? `admin.tickets.show`
    : userRole === 'teknisi'
    ? 'teknisi.tickets.show'
    : 'user.tickets.show'

  router.get(route, ticketId)
}

const openSaveSearchModal = () => {
  showSaveSearchModal.value = true
}

const handleSaveSearch = async (name) => {
  try {
    await router.post('/search/save', {
      name: name,
      filters: { ...filters }
    }, {
      preserveState: true,
      preserveScroll: true,
      only: ['saved_searches']
    })
    savedSearches.value = page.props.saved_searches || []
    showSaveSearchModal.value = false
  } catch (error) {
    console.error('Error saving search:', error)
  }
}

const deleteSavedSearch = async (searchId) => {
  try {
    await router.delete(`/search/saved/${searchId}`, {
      preserveState: true,
      preserveScroll: true,
      only: ['saved_searches']
    })
    savedSearches.value = page.props.saved_searches || []
  } catch (error) {
    console.error('Error deleting saved search:', error)
  }
}

const searchFromHistory = (historyItem) => {
  Object.assign(filters, historyItem.filters)
  searchQuery.value = historyItem.query || ''
  performSearch()
}

const searchFromSaved = (savedSearch) => {
  Object.assign(filters, savedSearch.filters)
  searchQuery.value = savedSearch.filters.query || ''
  performSearch()
}

const clearHistory = async () => {
  try {
    await router.delete('/search/history', {
      preserveState: true,
      preserveScroll: true,
      only: ['search_history']
    })
    searchHistory.value = []
  } catch (error) {
    console.error('Error clearing history:', error)
  }
}

const exportResults = async () => {
  try {
    const response = await router.post('/search/export', {
      filters: { ...filters },
      format: 'xlsx'
    }, {
      preserveState: true,
      preserveScroll: true
    })

    // Download the file
    const blob = new Blob([JSON.stringify(response.props.data, null, 2)], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${response.props.filename}.xlsx`
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Export error:', error)
  }
}

const loadStatistics = async () => {
  try {
    await router.reload({
      only: ['popular_searches', 'saved_searches', 'search_history'],
      preserveState: true,
      preserveScroll: true
    })
    popularSearches.value = page.props.popular_searches || []
    savedSearches.value = page.props.saved_searches || []
    searchHistory.value = page.props.search_history || []
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

// Lifecycle
onMounted(() => {
  popularSearches.value = popularSearchesData.value
  searchHistory.value = searchHistoryData.value
  savedSearches.value = savedSearchesData.value
})

// Watchers
watch(searchQuery, (newValue) => {
  if (newValue === '') {
    suggestions.value = []
    showSuggestions.value = false
  }
})
</script>

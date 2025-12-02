<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">Filters</h3>
      <button
        @click="resetFilters"
        class="text-sm text-gray-500 hover:text-gray-700"
      >
        Reset all
      </button>
    </div>

    <div class="space-y-6">
      <!-- Status Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <div class="space-y-2">
          <label
            v-for="status in filterOptions.statuses"
            :key="status.value"
            class="flex items-center"
          >
            <input
              v-model="localFilters.status"
              :value="status.value"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">{{ status.label }}</span>
          </label>
        </div>
      </div>

      <!-- Priority Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
        <div class="space-y-2">
          <label
            v-for="priority in filterOptions.priorities"
            :key="priority.value"
            class="flex items-center"
          >
            <input
              v-model="localFilters.priority"
              :value="priority.value"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">{{ priority.label }}</span>
          </label>
        </div>
      </div>

      <!-- Application Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
        <select
          v-model="localFilters.aplikasi_id"
          class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">All Applications</option>
          <option
            v-for="aplikasi in filterOptions.aplikasis"
            :key="aplikasi.id"
            :value="aplikasi.id"
          >
            {{ aplikasi.name }}
          </option>
        </select>
      </div>

      <!-- Category Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Problem Category</label>
        <select
          v-model="localFilters.kategori_masalah_id"
          :disabled="!localFilters.aplikasi_id"
          class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
        >
          <option value="">All Categories</option>
          <option
            v-for="kategori in filteredCategories"
            :key="kategori.id"
            :value="kategori.id"
          >
            {{ kategori.nama_kategori }}
          </option>
        </select>
      </div>

      <!-- User Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
        <select
          v-model="localFilters.user_nip"
          class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">All Users</option>
          <option
            v-for="user in filterOptions.users"
            :key="user.nip"
            :value="user.nip"
          >
            {{ user.name }} ({{ user.email }})
          </option>
        </select>
      </div>

      <!-- Technician Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Technician</label>
        <select
          v-model="localFilters.assigned_teknisi_nip"
          class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">All Technicians</option>
          <option
            v-for="teknisi in filterOptions.teknisis"
            :key="teknisi.nip"
            :value="teknisi.nip"
          >
            {{ teknisi.name }} ({{ teknisi.email }})
          </option>
        </select>
      </div>

      <!-- Date Range Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Created Date Range</label>
        <div class="space-y-2">
          <input
            v-model="localFilters.date_from"
            type="date"
            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="From date"
          />
          <input
            v-model="localFilters.date_to"
            type="date"
            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="To date"
          />
        </div>
      </div>

      <!-- Due Date Range Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Due Date Range</label>
        <div class="space-y-2">
          <input
            v-model="localFilters.due_date_from"
            type="date"
            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="From date"
          />
          <input
            v-model="localFilters.due_date_to"
            type="date"
            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="To date"
          />
        </div>
      </div>

      <!-- Advanced Filters -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Advanced Options</label>
        <div class="space-y-2">
          <label class="flex items-center">
            <input
              v-model="localFilters.is_escalated"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Escalated tickets</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="localFilters.has_attachments"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">With attachments</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="localFilters.has_rating"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">With rating</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="localFilters.is_overdue"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Overdue tickets</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="localFilters.is_unassigned"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Unassigned tickets</span>
          </label>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="pt-4 border-t border-gray-200 space-y-2">
        <button
          @click="applyFilters"
          class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Apply Filters
        </button>
        <button
          @click="$emit('save-search')"
          class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Save Search
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  filterOptions: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters', 'search', 'reset', 'save-search'])

// Create a local copy of filters to avoid direct mutation
const localFilters = ref({ ...props.filters })

// Computed properties
const filteredCategories = computed(() => {
  if (!props.filterOptions.kategori_masalahs || !localFilters.value.aplikasi_id) {
    return []
  }
  return props.filterOptions.kategori_masalahs.filter(
    kategori => kategori.aplikasi_id == localFilters.value.aplikasi_id
  )
})

// Methods
const applyFilters = () => {
  emit('update:filters', { ...localFilters.value })
  emit('search')
}

const resetFilters = () => {
  // Reset all filter values
  Object.keys(localFilters.value).forEach(key => {
    if (key === 'sort') localFilters.value[key] = 'created_at'
    else if (key === 'direction') localFilters.value[key] = 'desc'
    else if (key === 'page' || key === 'per_page') return
    else if (Array.isArray(localFilters.value[key])) localFilters.value[key] = []
    else localFilters.value[key] = ''
  })

  emit('update:filters', { ...localFilters.value })
  emit('reset')
}

// Watch for changes in props.filters to update local copy
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true })

// Watch for application change to reset category
watch(() => localFilters.value.aplikasi_id, () => {
  localFilters.value.kategori_masalah_id = ''
})
</script>
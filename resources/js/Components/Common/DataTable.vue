<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <!-- Table Header with Search and Filters -->
    <div class="p-4 border-b border-gray-200 bg-gray-50">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
        </div>
        <div class="flex items-center gap-2">
          <slot name="filters"></slot>

          <!-- Export Button -->
          <div class="relative" v-if="showExport">
            <button
              @click="showExportMenu = !showExportMenu"
              class="px-4 py-2 text-green-700 bg-green-50 border border-green-300 rounded-lg hover:bg-green-100 transition flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Export Dropdown Menu -->
            <div v-if="showExportMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
              <button
                @click="exportData('excel')"
                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 transition flex items-center gap-2"
              >
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                </svg>
                Export as Excel
              </button>
              <button
                @click="exportData('pdf')"
                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 transition flex items-center gap-2"
              >
                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                </svg>
                Export as PDF
              </button>
            </div>
          </div>

          <button
            v-if="showRefresh"
            @click="$emit('refresh')"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading Skeletons -->
    <div v-if="loading" class="p-6">
      <div class="space-y-4">
        <!-- Header Skeleton -->
        <div class="grid gap-4" :style="{ gridTemplateColumns: `repeat(${columns.length + (hasActions ? 1 : 0)}, 1fr)` }">
          <div v-for="n in columns.length + (hasActions ? 1 : 0)" :key="n" class="h-4 bg-gray-200 rounded animate-pulse"></div>
        </div>

        <!-- Row Skeletons -->
        <div v-for="n in 5" :key="n" class="grid gap-4 border-b border-gray-100 pb-4" :style="{ gridTemplateColumns: `repeat(${columns.length + (hasActions ? 1 : 0)}, 1fr)` }">
          <div v-for="m in columns.length + (hasActions ? 1 : 0)" :key="m" class="h-3 bg-gray-100 rounded animate-pulse"></div>
        </div>
      </div>
    </div>

    <!-- Table with Virtual Scrolling -->
    <div v-else class="relative">
      <!-- Table Header -->
      <div class="sticky top-0 bg-white border-b border-gray-200 z-10">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="column.sortable !== false && sort(column.key)"
              >
                <div class="flex items-center gap-2">
                  {{ column.label }}
                  <span v-if="column.sortable !== false && sortKey === column.key">
                    <svg v-if="sortOrder === 'asc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                  </span>
                </div>
              </th>
              <th v-if="hasActions" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
        </table>
      </div>

      <!-- Virtual Scrolling Container -->
      <div
        v-if="useVirtualScroll"
        ref="scrollContainer"
        class="overflow-auto"
        :style="{ height: virtualHeight + 'px' }"
        @scroll="handleScroll"
      >
        <div :style="{ height: totalHeight + 'px', position: 'relative' }">
          <div :style="{ transform: `translateY(${offsetY}px)` }">
            <table class="min-w-full divide-y divide-gray-200">
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="(row, index) in visibleData"
                  :key="getRowKey(row, index)"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td
                    v-for="column in columns"
                    :key="column.key"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                  >
                    <slot :name="`cell-${column.key}`" :row="row" :value="getNestedValue(row, column.key)">
                      {{ getNestedValue(row, column.key) }}
                    </slot>
                  </td>
                  <td v-if="hasActions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <slot name="actions" :row="row"></slot>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Regular Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="filteredData.length === 0">
              <td :colspan="columns.length + (hasActions ? 1 : 0)" class="px-6 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                  </svg>
                  <p>No data available</p>
                </div>
              </td>
            </tr>
            <tr
              v-for="(row, index) in paginatedData"
              :key="index"
              class="hover:bg-gray-50 transition-colors"
            >
              <td
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
              >
                <slot :name="`cell-${column.key}`" :row="row" :value="getNestedValue(row, column.key)">
                  {{ getNestedValue(row, column.key) }}
                </slot>
              </td>
              <td v-if="hasActions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <slot name="actions" :row="row"></slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && filteredData.length > perPage" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, filteredData.length) }} of {{ filteredData.length }} results
        </div>
        <div class="flex gap-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition"
          >
            Previous
          </button>
          <button
            v-for="page in totalPages"
            :key="page"
            @click="currentPage = page"
            :class="[
              'px-3 py-1 border rounded-lg transition',
              currentPage === page
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'border-gray-300 hover:bg-gray-100'
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    required: true
  },
  perPage: {
    type: Number,
    default: 10
  },
  showPagination: {
    type: Boolean,
    default: true
  },
  showRefresh: {
    type: Boolean,
    default: false
  },
  hasActions: {
    type: Boolean,
    default: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  showExport: {
    type: Boolean,
    default: false
  },
  useVirtualScroll: {
    type: Boolean,
    default: false
  },
  virtualHeight: {
    type: Number,
    default: 400
  },
  rowKey: {
    type: String,
    default: 'id'
  }
});

const emit = defineEmits(['refresh', 'export', 'search']);

const searchQuery = ref('');
const sortKey = ref('');
const sortOrder = ref('asc');
const currentPage = ref(1);
const showExportMenu = ref(false);

// Virtual Scrolling State
const scrollContainer = ref(null);
const scrollTop = ref(0);
const itemHeight = 48; // Approximate row height
const bufferSize = 5;

const filteredData = computed(() => {
  let data = props.data;

  // Search filter
  if (searchQuery.value) {
    data = data.filter(row => {
      return props.columns.some(column => {
        const value = getNestedValue(row, column.key);
        return String(value).toLowerCase().includes(searchQuery.value.toLowerCase());
      });
    });
  }

  // Sort
  if (sortKey.value) {
    data = [...data].sort((a, b) => {
      const aVal = getNestedValue(a, sortKey.value);
      const bVal = getNestedValue(b, sortKey.value);
      
      if (sortOrder.value === 'asc') {
        return aVal > bVal ? 1 : -1;
      } else {
        return aVal < bVal ? 1 : -1;
      }
    });
  }

  return data;
});

const paginatedData = computed(() => {
  if (!props.showPagination) return filteredData.value;
  
  const start = (currentPage.value - 1) * props.perPage;
  const end = start + props.perPage;
  return filteredData.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredData.value.length / props.perPage);
});

const sort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
};

const getNestedValue = (obj, path) => {
  return path.split('.').reduce((current, key) => current?.[key], obj);
};

// Virtual Scrolling Computeds
const totalHeight = computed(() => {
  return filteredData.value.length * itemHeight;
});

const visibleCount = computed(() => {
  return Math.ceil(props.virtualHeight / itemHeight) + bufferSize * 2;
});

const startIndex = computed(() => {
  return Math.max(0, Math.floor(scrollTop.value / itemHeight) - bufferSize);
});

const endIndex = computed(() => {
  return Math.min(filteredData.value.length, startIndex.value + visibleCount.value);
});

const offsetY = computed(() => {
  return startIndex.value * itemHeight;
});

const visibleData = computed(() => {
  return filteredData.value.slice(startIndex.value, endIndex.value);
});

// Virtual Scrolling Methods
const handleScroll = (event) => {
  scrollTop.value = event.target.scrollTop;
};

const getRowKey = (row, index) => {
  return row[props.rowKey] || index;
};

// Export Methods
const exportData = (format) => {
  showExportMenu.value = false;
  emit('export', {
    format,
    data: props.useVirtualScroll ? filteredData.value : paginatedData.value,
    columns: props.columns
  });
};

// Close export menu when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showExportMenu.value = false;
  }
};

// Watch search query and emit event
watch(searchQuery, (newValue) => {
  emit('search', newValue);
  // Reset to first page on search
  currentPage.value = 1;
});

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Advanced Filters</h3>
            <div class="flex items-center space-x-2">
                <button
                    @click="saveCurrentFilter"
                    :disabled="!hasActiveFilters"
                    class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Save Filter
                </button>
                <button
                    @click="clearAllFilters"
                    class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 transition"
                >
                    Clear All
                </button>
                <button
                    @click="showFilters = !showFilters"
                    class="text-gray-600 hover:text-gray-800 transition"
                >
                    <svg v-if="showFilters" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Filter Content -->
        <div v-show="showFilters" class="space-y-6">
            <!-- Text Search -->
            <div v-if="config.showSearch !== false">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <div class="relative">
                    <input
                        v-model="filters.search"
                        @input="debouncedUpdate"
                        type="text"
                        placeholder="Search across all fields..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Date Range -->
            <div v-if="config.showDateRange !== false">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <select
                            v-model="dateRangePreset"
                            @change="applyDatePreset"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">Custom Range</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="last_week">Last Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="last_quarter">Last Quarter</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                    <div>
                        <input
                            v-model="filters.date_from"
                            @input="debouncedUpdate"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="From"
                        >
                    </div>
                    <div>
                        <input
                            v-model="filters.date_to"
                            @input="debouncedUpdate"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="To"
                        >
                    </div>
                </div>
            </div>

            <!-- Status Filter -->
            <div v-if="config.showStatus && config.statusOptions">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="option in config.statusOptions"
                        :key="option.value"
                        class="flex items-center px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                        :class="{ 'bg-indigo-50 border-indigo-500': filters.status?.includes(option.value) }"
                    >
                        <input
                            v-model="filters.status"
                            :value="option.value"
                            type="checkbox"
                            class="sr-only"
                            @change="updateFilters"
                        >
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(option.value)]">
                            {{ option.label }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Priority Filter -->
            <div v-if="config.showPriority && config.priorityOptions">
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="option in config.priorityOptions"
                        :key="option.value"
                        class="flex items-center px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                        :class="{ 'bg-indigo-50 border-indigo-500': filters.priority?.includes(option.value) }"
                    >
                        <input
                            v-model="filters.priority"
                            :value="option.value"
                            type="checkbox"
                            class="sr-only"
                            @change="updateFilters"
                        >
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPriorityColor(option.value)]">
                            {{ option.label }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Application Filter -->
            <div v-if="config.showApplication && config.applicationOptions">
                <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                <select
                    v-model="filters.aplikasi_id"
                    @change="updateFilters"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                    <option value="">All Applications</option>
                    <option v-for="app in config.applicationOptions" :key="app.id" :value="app.id">
                        {{ app.name }}
                    </option>
                </select>
            </div>

            <!-- Category Filter -->
            <div v-if="config.showCategory && config.categoryOptions">
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select
                    v-model="filters.kategori_id"
                    @change="updateFilters"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                    <option value="">All Categories</option>
                    <option v-for="category in config.categoryOptions" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <!-- Multi-Select Filter -->
            <div v-for="filterConfig in config.multiSelectFilters" :key="filterConfig.key">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ filterConfig.label }}</label>
                <div class="relative">
                    <button
                        @click="toggleMultiSelectDropdown(filterConfig.key)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-left flex items-center justify-between hover:bg-gray-50 transition"
                    >
                        <span class="text-gray-700">
                            {{ getMultiSelectLabel(filterConfig.key) }}
                        </span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown -->
                    <div
                        v-if="openDropdowns[filterConfig.key]"
                        class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto"
                    >
                        <div class="p-2">
                            <label
                                v-for="option in filterConfig.options"
                                :key="option.value"
                                class="flex items-center px-3 py-2 hover:bg-gray-50 rounded cursor-pointer"
                            >
                                <input
                                    :checked="filters[filterConfig.key]?.includes(option.value)"
                                    @change="toggleMultiSelectOption(filterConfig.key, option.value)"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-2"
                                >
                                <span class="text-sm text-gray-700">{{ option.label }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Filters -->
            <div v-for="filterConfig in config.customFilters" :key="filterConfig.key">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ filterConfig.label }}</label>
                <input
                    v-if="filterConfig.type === 'text'"
                    v-model="filters[filterConfig.key]"
                    @input="debouncedUpdate"
                    type="text"
                    :placeholder="filterConfig.placeholder"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                <select
                    v-else-if="filterConfig.type === 'select'"
                    v-model="filters[filterConfig.key]"
                    @change="updateFilters"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                    <option value="">{{ filterConfig.placeholder || 'Select...' }}</option>
                    <option v-for="option in filterConfig.options" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <input
                    v-else-if="filterConfig.type === 'number'"
                    v-model.number="filters[filterConfig.key]"
                    @input="debouncedUpdate"
                    type="number"
                    :placeholder="filterConfig.placeholder"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
            </div>
        </div>

        <!-- Active Filters Summary -->
        <div v-if="activeFilters.length > 0" class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-gray-900">Active Filters</h4>
                <button
                    @click="clearAllFilters"
                    class="text-xs text-gray-500 hover:text-gray-700 transition"
                >
                    Clear All
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="filter in activeFilters"
                    :key="filter.key"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800"
                >
                    {{ filter.label }}: {{ filter.value }}
                    <button
                        @click="removeFilter(filter.key)"
                        class="ml-2 text-indigo-600 hover:text-indigo-800"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </span>
            </div>
        </div>

        <!-- Saved Filters -->
        <div v-if="savedFilters.length > 0" class="mt-6 pt-6 border-t border-gray-200">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Saved Filters</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                    v-for="savedFilter in savedFilters"
                    :key="savedFilter.id"
                    class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ savedFilter.name }}</p>
                        <p class="text-xs text-gray-500">{{ formatDate(savedFilter.created_at) }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="applySavedFilter(savedFilter)"
                            class="text-indigo-600 hover:text-indigo-800"
                            title="Apply Filter"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </button>
                        <button
                            @click="deleteSavedFilter(savedFilter.id)"
                            class="text-red-600 hover:text-red-800"
                            title="Delete Filter"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Filter Modal -->
    <div v-if="showSaveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Save Filter</h3>
            <input
                v-model="newFilterName"
                type="text"
                placeholder="Enter filter name..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent mb-4"
            >
            <div class="flex justify-end space-x-3">
                <button
                    @click="showSaveModal = false"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 transition"
                >
                    Cancel
                </button>
                <button
                    @click="confirmSaveFilter"
                    :disabled="!newFilterName.trim()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    config: {
        type: Object,
        default: () => ({})
    },
    modelValue: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const showFilters = ref(true);
const dateRangePreset = ref('');
const openDropdowns = ref({});
const showSaveModal = ref(false);
const newFilterName = ref('');

const filters = ref({ ...props.modelValue });

// Initialize filter structure
const initializeFilters = () => {
    if (props.config.showStatus) {
        filters.value.status = filters.value.status || [];
    }
    if (props.config.showPriority) {
        filters.value.priority = filters.value.priority || [];
    }
    if (props.config.multiSelectFilters) {
        props.config.multiSelectFilters.forEach(filter => {
            filters.value[filter.key] = filters.value[filter.key] || [];
        });
    }
};

initializeFilters();

const hasActiveFilters = computed(() => {
    return Object.keys(filters.value).some(key => {
        const value = filters.value[key];
        return value && (
            (Array.isArray(value) && value.length > 0) ||
            (!Array.isArray(value) && value !== '')
        );
    });
});

const activeFilters = computed(() => {
    const active = [];

    Object.keys(filters.value).forEach(key => {
        const value = filters.value[key];
        if (value && ((Array.isArray(value) && value.length > 0) || (!Array.isArray(value) && value !== ''))) {
            let label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            let displayValue = value;

            if (Array.isArray(value)) {
                if (key === 'status' && props.config.statusOptions) {
                    displayValue = value.map(v => {
                        const option = props.config.statusOptions.find(opt => opt.value === v);
                        return option ? option.label : v;
                    }).join(', ');
                } else if (key === 'priority' && props.config.priorityOptions) {
                    displayValue = value.map(v => {
                        const option = props.config.priorityOptions.find(opt => opt.value === v);
                        return option ? option.label : v;
                    }).join(', ');
                } else {
                    displayValue = `${value.length} selected`;
                }
            }

            active.push({ key, label, value: displayValue });
        }
    });

    return active;
});

const debouncedUpdate = debounce(() => {
    updateFilters();
}, 500);

const updateFilters = () => {
    emit('update:modelValue', { ...filters.value });
};

const clearAllFilters = () => {
    Object.keys(filters.value).forEach(key => {
        if (Array.isArray(filters.value[key])) {
            filters.value[key] = [];
        } else {
            filters.value[key] = '';
        }
    });
    dateRangePreset.value = '';
    updateFilters();
};

const removeFilter = (key) => {
    if (Array.isArray(filters.value[key])) {
        filters.value[key] = [];
    } else {
        filters.value[key] = '';
    }
    updateFilters();
};

const applyDatePreset = () => {
    const today = new Date();
    let from = new Date();
    let to = new Date();

    switch (dateRangePreset.value) {
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

    filters.value.date_from = from.toISOString().split('T')[0];
    filters.value.date_to = to.toISOString().split('T')[0];
    updateFilters();
};

const toggleMultiSelectDropdown = (key) => {
    openDropdowns.value[key] = !openDropdowns.value[key];
    // Close other dropdowns
    Object.keys(openDropdowns.value).forEach(k => {
        if (k !== key) {
            openDropdowns.value[k] = false;
        }
    });
};

const toggleMultiSelectOption = (key, value) => {
    if (!filters.value[key].includes(value)) {
        filters.value[key].push(value);
    } else {
        filters.value[key] = filters.value[key].filter(v => v !== value);
    }
    updateFilters();
};

const getMultiSelectLabel = (key) => {
    const filterConfig = props.config.multiSelectFilters.find(f => f.key === key);
    if (!filterConfig) return 'Select...';

    const selected = filters.value[key] || [];
    if (selected.length === 0) return filterConfig.placeholder || 'Select...';
    if (selected.length === 1) {
        const option = filterConfig.options.find(opt => opt.value === selected[0]);
        return option ? option.label : selected[0];
    }
    return `${selected.length} selected`;
};

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-yellow-100 text-yellow-800',
        assigned: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-indigo-100 text-indigo-800',
        waiting_response: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
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

// Saved Filters (mock implementation - should come from props or API)
const savedFilters = ref([]);

const saveCurrentFilter = () => {
    showSaveModal.value = true;
};

const confirmSaveFilter = () => {
    if (!newFilterName.value.trim()) return;

    const newFilter = {
        id: Date.now(),
        name: newFilterName.value.trim(),
        filters: { ...filters.value },
        created_at: new Date().toISOString()
    };

    savedFilters.value.push(newFilter);
    newFilterName.value = '';
    showSaveModal.value = false;
};

const applySavedFilter = (savedFilter) => {
    filters.value = { ...savedFilter.filters };
    updateFilters();
};

const deleteSavedFilter = (filterId) => {
    if (confirm('Are you sure you want to delete this saved filter?')) {
        savedFilters.value = savedFilters.value.filter(f => f.id !== filterId);
    }
};

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
    filters.value = { ...newValue };
}, { deep: true });

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        Object.keys(openDropdowns.value).forEach(key => {
            openDropdowns.value[key] = false;
        });
    }
};

// Initialize click outside listener
if (typeof window !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
}
</script>
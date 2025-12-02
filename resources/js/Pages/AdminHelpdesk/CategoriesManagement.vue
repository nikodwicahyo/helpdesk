<template>
    <AppLayout role="admin">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Categories Management</h1>
                    <p class="text-gray-600 mt-1">System-wide category oversight and management</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="showCreateModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Category
                    </button>
                    <button
                        @click="exportCategories"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </template>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <StatCard
                title="Total Categories"
                :value="stats.total_categories"
                icon="📁"
                color="blue"
            />
            <StatCard
                title="Active Categories"
                :value="stats.active_categories"
                icon="✅"
                color="green"
            />
            <StatCard
                title="Total Applications"
                :value="stats.total_applications"
                icon="💻"
                color="purple"
            />
            <StatCard
                title="Categories with Tickets"
                :value="stats.categories_with_tickets"
                icon="🎫"
                color="yellow"
            />
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input
                        v-model="filters.search"
                        @input="applyFilters"
                        type="text"
                        placeholder="Search categories..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                    <select
                        v-model="filters.aplikasi_id"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Applications</option>
                        <option v-for="app in filterOptions.applications" :key="app.value" :value="app.value">
                            {{ app.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Statuses</option>
                        <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <div class="flex space-x-2">
                        <select
                            v-model="filters.sort_by"
                            @change="applyFilters"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="name">Name</option>
                            <option value="created_at">Created Date</option>
                            <option value="total_tickets">Total Tickets</option>
                            <option value="application">Application</option>
                        </select>
                        <button
                            @click="toggleSortDirection"
                            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="filters.sort_direction === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selectedCategories.length > 0" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-sm font-medium text-indigo-800">
                        {{ selectedCategories.length }} categories selected
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <button
                        @click="bulkAction('activate')"
                        class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700"
                    >
                        Activate
                    </button>
                    <button
                        @click="bulkAction('deactivate')"
                        class="text-sm bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700"
                    >
                        Deactivate
                    </button>
                    <button
                        @click="bulkAction('delete')"
                        class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
                    >
                        Delete
                    </button>
                    <button
                        @click="selectedCategories = []"
                        class="text-sm bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700"
                    >
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Categories Overview</h2>
                <label class="flex items-center">
                    <input
                        v-model="selectAll"
                        @change="toggleSelectAll"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <span class="ml-2 text-sm text-gray-700">Select All</span>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input
                                    v-model="selectAll"
                                    @change="toggleSelectAll"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Application
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Admin
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tickets
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Avg Resolution Time
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="categories.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">No categories found</p>
                            </td>
                        </tr>
                        <tr v-for="category in categories.data" :key="category.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    v-model="selectedCategories"
                                    :value="category.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                                    <div v-if="category.description" class="text-xs text-gray-500 mt-1 max-w-xs truncate">{{ category.description }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="category.aplikasi">
                                    <div class="text-sm font-medium text-gray-900">{{ category.aplikasi.name }}</div>
                                    <div class="text-xs text-gray-500">{{ category.aplikasi.code }}</div>
                                    <div class="text-xs text-gray-400">({{ category.aplikasi.status }})</div>
                                </div>
                                <span v-else class="text-sm text-gray-400">No application</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="category.admin_aplikasi" class="text-sm text-gray-900">{{ category.admin_aplikasi.name }}</span>
                                <span v-else class="text-sm text-gray-400">Not assigned</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusBadgeClass(category.status)">
                                    {{ category.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <div class="font-medium">{{ category.total_tickets }}</div>
                                    <div class="text-xs text-gray-500">
                                        <span class="text-yellow-600">{{ category.open_tickets }} open</span> /
                                        <span class="text-blue-600">{{ category.in_progress_tickets }} in progress</span> /
                                        <span class="text-green-600">{{ category.resolved_tickets }} resolved</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="category.avg_resolution_time" class="text-sm text-gray-900">
                                    {{ category.avg_resolution_time }}h
                                </span>
                                <span v-else class="text-sm text-gray-400">N/A</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ category.formatted_created_at }}</div>
                                <div v-if="category.last_ticket_activity" class="text-xs text-gray-500">
                                    Active: {{ category.last_ticket_activity }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button
                                        @click="editCategory(category)"
                                        class="text-indigo-600 hover:text-indigo-800"
                                        title="Edit Category"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteCategory(category)"
                                        class="text-red-600 hover:text-red-800"
                                        title="Delete Category"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                <SimplePagination
                    :data="categories"
                    label="category"
                    @page-changed="handlePageChange"
                />
            </div>
        </div>

        <!-- Create/Edit Category Modal -->
        <CategoryModal
            v-if="showCreateModal || showEditModal"
            :mode="showCreateModal ? 'create' : 'edit'"
            :category="editingCategory"
            :applications="applications"
            @close="closeModal"
            @saved="handleCategorySaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import SimplePagination from '@/Components/Common/SimplePagination.vue';
import CategoryModal from '@/Components/Modals/CategoryModal.vue';

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({}),
    },
    applications: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const filters = ref({ ...props.filters });
const selectedCategories = ref([]);
const selectAll = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCategory = ref(null);

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800',
        inactive: 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800',
    };
    return classes[status] || classes.inactive;
};

const applyFilters = () => {
    router.get(route('admin.categories.index'), filters.value, {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleSortDirection = () => {
    filters.value.sort_direction = filters.value.sort_direction === 'asc' ? 'desc' : 'asc';
    applyFilters();
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedCategories.value = props.categories.data.map(cat => cat.id);
    } else {
        selectedCategories.value = [];
    }
};

const editCategory = (category) => {
    editingCategory.value = { ...category };
    showEditModal.value = true;
};

const deleteCategory = async (category) => {
    if (category.total_tickets > 0) {
        alert(`Cannot delete category "${category.name}" because it has ${category.total_tickets} tickets.`);
        return;
    }

    if (!confirm(`Are you sure you want to delete category "${category.name}"?`)) {
        return;
    }

    try {
        const response = await fetch(route('admin.categories.destroy', category.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        const data = await response.json();

        if (data.success) {
            router.reload({ preserveScroll: true });
        } else {
            alert('Failed to delete category: ' + data.errors.join(', '));
        }
    } catch (error) {
        alert('Error deleting category: ' + error.message);
    }
};

const bulkAction = async (action) => {
    if (selectedCategories.value.length === 0) return;

    const actionText = action === 'delete' ? 'delete' : `${action} the selected`;
    if (!confirm(`Are you sure you want to ${actionText} ${selectedCategories.value.length} categories?`)) {
        return;
    }

    try {
        const response = await fetch(route('admin.categories.bulk-action'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                action: action,
                category_ids: selectedCategories.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            selectedCategories.value = [];
            selectAll.value = false;
            router.reload({ preserveScroll: true });
        } else {
            alert('Failed to perform bulk action: ' + data.errors.join(', '));
        }
    } catch (error) {
        alert('Error performing bulk action: ' + error.message);
    }
};

const exportCategories = async () => {
    try {
        // Build export parameters with all current filters
        const exportParams = { ...filters.value };

        // Remove pagination-related params from export
        delete exportParams.page;

        const params = new URLSearchParams(exportParams);
        const exportUrl = route('admin.categories.export') + '?' + params.toString();

        // Create download link directly (more reliable for CSV downloads)
        const link = document.createElement('a');
        link.href = exportUrl;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();

        // Clean up after a short delay
        setTimeout(() => {
            document.body.removeChild(link);
        }, 100);

        // Show success notification
        const successDiv = document.createElement('div');
        successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
        successDiv.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Export started! Download will begin shortly.
        `;
        document.body.appendChild(successDiv);

        setTimeout(() => {
            if (document.body.contains(successDiv)) {
                document.body.removeChild(successDiv);
            }
        }, 3000);

    } catch (error) {
        console.error('Export failed:', error);

        // Show user-friendly error message
        const errorMessage = error.message || 'Unknown error occurred during export';

        // Create a nice error notification
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
        errorDiv.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Export failed: ${errorMessage}
        `;
        document.body.appendChild(errorDiv);

        // Remove error message after 5 seconds
        setTimeout(() => {
            if (document.body.contains(errorDiv)) {
                document.body.removeChild(errorDiv);
            }
        }, 5000);
    }
};

const handlePageChange = (page) => {
    filters.value.page = page;
    applyFilters();
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingCategory.value = null;
};

const handleCategorySaved = () => {
    closeModal();
    router.reload({ preserveScroll: true });
};

// Watch for changes in categories data to update selectAll state
watch(() => props.categories.data, (newData) => {
    if (selectedCategories.value.length === newData.length && newData.length > 0) {
        selectAll.value = true;
    } else {
        selectAll.value = false;
    }
});

watch(selectedCategories, (newSelected) => {
    if (newSelected.length === props.categories.data.length && props.categories.data.length > 0) {
        selectAll.value = true;
    } else {
        selectAll.value = false;
    }
});
</script>

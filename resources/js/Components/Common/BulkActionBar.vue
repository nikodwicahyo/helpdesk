<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
    >
        <div v-if="show && selectedItems.length > 0" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Selection Info -->
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-indigo-800 font-medium">
                            {{ selectedItems.length }} {{ selectedItems.length === 1 ? 'item' : 'items' }} selected
                        </span>
                    </div>

                    <!-- Selected Items Preview (if enabled) -->
                    <div v-if="showPreview && selectedItems.length <= 3" class="flex items-center space-x-2">
                        <div
                            v-for="(item, index) in selectedItems.slice(0, 3)"
                            :key="getItemKey(item)"
                            class="flex items-center space-x-2 px-3 py-1 bg-white rounded-full border border-indigo-300"
                        >
                            <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-indigo-600">
                                    {{ getItemInitial(item) }}
                                </span>
                            </div>
                            <span class="text-sm text-gray-700 truncate max-w-32">
                                {{ getItemLabel(item) }}
                            </span>
                        </div>
                    </div>
                    <div v-else-if="showPreview && selectedItems.length > 3" class="flex items-center space-x-2">
                        <div class="flex items-center space-x-2 px-3 py-1 bg-white rounded-full border border-indigo-300">
                            <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-indigo-600">+</span>
                            </div>
                            <span class="text-sm text-gray-700">
                                {{ selectedItems.length - 3 }} more
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    <!-- Bulk Action Selector -->
                    <select
                        v-model="selectedAction"
                        class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    >
                        <option value="">Choose Action</option>
                        <option
                            v-for="action in availableActions"
                            :key="action.key"
                            :value="action.key"
                            :disabled="action.disabled"
                        >
                            {{ action.label }}
                        </option>
                    </select>

                    <!-- Action-specific Options -->
                    <div v-if="showActionOptions" class="flex items-center space-x-2">
                        <!-- Status Selector -->
                        <select
                            v-if="selectedAction === 'update_status'"
                            v-model="actionData.status"
                            class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        >
                            <option value="">Select Status</option>
                            <option
                                v-for="status in statusOptions"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>

                        <!-- Priority Selector -->
                        <select
                            v-if="selectedAction === 'update_priority'"
                            v-model="actionData.priority"
                            class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        >
                            <option value="">Select Priority</option>
                            <option
                                v-for="priority in priorityOptions"
                                :key="priority.value"
                                :value="priority.value"
                            >
                                {{ priority.label }}
                            </option>
                        </select>

                        <!-- Assignee Selector -->
                        <select
                            v-if="selectedAction === 'assign'"
                            v-model="actionData.assignee"
                            class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        >
                            <option value="">Select Assignee</option>
                            <option
                                v-for="assignee in assigneeOptions"
                                :key="assignee.id"
                                :value="assignee.id"
                            >
                                {{ assignee.name }}
                            </option>
                        </select>

                        <!-- Category Selector -->
                        <select
                            v-if="selectedAction === 'update_category'"
                            v-model="actionData.category"
                            class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        >
                            <option value="">Select Category</option>
                            <option
                                v-for="category in categoryOptions"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>

                        <!-- Custom Input -->
                        <input
                            v-if="selectedAction === 'custom' && customActionConfig"
                            v-model="actionData.customValue"
                            :type="customActionConfig.type || 'text'"
                            :placeholder="customActionConfig.placeholder"
                            class="px-4 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        >
                    </div>

                    <!-- Execute Button -->
                    <button
                        @click="executeBulkAction"
                        :disabled="!selectedAction || !canExecuteAction"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                    >
                        <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="loading">Processing...</span>
                        <span v-else>Execute</span>
                    </button>

                    <!-- Cancel Button -->
                    <button
                        @click="cancelSelection"
                        class="text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Progress Bar (if showing progress) -->
            <div v-if="showProgress && progress !== null" class="mt-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-indigo-700 font-medium">{{ progressText }}</span>
                    <span class="text-sm text-indigo-600">{{ progress }}%</span>
                </div>
                <div class="w-full bg-indigo-200 rounded-full h-2">
                    <div
                        class="bg-indigo-600 h-2 rounded-full transition-all duration-300 ease-out"
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Results Summary (if showing results) -->
            <div v-if="results" class="mt-4 p-3 bg-indigo-100 rounded-lg">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-indigo-800">
                        {{ results.successful }} successful, {{ results.failed }} failed
                    </span>
                </div>
                <div v-if="results.errors && results.errors.length > 0" class="mt-2">
                    <div class="text-sm text-red-600">
                        <p class="font-medium">Errors:</p>
                        <ul class="mt-1 space-y-1">
                            <li v-for="(error, index) in results.errors" :key="index">• {{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: true
    },
    selectedItems: {
        type: Array,
        default: () => []
    },
    availableActions: {
        type: Array,
        default: () => []
    },
    statusOptions: {
        type: Array,
        default: () => []
    },
    priorityOptions: {
        type: Array,
        default: () => []
    },
    assigneeOptions: {
        type: Array,
        default: () => []
    },
    categoryOptions: {
        type: Array,
        default: () => []
    },
    showPreview: {
        type: Boolean,
        default: true
    },
    showProgress: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    progress: {
        type: Number,
        default: null
    },
    progressText: {
        type: String,
        default: 'Processing...'
    },
    results: {
        type: Object,
        default: null
    },
    itemKeyField: {
        type: String,
        default: 'id'
    },
    itemLabelField: {
        type: String,
        default: 'name'
    },
    customActionConfig: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['execute', 'cancel']);

const selectedAction = ref('');
const actionData = ref({});

const showActionOptions = computed(() => {
    return selectedAction.value && (
        selectedAction.value === 'update_status' ||
        selectedAction.value === 'update_priority' ||
        selectedAction.value === 'assign' ||
        selectedAction.value === 'update_category' ||
        selectedAction.value === 'custom'
    );
});

const canExecuteAction = computed(() => {
    if (!selectedAction.value) return false;

    switch (selectedAction.value) {
        case 'update_status':
            return actionData.value.status;
        case 'update_priority':
            return actionData.value.priority;
        case 'assign':
            return actionData.value.assignee;
        case 'update_category':
            return actionData.value.category;
        case 'custom':
            return props.customActionConfig ?
                (props.customActionConfig.required ? actionData.value.customValue : true) : true;
        default:
            return true;
    }
});

const getItemKey = (item) => {
    return item[props.itemKeyField] || item.id || Math.random().toString(36);
};

const getItemLabel = (item) => {
    return item[props.itemLabelField] || item.name || item.title || 'Unknown';
};

const getItemInitial = (item) => {
    const label = getItemLabel(item);
    return label ? label.charAt(0).toUpperCase() : '?';
};

const executeBulkAction = () => {
    if (!canExecuteAction.value) return;

    const payload = {
        action: selectedAction.value,
        items: props.selectedItems,
        data: actionData.value
    };

    emit('execute', payload);
};

const cancelSelection = () => {
    selectedAction.value = '';
    actionData.value = {};
    emit('cancel');
};

// Reset action data when action changes
watch(selectedAction, () => {
    actionData.value = {};
});

// Watch for external loading changes
watch(() => props.loading, (newLoading) => {
    if (!newLoading && props.results) {
        // Auto-clear after showing results for 3 seconds
        setTimeout(() => {
            selectedAction.value = '';
            actionData.value = {};
        }, 3000);
    }
});
</script>
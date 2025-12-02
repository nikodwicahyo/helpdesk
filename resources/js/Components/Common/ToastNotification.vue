<template>
    <teleport to="body">
        <transition-group
            tag="div"
            class="fixed top-4 right-4 z-50 space-y-2"
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            move-class="transition duration-300"
        >
            <div
                v-for="toast in visibleToasts"
                :key="toast.id"
                :class="[
                    'max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto overflow-hidden',
                    getToastClasses(toast.type)
                ]"
                @click="handleToastClick(toast)"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <component
                                :is="getToastIcon(toast.type)"
                                class="h-6 w-6"
                                :class="getIconColor(toast.type)"
                            />
                        </div>

                        <!-- Content -->
                        <div class="ml-3 w-0 flex-1">
                            <p
                                v-if="toast.title"
                                class="text-sm font-medium"
                                :class="getTitleColor(toast.type)"
                            >
                                {{ toast.title }}
                            </p>
                            <p
                                v-if="toast.message"
                                class="text-sm mt-1"
                                :class="getMessageColor(toast.type)"
                            >
                                {{ toast.message }}
                            </p>

                            <!-- Progress Bar (if auto-dismiss) -->
                            <div
                                v-if="toast.autoDismiss && toast.duration"
                                class="mt-2 w-full bg-gray-200 rounded-full h-1"
                            >
                                <div
                                    class="h-1 rounded-full transition-all ease-linear"
                                    :class="getProgressBarColor(toast.type)"
                                    :style="{
                                        width: toast.progress + '%',
                                        transitionDuration: (toast.duration / 1000) + 's'
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- Close Button -->
                        <div class="ml-4 flex-shrink-0 flex">
                            <button
                                @click.stop="removeToast(toast.id)"
                                class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition ease-in-out duration-150"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="toast.actions && toast.actions.length > 0" class="mt-3 flex space-x-2">
                        <button
                            v-for="(action, index) in toast.actions"
                            :key="index"
                            @click.stop="handleActionClick(toast, action)"
                            class="flex-1 px-3 py-1 text-xs font-medium rounded transition-colors"
                            :class="getActionClasses(action)"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>

                <!-- Border Accent -->
                <div
                    v-if="toast.showBorder !== false"
                    class="h-1 w-full"
                    :class="getBorderAccentColor(toast.type)"
                ></div>
            </div>
        </transition-group>
    </teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    maxToasts: {
        type: Number,
        default: 5
    },
    position: {
        type: String,
        default: 'top-right',
        validator: (value) => ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'top-center', 'bottom-center'].includes(value)
    }
});

const toasts = ref([]);
let toastIdCounter = 0;
let intervals = {};

const visibleToasts = computed(() => {
    return toasts.value.slice(-props.maxToasts);
});

const showToast = (options) => {
    const id = ++toastIdCounter;
    const toast = {
        id,
        type: options.type || 'info',
        title: options.title,
        message: options.message,
        duration: options.duration || 5000,
        autoDismiss: options.autoDismiss !== false,
        persistent: options.persistent || false,
        actions: options.actions || [],
        showBorder: options.showBorder !== false,
        progress: 0,
        ...options
    };

    toasts.value.push(toast);

    // Auto-dismiss functionality
    if (toast.autoDismiss && !toast.persistent && toast.duration > 0) {
        let elapsed = 0;
        const interval = 100; // Update every 100ms

        intervals[id] = setInterval(() => {
            elapsed += interval;
            toast.progress = Math.min(100, (elapsed / toast.duration) * 100);

            if (elapsed >= toast.duration) {
                removeToast(id);
            }
        }, interval);
    }

    return id;
};

const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id);
    if (index > -1) {
        toasts.value.splice(index, 1);
    }

    // Clear interval
    if (intervals[id]) {
        clearInterval(intervals[id]);
        delete intervals[id];
    }
};

const clearAllToasts = () => {
    toasts.value = [];

    // Clear all intervals
    Object.keys(intervals).forEach(id => {
        clearInterval(intervals[id]);
    });
    intervals = {};
};

const handleToastClick = (toast) => {
    if (toast.onClick) {
        toast.onClick(toast);
    }
};

const handleActionClick = (toast, action) => {
    if (action.onClick) {
        action.onClick(toast);
    }

    if (action.dismissOnClick !== false) {
        removeToast(toast.id);
    }
};

// Toast Types
const success = (options) => showToast({ ...options, type: 'success' });
const error = (options) => showToast({ ...options, type: 'error', duration: 8000 });
const warning = (options) => showToast({ ...options, type: 'warning', duration: 6000 });
const info = (options) => showToast({ ...options, type: 'info' });

// Style helpers
const getToastClasses = (type) => {
    const classes = {
        success: 'border-l-4 border-green-500',
        error: 'border-l-4 border-red-500',
        warning: 'border-l-4 border-yellow-500',
        info: 'border-l-4 border-blue-500'
    };
    return classes[type] || classes.info;
};

const getToastIcon = (type) => {
    const icons = {
        success: 'CheckCircleIcon',
        error: 'ExclamationCircleIcon',
        warning: 'ExclamationIcon',
        info: 'InformationCircleIcon'
    };
    return icons[type] || icons.info;
};

const getIconColor = (type) => {
    const colors = {
        success: 'text-green-500',
        error: 'text-red-500',
        warning: 'text-yellow-500',
        info: 'text-blue-500'
    };
    return colors[type] || colors.info;
};

const getTitleColor = (type) => {
    const colors = {
        success: 'text-gray-900',
        error: 'text-gray-900',
        warning: 'text-gray-900',
        info: 'text-gray-900'
    };
    return colors[type] || colors.info;
};

const getMessageColor = (type) => {
    const colors = {
        success: 'text-gray-500',
        error: 'text-gray-500',
        warning: 'text-gray-500',
        info: 'text-gray-500'
    };
    return colors[type] || colors.info;
};

const getProgressBarColor = (type) => {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    return colors[type] || colors.info;
};

const getBorderAccentColor = (type) => {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    return colors[type] || colors.info;
};

const getActionClasses = (action) => {
    const baseClasses = 'hover:opacity-80 transition-opacity';
    const colorClasses = {
        primary: 'bg-indigo-600 text-white',
        secondary: 'bg-gray-200 text-gray-800',
        success: 'bg-green-600 text-white',
        danger: 'bg-red-600 text-white',
        warning: 'bg-yellow-600 text-white'
    };

    return `${baseClasses} ${colorClasses[action.variant] || colorClasses.secondary}`;
};

// Icon Components
const CheckCircleIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414z" clip-rule="evenodd"/>
        </svg>
    `
};

const ExclamationCircleIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 112 0V6a1 1 0 11-2 0v5z" clip-rule="evenodd"/>
        </svg>
    `
};

const ExclamationIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 112 0V4a1 1 0 11-2 0v5z" clip-rule="evenodd"/>
        </svg>
    `
};

const InformationCircleIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
    `
};

// Expose methods for external use
defineExpose({
    success,
    error,
    warning,
    info,
    show: showToast,
    remove: removeToast,
    clear: clearAllToasts
});

// Auto-cleanup on unmount
onUnmounted(() => {
    clearAllToasts();
});
</script>
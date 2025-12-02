<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeDialog">
                <!-- Backdrop with blur effect -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="closeDialog"></div>

                <!-- Dialog Panel -->
                <Transition
                    enter-active-class="transition-all ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition-all ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full max-h-[90vh] overflow-hidden flex flex-col">
                            <!-- Icon -->
                            <div v-if="showIcon" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full" :class="iconContainerClass">
                                <component :is="iconComponent" class="h-6 w-6" :class="iconClass" />
                            </div>

                            <!-- Header -->
                            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                            {{ title }}
                                        </h3>
                                        <div v-if="description" class="mt-2 text-sm text-gray-500">
                                            {{ description }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div v-if="content || $slots.default" class="px-4 pt-2 pb-4 sm:p-6 sm:pt-4 flex-1 overflow-y-auto">
                                <div class="text-sm text-gray-600">
                                    <slot v-if="$slots.default">
                                        {{ content }}
                                    </slot>
                                    <p v-else>
                                        {{ defaultContent }}
                                    </p>
                                </div>

                                <!-- Custom Fields -->
                                <div v-if="showCustomFields" class="mt-4 space-y-4 flex-1 overflow-y-auto">
                                    <!-- Confirmation Input -->
                                    <div v-if="requireConfirmation">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Please type <code class="bg-gray-100 px-1 py-0.5 rounded">{{ confirmationText }}</code> to confirm
                                        </label>
                                        <input
                                            v-model="confirmationInput"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            :placeholder="`Type '${confirmationText}' to confirm`"
                                        >
                                        </div>

                                    <!-- Reason Input -->
                                    <div v-if="requireReason">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ reasonLabel || 'Please provide a reason' }}
                                        </label>
                                        <textarea
                                            v-model="reasonInput"
                                            rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            :placeholder="reasonPlaceholder"
                                        ></textarea>
                                    </div>

                                    <!-- Additional Fields -->
                                    <div v-for="field in customFields" :key="field.name">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ field.label }}
                                            <span v-if="field.required" class="text-red-500 ml-1">*</span>
                                        </label>
                                        <input
                                            v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'"
                                            v-model="customFieldValues[field.name]"
                                            :type="field.type"
                                            :required="field.required"
                                            :placeholder="field.placeholder"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                        <select
                                            v-else-if="field.type === 'select'"
                                            v-model="customFieldValues[field.name]"
                                            :required="field.required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">Select...</option>
                                            <option
                                                v-for="option in field.options"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <textarea
                                            v-else-if="field.type === 'textarea'"
                                            v-model="customFieldValues[field.name]"
                                            :required="field.required"
                                            :placeholder="field.placeholder"
                                            rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- Warning Details -->
                                <div v-if="warningDetails" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="text-sm text-yellow-800">
                                            <p class="font-medium">Warning</p>
                                            <p class="mt-1">{{ warningDetails }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="px-4 py-3 bg-gray-50 px-6 flex flex-row-reverse sm:px-6 sm:flex-row">
                                <button
                                    type="button"
                                    @click="handleConfirm"
                                    :disabled="!canConfirm"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                    :class="confirmButtonClass"
                                >
                                    <span v-if="loading">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ loading ? 'Processing...' : confirmText }}
                                    </span>
                                    <span v-else>
                                        <svg v-if="showConfirmIcon" class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ confirmText }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="closeDialog"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    <svg v-if="showCancelIcon" class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ cancelText }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirm Action'
    },
    description: {
        type: String,
        default: ''
    },
    content: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'warning',
        validator: (value) => ['success', 'warning', 'error', 'info', 'danger'].includes(value)
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    loading: {
        type: Boolean,
        default: false
    },
    showIcon: {
        type: Boolean,
        default: true
    },
    showConfirmIcon: {
        type: Boolean,
        default: true
    },
    showCancelIcon: {
        type: Boolean,
        default: true
    },
    requireConfirmation: {
        type: Boolean,
        default: false
    },
    confirmationText: {
        type: String,
        default: 'confirm'
    },
    requireReason: {
        type: Boolean,
        default: false
    },
    reasonLabel: {
        type: String,
        default: 'Reason'
    },
    reasonPlaceholder: {
        type: String,
        default: 'Please provide a reason...'
    },
    customFields: {
        type: Array,
        default: () => []
    },
    warningDetails: {
        type: String,
        default: ''
    },
    defaultContent: {
        type: String,
        default: 'Are you sure you want to proceed?'
    }
});

const emit = defineEmits(['confirm', 'cancel']);

const confirmationInput = ref('');
const reasonInput = ref('');
const customFieldValues = ref({});

// Initialize custom field values
const initializeCustomFields = () => {
    props.customFields.forEach(field => {
        if (field.defaultValue !== undefined) {
            customFieldValues.value[field.name] = field.defaultValue;
        } else {
            customFieldValues.value[field.name] = field.type === 'checkbox' ? [] : '';
        }
    });
};

initializeCustomFields();

const iconContainerClass = computed(() => {
    const classes = {
        success: 'bg-green-100',
        warning: 'bg-yellow-100',
        error: 'bg-red-100',
        info: 'bg-blue-100',
        danger: 'bg-red-100'
    };
    return classes[props.type] || classes.warning;
});

const iconClass = computed(() => {
    const classes = {
        success: 'text-green-600',
        warning: 'text-yellow-600',
        error: 'text-red-600',
        info: 'text-blue-600',
        danger: 'text-red-600'
    };
    return classes[props.type] || classes.warning;
});

const confirmButtonClass = computed(() => {
    const classes = {
        success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
        warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        error: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        info: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
        danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
    };
    return classes[props.type] || classes.warning;
});

const iconComponent = computed(() => {
    const icons = {
        success: 'CheckCircleIcon',
        warning: 'ExclamationIcon',
        error: 'XCircleIcon',
        info: 'InformationCircleIcon',
        danger: 'ExclamationTriangleIcon'
    };
    return icons[props.type] || icons.warning;
});

const canConfirm = computed(() => {
    if (props.loading) return false;
    if (props.requireConfirmation && confirmationInput.value !== props.confirmationText) return false;
    if (props.requireReason && !reasonInput.value.trim()) return false;

    // Check required custom fields
    for (const field of props.customFields) {
        if (field.required && !customFieldValues.value[field.name] && !customFieldValues.value[field.name]?.toString().trim()) {
            return false;
        }
    }

    return true;
});

const handleConfirm = () => {
    if (!canConfirm.value) return;

    const data = {
        confirmation: confirmationInput.value,
        reason: reasonInput.value,
        customFields: customFieldValues.value
    };

    emit('confirm', data);
};

const closeDialog = () => {
    // Reset form data
    confirmationInput.value = '';
    reasonInput.value = '';
    Object.keys(customFieldValues.value).forEach(key => {
        const field = props.customFields.find(f => f.name === key);
        if (field) {
            customFieldValues.value[key] = field.defaultValue !== undefined ? field.defaultValue : (field.type === 'checkbox' ? [] : '');
        }
    });

    emit('cancel');
};

// Icon components
const CheckCircleIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414z" clip-rule="evenodd"/>
        </svg>
    `
};

const ExclamationIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 112 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
    `
};

const XCircleIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414-1.414L8.586 5.707a1 1 0 00-1.414 0l-2 2a1 1 0 01-1.414 0l4.293-4.293a1 1 0 011.414 1.414L11.414 7l4.293 4.293a1 1 0 01-1.414 1.414L8.586 9.414l-2-2a1 1 0 01-1.414 0L4.879 8.879a1 1 0 111.415-1.415l2-2a1 1 0 011.415-1.414L11.414 10.586l4.293-4.293a1 1 0 011.414 1.414L11.414 12l4.293 4.293a1 1 0 01-1.414 1.414L8.586 14.414l-2-2a1 1 0 01-1.414 0L4.879 14.879a1 1 0 111.415-1.415l2-2a1 1 0 011.415-1.414L11.414 15.414l4.293 4.293a1 1 0 001.414 1.414L11.414 17l-2-2a1 1 0 01-1.414 0L4.879 17.879a1 1 0 111.415-1.415l2-2a1 1 0 011.415-1.414z" clip-rule="evenodd"/>
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

const ExclamationTriangleIcon = {
    template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 112 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
    `
};
</script>

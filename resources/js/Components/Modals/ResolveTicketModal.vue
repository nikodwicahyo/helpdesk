<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center">
                                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <DialogTitle as="h3" class="ml-4 text-xl font-semibold leading-6 text-gray-900">
                                            Resolve Ticket
                                        </DialogTitle>
                                    </div>
                                    <button
                                        @click="$emit('close')"
                                        class="text-gray-400 hover:text-gray-500 focus:outline-none"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Ticket Info -->
                                <div v-if="ticket" class="mb-6 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ ticket.ticket_number }}</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ ticket.title }}</p>
                                        </div>
                                        <span :class="getPriorityBadgeClass(ticket.priority)" class="px-3 py-1 text-xs font-medium rounded-full">
                                            {{ ticket.priority_label || ticket.priority }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Form -->
                                <form @submit.prevent="handleSubmit" class="space-y-5">
                                    <!-- Resolution Notes -->
                                    <div>
                                        <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                            Resolution Notes <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.resolution_notes"
                                            id="resolution_notes"
                                            rows="4"
                                            required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                                            placeholder="Describe the steps taken to resolve the issue..."
                                        ></textarea>
                                        <p v-if="errors.resolution_notes" class="mt-1 text-sm text-red-600">{{ errors.resolution_notes }}</p>
                                    </div>

                                    <!-- Solution Summary -->
                                    <div>
                                        <label for="solution_summary" class="block text-sm font-medium text-gray-700 mb-2">
                                            Solution Summary <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.solution_summary"
                                            id="solution_summary"
                                            rows="3"
                                            required
                                            maxlength="500"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                                            placeholder="Brief summary of the solution for the user..."
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500">{{ form.solution_summary?.length || 0 }}/500 characters</p>
                                        <p v-if="errors.solution_summary" class="mt-1 text-sm text-red-600">{{ errors.solution_summary }}</p>
                                    </div>

                                    <!-- Technical Notes (Internal) -->
                                    <div>
                                        <label for="technical_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                            Technical Notes
                                            <span class="text-gray-400 text-xs ml-1">(Internal - not visible to user)</span>
                                        </label>
                                        <textarea
                                            v-model="form.technical_notes"
                                            id="technical_notes"
                                            rows="3"
                                            maxlength="1000"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                                            placeholder="Technical details for future reference..."
                                        ></textarea>
                                    </div>

                                    <!-- File Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Solution Documentation
                                            <span class="text-gray-400 text-xs ml-1">(Optional - screenshots, documents)</span>
                                        </label>
                                        <div
                                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-green-400 transition-colors"
                                            @dragover.prevent
                                            @drop.prevent="handleFileDrop"
                                        >
                                            <div class="space-y-1 text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <div class="flex text-sm text-gray-600">
                                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none">
                                                        <span>Upload files</span>
                                                        <input
                                                            id="file-upload"
                                                            type="file"
                                                            class="sr-only"
                                                            multiple
                                                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt"
                                                            @change="handleFileSelect"
                                                        />
                                                    </label>
                                                    <p class="pl-1">or drag and drop</p>
                                                </div>
                                                <p class="text-xs text-gray-500">PNG, JPG, PDF, DOC up to 2MB each</p>
                                            </div>
                                        </div>

                                        <!-- Selected Files -->
                                        <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
                                            <div
                                                v-for="(file, index) in selectedFiles"
                                                :key="index"
                                                class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
                                            >
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                    </svg>
                                                    <span class="text-sm text-gray-700 truncate max-w-xs">{{ file.name }}</span>
                                                    <span class="text-xs text-gray-400 ml-2">({{ formatFileSize(file.size) }})</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="removeFile(index)"
                                                    class="text-red-500 hover:text-red-700"
                                                >
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add to Knowledge Base -->
                                    <div class="flex items-center">
                                        <input
                                            v-model="form.add_to_knowledge_base"
                                            id="add_to_kb"
                                            type="checkbox"
                                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                        />
                                        <label for="add_to_kb" class="ml-2 block text-sm text-gray-900">
                                            Add solution to Knowledge Base
                                        </label>
                                    </div>

                                    <!-- Error Messages -->
                                    <div v-if="Object.keys(errors).length > 0" class="rounded-lg bg-red-50 p-4">
                                        <div class="flex">
                                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="ml-3">
                                                <ul class="text-sm text-red-700 list-disc list-inside">
                                                    <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button
                                    type="button"
                                    @click="handleSubmit"
                                    :disabled="submitting || !isFormValid"
                                    class="inline-flex w-full justify-center rounded-lg bg-green-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ submitting ? 'Resolving...' : 'Resolve Ticket' }}
                                </button>
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    :disabled="submitting"
                                    class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                >
                                    Cancel
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    ticket: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'resolved']);

const form = ref({
    resolution_notes: '',
    solution_summary: '',
    technical_notes: '',
    add_to_knowledge_base: false,
});

const selectedFiles = ref([]);
const errors = ref({});
const submitting = ref(false);

const isFormValid = computed(() => {
    return form.value.resolution_notes.trim().length > 0 &&
           form.value.solution_summary.trim().length > 0;
});

watch(() => props.show, (newVal) => {
    if (!newVal) {
        resetForm();
    }
});

const resetForm = () => {
    form.value = {
        resolution_notes: '',
        solution_summary: '',
        technical_notes: '',
        add_to_knowledge_base: false,
    };
    selectedFiles.value = [];
    errors.value = {};
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    addFiles(files);
};

const handleFileDrop = (event) => {
    const files = Array.from(event.dataTransfer.files);
    addFiles(files);
};

const addFiles = (files) => {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    const maxSize = 2 * 1024 * 1024; // 2MB

    files.forEach(file => {
        if (!allowedTypes.includes(file.type)) {
            errors.value.files = `File type not allowed: ${file.name}`;
            return;
        }
        if (file.size > maxSize) {
            errors.value.files = `File too large: ${file.name} (max 2MB)`;
            return;
        }
        if (selectedFiles.value.length >= 5) {
            errors.value.files = 'Maximum 5 files allowed';
            return;
        }
        selectedFiles.value.push(file);
    });
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    delete errors.value.files;
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    };
    return classes[priority] || 'bg-gray-100 text-gray-800';
};

const handleSubmit = async () => {
    if (!isFormValid.value || !props.ticket) return;

    errors.value = {};
    submitting.value = true;

    try {
        const formData = new FormData();
        formData.append('resolution_notes', form.value.resolution_notes);
        formData.append('solution_summary', form.value.solution_summary);
        if (form.value.technical_notes) {
            formData.append('technical_notes', form.value.technical_notes);
        }
        formData.append('add_to_knowledge_base', form.value.add_to_knowledge_base ? '1' : '0');

        selectedFiles.value.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        const response = await axios.post(`/teknisi/tickets/${props.ticket.id}/resolve`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            emit('resolved', response.data.ticket);
            emit('close');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            const responseErrors = error.response.data.errors;
            if (Array.isArray(responseErrors)) {
                errors.value = { general: responseErrors.join(', ') };
            } else {
                errors.value = responseErrors || { general: 'Validation failed' };
            }
        } else {
            errors.value = { general: error.response?.data?.message || 'Failed to resolve ticket. Please try again.' };
        }
    } finally {
        submitting.value = false;
    }
};
</script>

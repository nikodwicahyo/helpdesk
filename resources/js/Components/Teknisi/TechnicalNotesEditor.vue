<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add Technical Note</h3>
            <div class="flex items-center space-x-2">
                <label class="flex items-center space-x-2 text-sm text-gray-600">
                    <input
                        v-model="isInternal"
                        type="checkbox"
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                    />
                    <span>Internal note (not visible to user)</span>
                </label>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Note Content -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Note Content *
                </label>
                <textarea
                    v-model="noteContent"
                    rows="4"
                    placeholder="Add technical details, troubleshooting steps, or internal observations..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"
                    :class="{ 'border-red-300': showError && !noteContent }"
                ></textarea>
                <p v-if="showError && !noteContent" class="mt-1 text-sm text-red-600">
                    Note content is required
                </p>
            </div>

            <!-- File Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Attachments (optional)
                </label>
                <div
                    @drop.prevent="handleDrop"
                    @dragover.prevent
                    @dragenter.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    :class="[
                        'border-2 border-dashed rounded-lg p-4 text-center transition-colors',
                        isDragging ? 'border-orange-500 bg-orange-50' : 'border-gray-300'
                    ]"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                        @change="handleFileSelect"
                        class="hidden"
                    />
                    
                    <button
                        @click="$refs.fileInput.click()"
                        type="button"
                        class="text-orange-600 hover:text-orange-700 font-medium"
                    >
                        Click to upload
                    </button>
                    <span class="text-gray-600"> or drag and drop</span>
                    <p class="text-xs text-gray-500 mt-1">
                        PNG, JPG, PDF, DOC up to 2MB each (max 5 files)
                    </p>
                </div>

                <!-- Selected Files -->
                <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
                    <div
                        v-for="(file, index) in selectedFiles"
                        :key="index"
                        class="flex items-center justify-between bg-gray-50 rounded-lg p-2"
                    >
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-sm text-gray-700">{{ file.name }}</span>
                            <span class="text-xs text-gray-500">({{ formatFileSize(file.size) }})</span>
                        </div>
                        <button
                            @click="removeFile(index)"
                            type="button"
                            class="text-red-600 hover:text-red-700"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <button
                    @click="clearForm"
                    type="button"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors"
                >
                    Clear
                </button>
                <button
                    @click="submitNote"
                    :disabled="loading || !noteContent"
                    class="px-4 py-2 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Submitting...
                    </span>
                    <span v-else>Add Technical Note</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    ticketId: {
        type: [Number, String],
        required: true
    }
});

const emit = defineEmits(['submitted', 'error']);

const noteContent = ref('');
const isInternal = ref(true);
const selectedFiles = ref([]);
const loading = ref(false);
const showError = ref(false);
const isDragging = ref(false);
const fileInput = ref(null);

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    addFiles(files);
};

const handleDrop = (event) => {
    isDragging.value = false;
    const files = Array.from(event.dataTransfer.files);
    addFiles(files);
};

const addFiles = (files) => {
    // Validate file count
    if (selectedFiles.value.length + files.length > 5) {
        emit('error', ['Maximum 5 files allowed']);
        return;
    }

    // Validate file size and type
    const validFiles = files.filter(file => {
        if (file.size > 2 * 1024 * 1024) {
            emit('error', [`File ${file.name} exceeds 2MB limit`]);
            return false;
        }

        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                           'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                           'text/plain'];
        if (!validTypes.includes(file.type)) {
            emit('error', [`File ${file.name} has invalid type`]);
            return false;
        }

        return true;
    });

    selectedFiles.value.push(...validFiles);
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const submitNote = async () => {
    if (!noteContent.value) {
        showError.value = true;
        return;
    }

    loading.value = true;
    showError.value = false;

    try {
        const formData = new FormData();
        formData.append('technical_note', noteContent.value);
        formData.append('is_internal', isInternal.value ? '1' : '0');

        selectedFiles.value.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        const response = await axios.post(`/teknisi/tickets/${props.ticketId}/technical-notes`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            emit('submitted', response.data);
            clearForm();
        }
    } catch (error) {
        console.error('Failed to submit technical note:', error);
        emit('error', error.response?.data?.errors || ['Failed to submit technical note']);
    } finally {
        loading.value = false;
    }
};

const clearForm = () => {
    noteContent.value = '';
    isInternal.value = true;
    selectedFiles.value = [];
    showError.value = false;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

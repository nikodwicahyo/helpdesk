<template>
  <div class="w-full">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Drop Zone -->
    <div
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      :class="[
        'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
        isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400',
        error ? 'border-red-500' : ''
      ]"
    >
      <input
        ref="fileInput"
        type="file"
        :multiple="multiple"
        :accept="accept"
        @change="handleFileSelect"
        class="hidden"
      />

      <div class="space-y-2">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        <div class="text-sm text-gray-600">
          <button
            type="button"
            @click="$refs.fileInput.click()"
            class="font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none"
          >
            Upload a file
          </button>
          or drag and drop
        </div>
        <p class="text-xs text-gray-500">
          {{ acceptText }} up to {{ maxSizeMB }}MB {{ multiple ? 'each' : '' }}
        </p>
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>

    <!-- File List -->
    <div v-if="files.length > 0" class="mt-4 space-y-2">
      <div
        v-for="(file, index) in files"
        :key="index"
        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200"
      >
        <div class="flex items-center space-x-3 flex-1 min-w-0">
          <!-- File Icon -->
          <div class="flex-shrink-0">
            <svg v-if="isImage(file)" class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
            </svg>
            <svg v-else-if="isPDF(file)" class="h-8 w-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
            </svg>
            <svg v-else class="h-8 w-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
            </svg>
          </div>

          <!-- File Info -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
            <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
          </div>

          <!-- Progress Bar (if uploading) -->
          <div v-if="file.progress !== undefined && file.progress < 100" class="flex-1 max-w-xs">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: file.progress + '%' }"
              ></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ file.progress }}%</p>
          </div>

          <!-- Success Icon -->
          <div v-else-if="file.uploaded" class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>

        <!-- Remove Button -->
        <button
          type="button"
          @click="removeFile(index)"
          class="ml-3 flex-shrink-0 text-red-600 hover:text-red-800 transition"
        >
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: ''
  },
  modelValue: {
    type: Array,
    default: () => []
  },
  multiple: {
    type: Boolean,
    default: true
  },
  accept: {
    type: String,
    default: 'image/*,.pdf,.doc,.docx'
  },
  maxSize: {
    type: Number,
    default: 2 * 1024 * 1024 // 2MB in bytes
  },
  maxFiles: {
    type: Number,
    default: 5
  },
  required: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'error']);

const fileInput = ref(null);
const files = ref([...props.modelValue]);
const isDragging = ref(false);
const error = ref('');

const maxSizeMB = computed(() => Math.round(props.maxSize / (1024 * 1024)));

const acceptText = computed(() => {
  const types = props.accept.split(',').map(t => t.trim());
  if (types.includes('image/*')) return 'Images, PDFs, Documents';
  return types.join(', ').toUpperCase();
});

const validateFile = (file) => {
  // Check file size
  if (file.size > props.maxSize) {
    return `File "${file.name}" exceeds maximum size of ${maxSizeMB.value}MB`;
  }

  // Check file type
  const acceptTypes = props.accept.split(',').map(t => t.trim());
  const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
  const fileType = file.type;

  const isValidType = acceptTypes.some(type => {
    if (type === 'image/*') return fileType.startsWith('image/');
    if (type.startsWith('.')) return fileExtension === type;
    return fileType === type;
  });

  if (!isValidType) {
    return `File type "${fileExtension}" is not allowed`;
  }

  return null;
};

const handleFileSelect = (event) => {
  const selectedFiles = Array.from(event.target.files);
  addFiles(selectedFiles);
  event.target.value = ''; // Reset input
};

const handleDrop = (event) => {
  isDragging.value = false;
  const droppedFiles = Array.from(event.dataTransfer.files);
  addFiles(droppedFiles);
};

const addFiles = (newFiles) => {
  error.value = '';

  // Check max files limit
  if (!props.multiple && newFiles.length > 1) {
    error.value = 'Only one file is allowed';
    emit('error', error.value);
    return;
  }

  if (files.value.length + newFiles.length > props.maxFiles) {
    error.value = `Maximum ${props.maxFiles} files allowed`;
    emit('error', error.value);
    return;
  }

  // Validate and add files
  for (const file of newFiles) {
    const validationError = validateFile(file);
    if (validationError) {
      error.value = validationError;
      emit('error', error.value);
      return;
    }

    if (!props.multiple) {
      files.value = [file];
    } else {
      files.value.push(file);
    }
  }

  emit('update:modelValue', files.value);
};

const removeFile = (index) => {
  files.value.splice(index, 1);
  emit('update:modelValue', files.value);
  error.value = '';
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const isImage = (file) => {
  return file.type.startsWith('image/');
};

const isPDF = (file) => {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
};
</script>

<template>
  <div class="space-y-4">
    <!-- Field Validation Messages -->
    <div v-if="showFieldValidation && fieldErrors" class="space-y-2">
      <div
        v-for="(error, field) in fieldErrors"
        :key="field"
        class="flex items-start space-x-2 p-3 bg-red-50 border border-red-200 rounded-lg animate-fade-in"
      >
        <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm font-medium text-red-800 capitalize">{{ field.replace('_', ' ') }}</p>
          <p class="text-sm text-red-700 mt-1">{{ error }}</p>
        </div>
        <button
          @click="clearFieldError(field)"
          class="text-red-400 hover:text-red-600"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Success Messages -->
    <div v-if="showSuccessMessage && successMessage" class="p-3 bg-green-50 border border-green-200 rounded-lg animate-fade-in">
      <div class="flex items-start space-x-2">
        <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm text-green-800">{{ successMessage }}</p>
        </div>
        <button
          @click="clearSuccessMessage"
          class="text-green-400 hover:text-green-600"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Warning Messages -->
    <div v-if="showWarningMessage && warningMessage" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg animate-fade-in">
      <div class="flex items-start space-x-2">
        <svg class="w-5 h-5 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm text-yellow-800">{{ warningMessage }}</p>
        </div>
        <button
          @click="clearWarningMessage"
          class="text-yellow-400 hover:text-yellow-600"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Info Messages -->
    <div v-if="showInfoMessage && infoMessage" class="p-3 bg-blue-50 border border-blue-200 rounded-lg animate-fade-in">
      <div class="flex items-start space-x-2">
        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm text-blue-800">{{ infoMessage }}</p>
        </div>
        <button
          @click="clearInfoMessage"
          class="text-blue-400 hover:text-blue-600"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Form Summary -->
    <div v-if="showFormSummary && formSummary" class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div
            class="w-3 h-3 rounded-full"
            :class="isFormValid ? 'bg-green-500' : 'bg-red-500'"
          ></div>
          <span class="text-sm font-medium" :class="isFormValid ? 'text-green-800' : 'text-red-800'">
            {{ formSummary }}
          </span>
        </div>
        <div v-if="fieldCount > 0" class="text-xs text-gray-500">
          {{ validFields }}/{{ fieldCount }} field valid
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

// Props
const props = defineProps({
  fieldErrors: {
    type: Object,
    default: () => ({})
  },
  successMessage: {
    type: String,
    default: ''
  },
  warningMessage: {
    type: String,
    default: ''
  },
  infoMessage: {
    type: String,
    default: ''
  },
  formSummary: {
    type: String,
    default: ''
  },
  validFields: {
    type: Number,
    default: 0
  },
  fieldCount: {
    type: Number,
    default: 0
  },
  showFieldValidation: {
    type: Boolean,
    default: true
  },
  showSuccessMessage: {
    type: Boolean,
    default: true
  },
  showWarningMessage: {
    type: Boolean,
    default: true
  },
  showInfoMessage: {
    type: Boolean,
    default: true
  },
  showFormSummary: {
    type: Boolean,
    default: false
  }
});

// Computed
const isFormValid = computed(() => {
  return props.validFields === props.fieldCount && props.fieldCount > 0;
});

// Emits
const emit = defineEmits(['clear-field-error', 'clear-success', 'clear-warning', 'clear-info']);

// Methods
const clearFieldError = (field) => {
  emit('clear-field-error', field);
};

const clearSuccessMessage = () => {
  emit('clear-success');
};

const clearWarningMessage = () => {
  emit('clear-warning');
};

const clearInfoMessage = () => {
  emit('clear-info');
};
</script>

<style scoped>
/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

/* Smooth transitions */
.transition-all {
  transition: all 0.2s ease;
}

/* Custom focus styles */
button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

button:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 2px;
}
</style>
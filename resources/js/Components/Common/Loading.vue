<template>
  <Teleport v-if="fullscreen" to="body">
    <div v-if="show" :class="containerClass">
      <div class="flex flex-col items-center justify-center space-y-4">
        <!-- Enhanced Spinner -->
        <div :class="spinnerClass" class="relative">
          <svg class="animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <!-- Pulse effect for larger spinners -->
          <div v-if="size === 'xl'" class="absolute inset-0 rounded-full bg-indigo-600 opacity-20 animate-pulse"></div>
        </div>

        <!-- Enhanced Loading Text -->
        <div class="text-center">
          <p :class="textClass" class="font-medium">{{ text || t('loading.defaultText') }}</p>
          <p v-if="subtext" class="text-sm text-gray-500 mt-1">{{ subtext }}</p>

          <!-- Progress Bar for longer operations -->
          <div v-if="showProgress" class="mt-4 w-64">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-indigo-600 h-2 rounded-full transition-all duration-300 ease-out"
                :style="{ width: progress + '%' }"
              ></div>
            </div>
            <p v-if="progressText" class="text-xs text-gray-500 mt-1">{{ progressText }}</p>
          </div>

          <!-- Loading Dots Animation -->
          <div v-if="showDots" class="flex justify-center space-x-1 mt-2">
            <div
              v-for="i in 3"
              :key="i"
              class="w-2 h-2 bg-indigo-600 rounded-full animate-pulse"
              :style="{ animationDelay: `${i * 0.2}s` }"
            ></div>
          </div>
        </div>

        <!-- Loading Steps -->
        <div v-if="steps && steps.length > 0" class="w-full max-w-xs">
          <div class="space-y-2">
            <div
              v-for="(step, index) in steps"
              :key="index"
              class="flex items-center space-x-2 text-sm"
              :class="index === currentStep ? 'text-indigo-600 font-medium' : 'text-gray-500'"
            >
              <div
                class="w-2 h-2 rounded-full"
                :class="index < currentStep ? 'bg-green-500' : index === currentStep ? 'bg-indigo-500' : 'bg-gray-300'"
              ></div>
              <span>{{ step }}</span>
              <svg v-if="index < currentStep" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Inline Loading (when not fullscreen) -->
  <div v-else-if="show" :class="containerClass">
    <div class="flex flex-col items-center justify-center space-y-4">
      <!-- Spinner -->
      <div :class="spinnerClass">
        <svg class="animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <!-- Loading Text -->
      <div class="text-center">
        <p :class="textClass">{{ text || t('loading.defaultText') }}</p>
        <p v-if="subtext" class="text-sm text-gray-500 mt-1">{{ subtext }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: true
  },
  text: {
    type: String,
    default: null
  },
  subtext: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  fullscreen: {
    type: Boolean,
    default: false
  },
  overlay: {
    type: Boolean,
    default: false
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  progress: {
    type: Number,
    default: 0,
    validator: (value) => value >= 0 && value <= 100
  },
  progressText: {
    type: String,
    default: ''
  },
  showDots: {
    type: Boolean,
    default: false
  },
  steps: {
    type: Array,
    default: () => []
  },
  currentStep: {
    type: Number,
    default: 0
  }
});

const containerClass = computed(() => {
  const classes = [];
  
  if (props.fullscreen) {
    classes.push('fixed inset-0 z-50 flex items-center justify-center');
  } else {
    classes.push('flex items-center justify-center p-8');
  }
  
  if (props.overlay) {
    classes.push('bg-white bg-opacity-90');
  }
  
  return classes.join(' ');
});

const spinnerClass = computed(() => {
  const sizes = {
    sm: 'w-8 h-8',
    md: 'w-12 h-12',
    lg: 'w-16 h-16',
    xl: 'w-24 h-24'
  };
  
  return `text-indigo-600 ${sizes[props.size] || sizes.md}`;
});

const textClass = computed(() => {
  const sizes = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl'
  };
  
  return `font-medium text-gray-700 ${sizes[props.size] || sizes.md}`;
});
</script>

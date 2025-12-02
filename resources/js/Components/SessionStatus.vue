<template>
  <!-- Session Status Indicator - Shows when session is getting low -->
  <div
    v-if="showIndicator"
    class="fixed top-16 right-4 z-40 animate-slideInRight"
  >
    <div
      class="bg-white rounded-lg shadow-lg border-l-4 p-4 max-w-sm"
      :class="borderColorClass"
    >
      <div class="flex items-start space-x-3">
        <!-- Icon -->
        <div class="flex-shrink-0">
          <div 
            class="w-10 h-10 rounded-full flex items-center justify-center"
            :class="iconBgClass"
          >
            <svg class="w-5 h-5" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900">
            Sesi {{ statusText }}
          </p>
          <p class="text-xs text-gray-600 mt-1">
            Tersisa: <span class="font-semibold" :class="timeColorClass">{{ timeRemaining }}</span>
          </p>
          
          <!-- Quick Action Button -->
          <button
            v-if="minutesRemaining <= 10"
            @click="quickExtend"
            :disabled="isExtending"
            class="mt-2 w-full inline-flex justify-center items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
          >
            <svg v-if="isExtending" class="animate-spin -ml-1 mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="isExtending">Memperpanjang...</span>
            <span v-else>Perpanjang Sesi</span>
          </button>
        </div>

        <!-- Close Button -->
        <button
          @click="dismissIndicator"
          class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>

      <!-- Progress Bar -->
      <div class="mt-3">
        <div class="w-full bg-gray-200 rounded-full h-1">
          <div 
            class="h-1 rounded-full transition-all duration-1000"
            :class="progressBarClass"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useSession } from '../composables/useSession';
import { formatTimeRemaining } from '../utils/dateFormatter';

const { sessionData, extendSession, isExtending } = useSession();

const showIndicator = ref(false);
const isDismissed = ref(false);

// Computed properties
const minutesRemaining = computed(() => {
  return sessionData.value?.minutes_remaining || 0;
});

const timeRemaining = computed(() => {
  return formatTimeRemaining(minutesRemaining.value);
});

const progressPercentage = computed(() => {
  // Calculate based on 15 minutes threshold (show indicator when < 15 min)
  const maxMinutes = 15;
  return Math.min(100, (minutesRemaining.value / maxMinutes) * 100);
});

const statusText = computed(() => {
  if (minutesRemaining.value <= 5) return 'Kritis';
  if (minutesRemaining.value <= 10) return 'Menjelang Habis';
  return 'Akan Berakhir';
});

const borderColorClass = computed(() => {
  if (minutesRemaining.value <= 5) return 'border-red-500';
  if (minutesRemaining.value <= 10) return 'border-yellow-500';
  return 'border-blue-500';
});

const iconBgClass = computed(() => {
  if (minutesRemaining.value <= 5) return 'bg-red-100';
  if (minutesRemaining.value <= 10) return 'bg-yellow-100';
  return 'bg-blue-100';
});

const iconColorClass = computed(() => {
  if (minutesRemaining.value <= 5) return 'text-red-600';
  if (minutesRemaining.value <= 10) return 'text-yellow-600';
  return 'text-blue-600';
});

const timeColorClass = computed(() => {
  if (minutesRemaining.value <= 5) return 'text-red-600';
  if (minutesRemaining.value <= 10) return 'text-yellow-600';
  return 'text-blue-600';
});

const progressBarClass = computed(() => {
  if (minutesRemaining.value <= 5) return 'bg-red-500';
  if (minutesRemaining.value <= 10) return 'bg-yellow-500';
  return 'bg-blue-500';
});

// Watch for session time changes
watch(minutesRemaining, (minutes) => {
  // Show indicator when less than 15 minutes remaining and not dismissed
  if (minutes <= 15 && minutes > 0 && !isDismissed.value) {
    showIndicator.value = true;
  } else if (minutes > 15 || minutes === 0) {
    showIndicator.value = false;
    isDismissed.value = false; // Reset dismiss state when session is extended or expired
  }
});

// Methods
const quickExtend = async () => {
  try {
    await extendSession();
    showIndicator.value = false;
    isDismissed.value = false;
  } catch (error) {
    console.error('Quick extend failed:', error);
  }
};

const dismissIndicator = () => {
  showIndicator.value = false;
  isDismissed.value = true;
};
</script>

<style scoped>
.animate-slideInRight {
  animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>

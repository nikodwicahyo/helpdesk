<template>
  <!-- Session Warning Modal -->
  <TransitionRoot appear :show="showWarning" as="template">
    <Dialog as="div" @close="handleClose" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <!-- Icon and Title -->
              <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div>
                  <DialogTitle as="h3" class="text-lg font-semibold text-gray-900">
                    {{ $t('session.timeoutWarning.title') }}
                  </DialogTitle>
                  <p class="text-sm text-gray-500">{{ $t('session.timeoutWarning.subtitle') }}</p>
                </div>
              </div>

              <!-- Warning Message -->
              <div class="mb-6">
                <p class="text-sm text-gray-700 mb-4">
                  {{ $t('session.timeoutWarning.messageStart') }}
                  <span class="font-bold text-yellow-600">{{ timeDisplay }}</span>
                  {{ $t('session.timeoutWarning.messageEnd') }}
                </p>
                <p class="text-xs text-gray-600">
                  {{ $t('session.timeoutWarning.instructionsStart') }}
                  <strong>{{ $t('session.timeoutWarning.extendSession') }}</strong>
                  {{ $t('session.timeoutWarning.instructionsMiddle') }}
                </p>
              </div>

              <!-- Countdown Progress Bar -->
              <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-xs font-medium text-gray-600">{{ $t('session.timeoutWarning.timeRemaining') }}</span>
                  <span class="text-xs font-bold" :class="progressColorClass">{{ timeDisplay }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-1000"
                    :class="progressBarClass"
                    :style="{ width: `${progressPercentage}%` }"
                  ></div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-3">
                <button
                  @click="extendSession"
                  :disabled="isExtending"
                  class="flex-1 inline-flex justify-center items-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  <svg v-if="isExtending" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span v-if="isExtending">{{ $t('session.timeoutWarning.extending') }}</span>
                  <span v-else>{{ $t('session.timeoutWarning.extendSession') }}</span>
                </button>
                
                <button
                  @click="logoutNow"
                  :disabled="isExtending"
                  class="flex-1 inline-flex justify-center items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  {{ $t('session.timeoutWarning.logoutNow') }}
                </button>
              </div>

              <!-- Auto-logout notification -->
              <div v-if="secondsRemaining <= 60" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-xs text-red-800 text-center font-medium">
                  {{ $t('session.timeoutWarning.autoLogoutWarning', { seconds: secondsRemaining }) }}
                </p>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useSession } from '../composables/useSession';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'extended', 'logout']);

const { extendSession: sessionExtend, logout, sessionData, isExtending } = useSession();

const showWarning = ref(props.show);
const secondsRemaining = ref(600); // 10 minutes in seconds
const countdownInterval = ref(null);

// Watch for prop changes
watch(() => props.show, (newValue) => {
  showWarning.value = newValue;
  
  if (newValue) {
    startCountdown();
  } else {
    stopCountdown();
  }
});

// Watch session data for minutes remaining
watch(() => sessionData.value?.minutes_remaining, (minutes) => {
  if (minutes !== undefined && minutes <= 10) {
    secondsRemaining.value = Math.max(0, Math.floor(minutes * 60));
  }
}, { immediate: true });

// Computed properties
const timeDisplay = computed(() => {
  const minutes = Math.floor(secondsRemaining.value / 60);
  const seconds = secondsRemaining.value % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const progressPercentage = computed(() => {
  return (secondsRemaining.value / 600) * 100; // 600 seconds = 10 minutes
});

const progressBarClass = computed(() => {
  if (progressPercentage.value > 50) return 'bg-green-500';
  if (progressPercentage.value > 25) return 'bg-yellow-500';
  return 'bg-red-500';
});

const progressColorClass = computed(() => {
  if (progressPercentage.value > 50) return 'text-green-600';
  if (progressPercentage.value > 25) return 'text-yellow-600';
  return 'text-red-600';
});

// Methods
const startCountdown = () => {
  stopCountdown(); // Clear any existing interval
  
  countdownInterval.value = setInterval(() => {
    if (secondsRemaining.value > 0) {
      secondsRemaining.value--;
    } else {
      // Auto logout when timer reaches 0
      stopCountdown();
      logoutNow();
    }
  }, 1000);
};

const stopCountdown = () => {
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value);
    countdownInterval.value = null;
  }
};

const extendSession = async () => {
  try {
    await sessionExtend();
    
    // Reset timer
    secondsRemaining.value = 600; // Reset to 10 minutes
    stopCountdown();
    showWarning.value = false;
    
    emit('extended');
    emit('close');
  } catch (error) {
    console.error('Failed to extend session:', error);
    // Show error notification
  }
};

const logoutNow = async () => {
  stopCountdown();
  emit('logout');
  await logout();
};

const handleClose = () => {
  // Don't allow closing by clicking outside
  // User must choose to extend or logout
};

// Cleanup on unmount
onUnmounted(() => {
  stopCountdown();
});
</script>

<style scoped>
.animate-slideInDown {
  animation: slideInDown 0.3s ease-out;
}

@keyframes slideInDown {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>

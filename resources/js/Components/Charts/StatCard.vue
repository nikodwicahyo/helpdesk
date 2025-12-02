<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 transition-all duration-300">
    <!-- Header -->
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-sm font-medium text-gray-600">{{ title }}</h3>
      <div class="flex items-center space-x-2">
        <!-- Auto-refresh toggle -->
        <button
          v-if="showAutoRefresh"
          @click="toggleAutoRefresh"
          :class="[
            'text-xs px-2 py-1 rounded transition-colors',
            autoRefreshEnabled
              ? 'bg-green-100 text-green-700 hover:bg-green-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
          :title="autoRefreshEnabled ? 'Auto-refresh ON (click to disable)' : 'Auto-refresh OFF (click to enable)'"
        >
          <svg class="w-3 h-3 mr-1 inline" :class="{ 'animate-pulse': autoRefreshEnabled }" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
          </svg>
          {{ autoRefreshEnabled ? 'Auto' : 'Manual' }}
        </button>

        <!-- Manual refresh button -->
        <button
          @click="handleRefresh"
          :disabled="isLoading || pollingInProgress"
          class="text-gray-400 hover:text-gray-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
          :class="{ 'animate-spin': isLoading }"
          title="Refresh data"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </button>

        <!-- Connection status indicator -->
        <div v-if="showConnectionStatus" class="flex items-center">
          <div
            :class="[
              'w-2 h-2 rounded-full',
              connectionStatus === 'connected' ? 'bg-green-500' :
              connectionStatus === 'connecting' ? 'bg-yellow-500 animate-pulse' :
              connectionStatus === 'error' ? 'bg-red-500' : 'bg-gray-400'
            ]"
            :title="connectionStatus === 'connected' ? 'Connected' : 
                   connectionStatus === 'connecting' ? 'Connecting...' :
                   connectionStatus === 'error' ? 'Connection error' : 'Disconnected'"
          ></div>
        </div>
      </div>
    </div>

    <!-- Value with trend -->
    <div class="flex items-baseline space-x-2">
      <span class="text-3xl font-bold text-gray-900" :class="{ 'text-gray-500': hasError }">
        {{ formattedValue }}
      </span>

      <!-- Trend indicator -->
      <div
        v-if="showTrend && trend !== 'neutral'"
        class="flex items-center space-x-1 text-sm"
        :class="{
          'text-green-600': trend === 'up',
          'text-red-600': trend === 'down',
          'text-gray-500': trend === 'stable'
        }"
      >
        <svg
          v-if="trend === 'up'"
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
        <svg
          v-else-if="trend === 'down'"
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
        </svg>
        <span v-if="trendValue !== null">{{ Math.abs(trendValue) }}%</span>
      </div>
    </div>

    <!-- Description -->
    <p class="text-sm text-gray-500 mt-2">{{ description }}</p>

    <!-- Additional info -->
    <div v-if="showDetails && details" class="mt-3 pt-3 border-t border-gray-100">
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div
          v-for="(detail, key) in details"
          :key="key"
          class="flex justify-between"
        >
          <span class="text-gray-500">{{ key }}:</span>
          <span class="font-medium text-gray-700">{{ detail }}</span>
        </div>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="hasError" class="mt-3 p-2 bg-red-100 border border-red-300 rounded text-xs text-red-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          {{ errorMessage || 'Failed to load data' }}
        </div>
        <button
          @click="retryRefresh"
          class="text-red-800 hover:text-red-900 font-medium"
          :disabled="isLoading"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-else-if="isLoading" class="mt-3 flex items-center justify-center py-4">
      <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
      <span class="ml-2 text-sm text-gray-500">
        {{ pollingInProgress ? 'Refreshing...' : 'Loading...' }}
      </span>
    </div>

    <!-- Last updated -->
    <div v-else class="mt-3 flex items-center justify-between text-xs text-gray-400">
      <span>Last updated: {{ lastUpdatedDisplay }}</span>
      <span v-if="showPollingStatus && autoRefreshEnabled && pollingInterval">
        Updates every {{ Math.floor(pollingInterval / 1000) }}s
      </span>
    </div>

    <!-- Progress bar for percentage metrics -->
    <div
      v-if="showProgress && maxValue"
      class="mt-3"
    >
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-500"
          :class="progressColor"
          :style="{ width: `${Math.min((value / maxValue) * 100, 100)}%` }"
        ></div>
      </div>
      <div class="flex justify-between text-xs text-gray-500 mt-1">
        <span>{{ Math.round((value / maxValue) * 100) }}%</span>
        <span>{{ formatNumber(maxValue) }}</span>
      </div>
    </div>

    <!-- Polling status debug info (development only) -->
    <div v-if="showDebugInfo && import.meta.env.DEV" class="mt-2 text-xs text-gray-400 border-t pt-2">
      <div>Debug: {{ debugInfo }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePolling } from '@/composables/usePolling.js';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: null
  },
  color: {
    type: String,
    default: 'blue' // blue, green, red, yellow, purple
  },
  format: {
    type: String,
    default: 'number', // number, percentage, currency, time
  },
  showTrend: {
    type: Boolean,
    default: true
  },
  trendValue: {
    type: Number,
    default: null
  },
  showDetails: {
    type: Boolean,
    default: false
  },
  details: {
    type: Object,
    default: () => ({})
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  maxValue: {
    type: Number,
    default: null
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  hasError: {
    type: Boolean,
    default: false
  },
  errorMessage: {
    type: String,
    default: null
  },
  // Polling-related props
  enablePolling: {
    type: Boolean,
    default: false
  },
  pollingEndpoint: {
    type: String,
    default: ''
  },
  pollingInterval: {
    type: Number,
    default: 30000 // 30 seconds
  },
  showAutoRefresh: {
    type: Boolean,
    default: true
  },
  showConnectionStatus: {
    type: Boolean,
    default: true
  },
  showPollingStatus: {
    type: Boolean,
    default: false
  },
  showDebugInfo: {
    type: Boolean,
    default: false
  },
  autoRefreshEnabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['refresh', 'error', 'data-update']);

// Local state
const lastUpdated = ref(null);
const updateInterval = ref(null);
const pollingInProgress = ref(false);
const localError = ref(null);
const localAutoRefreshEnabled = ref(props.autoRefreshEnabled);

// Initialize polling if enabled
const polling = props.enablePolling && props.pollingEndpoint
  ? usePolling({
      endpoint: props.pollingEndpoint,
      interval: props.pollingInterval,
      autoStart: localAutoRefreshEnabled.value,
      transform: (response) => {
        emit('data-update', response);
        return response;
      },
      onError: (error) => {
        localError.value = error.message;
        emit('error', error);
      }
    })
  : null;

// Computed properties
const formattedValue = computed(() => {
  return formatValue(props.value);
});

const trend = computed(() => {
  if (!props.showTrend || props.trendValue === null) return 'neutral';

  if (props.trendValue > 5) return 'up';
  if (props.trendValue < -5) return 'down';
  return 'stable';
});

const progressColor = computed(() => {
  const percentage = (props.value / props.maxValue) * 100;

  if (percentage >= 80) return 'bg-green-500';
  if (percentage >= 60) return 'bg-blue-500';
  if (percentage >= 40) return 'bg-yellow-500';
  return 'bg-red-500';
});

const connectionStatus = computed(() => {
  if (polling) {
    return polling.connectionStatus.value;
  }
  return 'disconnected';
});

const lastUpdatedDisplay = computed(() => {
  if (!lastUpdated.value) return 'Never';
  
  const now = new Date();
  const diff = Math.floor((now - lastUpdated.value) / 1000);
  
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  return `${Math.floor(diff / 3600)}h ago`;
});

const debugInfo = computed(() => {
  if (!polling) return 'No polling configured';
  
  return `Polling: ${polling.isPolling.value ? 'Active' : 'Inactive'}, ` +
         `Status: ${polling.connectionStatus.value}, ` +
         `Errors: ${polling.hasError.value ? 'Yes' : 'No'}`;
});

// Methods
const formatValue = (value) => {
  if (typeof value === 'string') return value;

  switch (props.format) {
    case 'percentage':
      return `${value}%`;
    case 'currency':
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(value);
    case 'time':
      return formatDuration(value);
    default:
      return formatNumber(value);
  }
};

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K';
  }
  return num.toLocaleString('id-ID');
};

const formatDuration = (minutes) => {
  if (minutes < 60) {
    return `${minutes}m`;
  } else if (minutes < 1440) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
  } else {
    const days = Math.floor(minutes / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    return `${days}d ${hours}h`;
  }
};

const handleRefresh = async () => {
  pollingInProgress.value = true;
  localError.value = null;
  
  try {
    if (polling) {
      await polling.refresh();
    }
    
    lastUpdated.value = new Date();
    emit('refresh');
  } catch (error) {
    localError.value = error.message;
    emit('error', error);
  } finally {
    pollingInProgress.value = false;
  }
};

const retryRefresh = () => {
  if (polling && polling.canRetry.value) {
    polling.retry();
  } else {
    handleRefresh();
  }
};

const toggleAutoRefresh = () => {
  localAutoRefreshEnabled.value = !localAutoRefreshEnabled.value;
  
  if (polling) {
    if (localAutoRefreshEnabled.value) {
      polling.startPolling();
    } else {
      polling.stopPolling();
    }
  }
};

const updateLastUpdated = () => {
  lastUpdated.value = new Date();

  // Update "last updated" text
  if (updateInterval.value) {
    clearInterval(updateInterval.value);
  }

  let seconds = 0;
  updateInterval.value = setInterval(() => {
    seconds++;
    if (seconds < 60) {
      // Reactive update will happen automatically due to computed property
    } else if (seconds < 3600) {
      // Reactive update will happen automatically due to computed property
    } else {
      // Reactive update will happen automatically due to computed property
    }
  }, 1000);
};

const startAutoRefresh = () => {
  if (polling && localAutoRefreshEnabled.value) {
    polling.startPolling();
  }
};

const stopAutoRefresh = () => {
  if (polling) {
    polling.stopPolling();
  }
};

// Lifecycle hooks
onMounted(() => {
  updateLastUpdated();
  startAutoRefresh();
});

onUnmounted(() => {
  if (updateInterval.value) {
    clearInterval(updateInterval.value);
  }
  stopAutoRefresh();
});

// Watch for auto-refresh prop changes
watch(() => props.autoRefreshEnabled, (newValue) => {
  localAutoRefreshEnabled.value = newValue;
  if (newValue) {
    startAutoRefresh();
  } else {
    stopAutoRefresh();
  }
});
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.ring-2 {
  animation: pulse 2s infinite;
}

/* Polling status indicators */
.polling-active {
  position: relative;
}

.polling-active::after {
  content: '';
  position: absolute;
  top: -2px;
  right: -2px;
  width: 6px;
  height: 6px;
  background: #10b981;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.polling-error::after {
  content: '';
  position: absolute;
  top: -2px;
  right: -2px;
  width: 6px;
  height: 6px;
  background: #ef4444;
  border-radius: 50%;
}
</style>
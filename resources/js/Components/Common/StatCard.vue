<template>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">{{ title }}</p>
        <p class="mt-2 text-3xl font-bold" :class="valueColor">{{ formattedValue }}</p>
        <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
      </div>
      <div class="flex-shrink-0">
        <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl" :class="iconBgColor">
          {{ icon }}
        </div>
      </div>
    </div>
    <div v-if="trend" class="mt-4 flex items-center text-sm">
      <span v-if="trend > 0" class="text-green-600 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        {{ Math.abs(trend) }}%
      </span>
      <span v-else-if="trend < 0" class="text-red-600 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        {{ Math.abs(trend) }}%
      </span>
      <span v-else class="text-gray-600">0%</span>
      <span class="ml-2 text-gray-500">{{ t('statCard.vsLastPeriod') }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  icon: {
    type: String,
    default: '📊'
  },
  color: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'green', 'yellow', 'red', 'indigo', 'purple', 'pink', 'gray'].includes(value)
  },
  subtitle: {
    type: String,
    default: null
  },
  trend: {
    type: Number,
    default: null
  }
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString();
  }
  return props.value;
});

const valueColor = computed(() => {
  const colors = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    red: 'text-red-600',
    indigo: 'text-indigo-600',
    purple: 'text-purple-600',
    pink: 'text-pink-600',
    gray: 'text-gray-600'
  };
  return colors[props.color] || colors.blue;
});

const iconBgColor = computed(() => {
  const colors = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    red: 'bg-red-100 text-red-600',
    indigo: 'bg-indigo-100 text-indigo-600',
    purple: 'bg-purple-100 text-purple-600',
    pink: 'bg-pink-100 text-pink-600',
    gray: 'bg-gray-100 text-gray-600'
  };
  return colors[props.color] || colors.blue;
});
</script>

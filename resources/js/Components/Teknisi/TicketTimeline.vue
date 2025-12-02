<template>
    <div class="space-y-4">
        <div v-if="timeline.length === 0" class="text-center py-8 text-gray-500">
            No activity recorded yet
        </div>

        <div v-else class="relative">
            <!-- Timeline line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            <!-- Timeline items -->
            <div
                v-for="(item, index) in timeline"
                :key="item.id"
                class="relative flex items-start space-x-4 pb-6"
            >
                <!-- Icon -->
                <div :class="[
                    'relative z-10 flex items-center justify-center w-8 h-8 rounded-full border-2 border-white shadow-md',
                    getBackgroundColor(item.color || 'gray')
                ]">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(item.icon || 'activity')" />
                    </svg>
                </div>

                <!-- Content -->
                <div class="flex-1 bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h4 class="font-medium text-gray-900">{{ item.description || 'Action performed' }}</h4>
                            <p v-if="item.actor" class="text-sm text-gray-600">
                                by {{ item.actor.name || 'Unknown' }}
                                <span class="text-gray-400">•</span>
                                <span class="text-gray-500">{{ item.actor.type || 'system' }}</span>
                            </p>
                        </div>
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-4">
                            {{ item.formatted_created_at || 'Just now' }}
                        </span>
                    </div>

                    <!-- Metadata -->
                    <div v-if="item.metadata && Object.keys(item.metadata).length > 0" class="mt-2">
                        <div class="bg-gray-50 rounded p-2 text-sm">
                            <div v-for="(value, key) in item.metadata" :key="key" class="text-gray-600">
                                <span class="font-medium">{{ formatKey(key) }}:</span>
                                {{ formatValue(value) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    timeline: {
        type: Array,
        required: true,
        default: () => []
    }
});

const getIconPath = (iconName) => {
    // Return SVG path data based on icon name
    const icons = {
        'plus-circle': 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        'refresh': 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        'message-square': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'x-circle': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'alert-triangle': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'activity': 'M13 10V3L4 14h7v7l9-11h-7z',
    };
    return icons[iconName] || icons['activity'];
};

const getBackgroundColor = (color) => {
    const colors = {
        'blue': 'bg-blue-500',
        'indigo': 'bg-indigo-500',
        'purple': 'bg-purple-500',
        'gray': 'bg-gray-500',
        'green': 'bg-green-500',
        'red': 'bg-red-500',
        'orange': 'bg-orange-500',
        'yellow': 'bg-yellow-500',
    };
    return colors[color] || 'bg-gray-500';
};

const formatKey = (key) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatValue = (value) => {
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }
    return value;
};
</script>

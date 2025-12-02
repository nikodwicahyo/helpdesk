<template>
    <div :class="containerClass">
        <!-- Card Skeleton -->
        <div v-if="variant === 'card'" class="space-y-4">
            <div v-for="i in count" :key="i" class="bg-white rounded-lg shadow p-6">
                <div class="animate-pulse">
                    <div class="flex items-center space-x-4">
                        <div v-if="showAvatar" class="rounded-full bg-gray-300 h-12 w-12"></div>
                        <div class="flex-1 space-y-3">
                            <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                        </div>
                    </div>
                    <div v-if="showContent" class="mt-4 space-y-2">
                        <div class="h-3 bg-gray-300 rounded"></div>
                        <div class="h-3 bg-gray-300 rounded w-5/6"></div>
                        <div class="h-3 bg-gray-300 rounded w-4/6"></div>
                    </div>
                    <div v-if="showActions" class="mt-4 flex space-x-2">
                        <div class="h-8 bg-gray-300 rounded w-20"></div>
                        <div class="h-8 bg-gray-300 rounded w-20"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- List Skeleton -->
        <div v-else-if="variant === 'list'" class="space-y-3">
            <div v-for="i in count" :key="i" class="flex items-center space-x-4 p-4 bg-white rounded-lg shadow">
                <div class="animate-pulse flex items-center space-x-4 flex-1">
                    <div v-if="showAvatar" class="rounded-full bg-gray-300 h-10 w-10"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                    </div>
                    <div v-if="showActions" class="h-8 bg-gray-300 rounded w-16"></div>
                </div>
            </div>
        </div>

        <!-- Table Skeleton -->
        <div v-else-if="variant === 'table'" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="animate-pulse">
                <!-- Table Header -->
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <div class="flex space-x-4">
                        <div v-for="i in columns" :key="i" class="h-4 bg-gray-300 rounded flex-1"></div>
                    </div>
                </div>
                <!-- Table Rows -->
                <div v-for="i in rows" :key="i" class="px-6 py-4 border-b border-gray-200">
                    <div class="flex space-x-4">
                        <div v-for="j in columns" :key="j" class="h-4 bg-gray-300 rounded flex-1"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Skeleton -->
        <div v-else-if="variant === 'stats'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="i in count" :key="i" class="bg-white rounded-lg shadow p-6">
                <div class="animate-pulse space-y-3">
                    <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                    <div class="h-8 bg-gray-300 rounded w-3/4"></div>
                    <div class="h-2 bg-gray-300 rounded w-1/3"></div>
                </div>
            </div>
        </div>

        <!-- Chart Skeleton -->
        <div v-else-if="variant === 'chart'" class="bg-white rounded-lg shadow p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 rounded w-1/3"></div>
                <div class="h-64 bg-gray-300 rounded"></div>
                <div class="flex justify-center space-x-4">
                    <div class="h-3 bg-gray-300 rounded w-16"></div>
                    <div class="h-3 bg-gray-300 rounded w-16"></div>
                    <div class="h-3 bg-gray-300 rounded w-16"></div>
                </div>
            </div>
        </div>

        <!-- Text Skeleton -->
        <div v-else-if="variant === 'text'" class="space-y-2">
            <div v-for="i in count" :key="i" class="animate-pulse">
                <div class="h-4 bg-gray-300 rounded" :class="getRandomWidth()"></div>
            </div>
        </div>

        <!-- Custom Skeleton -->
        <div v-else-if="variant === 'custom'" class="animate-pulse">
            <slot></slot>
        </div>

        <!-- Default Skeleton -->
        <div v-else class="space-y-4">
            <div v-for="i in count" :key="i" class="animate-pulse">
                <div class="h-4 bg-gray-300 rounded w-full"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'card',
        validator: (value) => [
            'card',
            'list',
            'table',
            'stats',
            'chart',
            'text',
            'custom',
        ].includes(value),
    },
    count: {
        type: Number,
        default: 3,
    },
    rows: {
        type: Number,
        default: 5,
    },
    columns: {
        type: Number,
        default: 4,
    },
    showAvatar: {
        type: Boolean,
        default: true,
    },
    showContent: {
        type: Boolean,
        default: true,
    },
    showActions: {
        type: Boolean,
        default: false,
    },
    containerClass: {
        type: String,
        default: '',
    },
});

const getRandomWidth = () => {
    const widths = ['w-full', 'w-11/12', 'w-5/6', 'w-3/4', 'w-2/3'];
    return widths[Math.floor(Math.random() * widths.length)];
};
</script>

<style scoped>
@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>

<template>
    <div v-if="show" :class="containerClass">
        <!-- Linear Progress -->
        <div v-if="type === 'linear'" class="w-full">
            <div v-if="showLabel" class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium" :class="labelColor">
                    {{ label }}
                </span>
                <span class="text-sm font-medium" :class="valueColor">
                    {{ progress }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-300 ease-out"
                    :class="progressBarClass"
                    :style="{
                        width: progress + '%',
                        transitionDuration: animated ? (duration / 1000) + 's' : '0s'
                    }"
                >
                    <div v-if="showStripes" class="h-full w-full animate-pulse bg-gradient-to-r from-transparent via-white to-transparent opacity-30"></div>
                </div>
            </div>
            <div v-if="showPercentage" class="mt-1 text-center text-sm" :class="valueColor">
                {{ progress }}% Complete
            </div>
        </div>

        <!-- Circular Progress -->
        <div v-else-if="type === 'circular'" class="relative inline-flex items-center justify-center">
            <div class="relative">
                <!-- Background Circle -->
                <svg class="w-20 h-20 transform -rotate-90">
                    <circle
                        :cx="radius"
                        :cy="radius"
                        :r="radius - strokeWidth"
                        stroke="currentColor"
                        :stroke-width="strokeWidth"
                        fill="none"
                        class="text-gray-200"
                    />
                    <!-- Progress Circle -->
                    <circle
                        :cx="radius"
                        :cy="radius"
                        :r="radius - strokeWidth"
                        stroke="currentColor"
                        :stroke-width="strokeWidth"
                        fill="none"
                        :class="progressBarClass"
                        :stroke-dasharray="circumference"
                        :stroke-dashoffset="strokeDashoffset"
                        class="transition-all duration-300 ease-out"
                        :style="{
                            transitionDuration: animated ? (duration / 1000) + 's' : '0s'
                        }"
                    />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold" :class="valueColor">
                        {{ progress }}%
                    </span>
                </div>
            </div>
            <div v-if="label" class="mt-3 text-center">
                <p class="text-sm font-medium" :class="labelColor">{{ label }}</p>
            </div>
        </div>

        <!-- Dots Progress -->
        <div v-else-if="type === 'dots'" class="flex items-center space-x-2">
            <div
                v-for="i in dots"
                :key="i"
                class="w-3 h-3 rounded-full transition-all duration-300"
                :class="getDotClass(i - 1)"
            ></div>
            <div v-if="label" class="ml-4 text-sm font-medium" :class="labelColor">
                {{ label }}
            </div>
        </div>

        <!-- Steps Progress -->
        <div v-else-if="type === 'steps'" class="w-full">
            <div v-if="showLabel" class="mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium" :class="labelColor">
                        {{ label }}
                    </span>
                    <span class="text-sm font-medium" :class="valueColor">
                        Step {{ currentStep }} of {{ totalSteps }}
                    </span>
                </div>
            </div>
            <div class="relative">
                <!-- Progress Line -->
                <div class="absolute top-5 left-0 h-1 bg-gray-200 rounded-full" :style="{ width: '100%' }">
                    <div
                        class="h-1 rounded-full transition-all duration-300 ease-out"
                        :class="progressBarClass"
                        :style="{
                            width: ((currentStep - 1) / (totalSteps - 1)) * 100 + '%',
                            transitionDuration: animated ? (duration / 1000) + 's' : '0s'
                        }"
                    ></div>
                </div>

                <!-- Steps -->
                <div class="relative flex justify-between">
                    <div
                        v-for="(step, index) in steps"
                        :key="index"
                        class="flex flex-col items-center"
                    >
                        <div
                            class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-all duration-300"
                            :class="getStepClass(index)"
                        >
                            <span v-if="index + 1 < currentStep" class="text-white">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="mt-2 text-xs text-center max-w-20">
                            <p class="font-medium" :class="getStepTextClass(index)">
                                {{ step.title }}
                            </p>
                            <p v-if="step.description" class="text-gray-500 mt-1">
                                {{ step.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Content -->
        <div v-else-if="type === 'custom'" class="w-full">
            <slot :progress="progress" :label="label" :animated="animated">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium" :class="labelColor">
                        {{ label }}
                    </span>
                    <span class="text-sm font-medium" :class="valueColor">
                        {{ progress }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300 ease-out"
                        :class="progressBarClass"
                        :style="{
                            width: progress + '%',
                            transitionDuration: animated ? (duration / 1000) + 's' : '0s'
                        }"
                    >
                        <div v-if="showStripes" class="h-full w-full animate-pulse bg-gradient-to-r from-transparent via-white to-transparent opacity-30"></div>
                    </div>
                </div>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    // Basic props
    progress: {
        type: Number,
        default: 0,
        validator: (value) => value >= 0 && value <= 100
    },
    label: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'linear',
        validator: (value) => ['linear', 'circular', 'dots', 'steps', 'custom'].includes(value)
    },

    // Appearance
    color: {
        type: String,
        default: 'indigo',
        validator: (value) => ['indigo', 'blue', 'green', 'yellow', 'red', 'purple', 'gray'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },

    // Behavior
    animated: {
        type: Boolean,
        default: true
    },
    duration: {
        type: Number,
        default: 1000
    },

    // Display options
    show: {
        type: Boolean,
        default: true
    },
    showLabel: {
        type: Boolean,
        default: true
    },
    showPercentage: {
        type: Boolean,
        default: false
    },
    showStripes: {
        type: Boolean,
        default: false
    },

    // Steps specific
    steps: {
        type: Array,
        default: () => []
    },
    currentStep: {
        type: Number,
        default: 1
    },
    totalSteps: {
        type: Number,
        default: 1
    },

    // Dots specific
    dots: {
        type: Number,
        default: 3
    }
});

const containerClass = computed(() => {
    return props.type === 'steps' ? 'w-full' : '';
});

const labelColor = computed(() => {
    const colors = {
        indigo: 'text-indigo-700',
        blue: 'text-blue-700',
        green: 'text-green-700',
        yellow: 'text-yellow-700',
        red: 'text-red-700',
        purple: 'text-purple-700',
        gray: 'text-gray-700'
    };
    return colors[props.color] || colors.indigo;
});

const valueColor = computed(() => {
    const colors = {
        indigo: 'text-indigo-900',
        blue: 'text-blue-900',
        green: 'text-green-900',
        yellow: 'text-yellow-900',
        red: 'text-red-900',
        purple: 'text-purple-900',
        gray: 'text-gray-900'
    };
    return colors[props.color] || colors.indigo;
});

const progressBarClass = computed(() => {
    const colors = {
        indigo: 'bg-indigo-600',
        blue: 'bg-blue-600',
        green: 'bg-green-600',
        yellow: 'bg-yellow-600',
        red: 'bg-red-600',
        purple: 'bg-purple-600',
        gray: 'bg-gray-600'
    };
    return colors[props.color] || colors.indigo;
});

// Circular progress calculations
const radius = 40;
const strokeWidth = 8;
const circumference = computed(() => {
    return 2 * Math.PI * (radius - strokeWidth);
});

const strokeDashoffset = computed(() => {
    return circumference.value - (props.progress / 100) * circumference.value;
});

// Dots animation
const getDotClass = (index) => {
    const dotProgress = (index + 1) / props.dots;
    const isActive = dotProgress <= (props.progress / 100);

    const colors = {
        indigo: isActive ? 'bg-indigo-600' : 'bg-gray-300',
        blue: isActive ? 'bg-blue-600' : 'bg-gray-300',
        green: isActive ? 'bg-green-600' : 'bg-gray-300',
        yellow: isActive ? 'bg-yellow-600' : 'bg-gray-300',
        red: isActive ? 'bg-red-600' : 'bg-gray-300',
        purple: isActive ? 'bg-purple-600' : 'bg-gray-300',
        gray: isActive ? 'bg-gray-600' : 'bg-gray-300'
    };

    return `${colors[props.color] || colors.indigo} ${isActive ? 'animate-pulse' : ''}`;
};

// Steps classes
const getStepClass = (index) => {
    const stepNumber = index + 1;
    const isActive = stepNumber === props.currentStep;
    const isCompleted = stepNumber < props.currentStep;

    const baseClasses = 'border-2 transition-all duration-300';

    if (isCompleted) {
        return `${baseClasses} bg-green-500 border-green-500 text-white`;
    } else if (isActive) {
        return `${baseClasses} ${progressBarClass.value.replace('bg-', 'border-')} text-white`;
    } else {
        return `${baseClasses} bg-white border-gray-300 text-gray-500`;
    }
};

const getStepTextClass = (index) => {
    const stepNumber = index + 1;
    const isActive = stepNumber === props.currentStep;
    const isCompleted = stepNumber < props.currentStep;

    if (isCompleted) {
        return 'text-green-600';
    } else if (isActive) {
        return valueColor.value;
    } else {
        return 'text-gray-500';
    }
};
</script>
<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="closeModal"
            >
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-black bg-opacity-90 transition-opacity"
                    @click="closeModal"
                ></div>

                <!-- Modal content -->
                <div
                    class="flex min-h-screen items-center justify-center p-4"
                >
                    <div
                        class="relative max-w-7xl w-full"
                        @click.stop
                    >
                        <!-- Close button -->
                        <button
                            @click="closeModal"
                            class="absolute top-4 right-4 z-10 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 rounded-full p-2"
                            title="Close (ESC)"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>

                        <!-- Navigation buttons -->
                        <button
                            v-if="hasPrevious"
                            @click="previousImage"
                            class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 rounded-full p-3"
                            title="Previous (←)"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </button>

                        <button
                            v-if="hasNext"
                            @click="nextImage"
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 rounded-full p-3"
                            title="Next (→)"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </button>

                        <!-- Image container -->
                        <div class="flex items-center justify-center">
                            <transition
                                mode="out-in"
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <img
                                    :key="currentImage.url"
                                    :src="currentImage.url"
                                    :alt="currentImage.name"
                                    class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl"
                                    @load="imageLoaded = true"
                                />
                            </transition>
                        </div>

                        <!-- Image info -->
                        <div
                            class="mt-4 text-center text-white bg-black bg-opacity-50 rounded-lg p-4"
                        >
                            <p class="text-lg font-medium">
                                {{ currentImage.name }}
                            </p>
                            <p v-if="currentImage.size" class="text-sm text-gray-300 mt-1">
                                {{ formatFileSize(currentImage.size) }}
                            </p>
                            <div
                                v-if="images.length > 1"
                                class="mt-2 text-sm text-gray-300"
                            >
                                {{ currentIndex + 1 }} / {{ images.length }}
                            </div>

                            <!-- Action buttons -->
                            <div class="mt-4 flex justify-center space-x-3">
                                <a
                                    :href="currentImage.downloadUrl || currentImage.url"
                                    :download="currentImage.name"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Download
                                </a>
                                <button
                                    v-if="zoomEnabled"
                                    @click="toggleZoom"
                                    class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="isZoomed ? 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10h-2m0 0H9m2 0v2m0-2V8' : 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7'"
                                        />
                                    </svg>
                                    {{ isZoomed ? 'Zoom Out' : 'Zoom In' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    images: {
        type: Array,
        required: true,
        // Expected format: [{ url: string, name: string, size?: number, downloadUrl?: string }]
    },
    initialIndex: {
        type: Number,
        default: 0,
    },
    zoomEnabled: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:show', 'close', 'change']);

const isOpen = ref(props.show);
const currentIndex = ref(props.initialIndex);
const imageLoaded = ref(false);
const isZoomed = ref(false);

const currentImage = computed(() => props.images[currentIndex.value] || {});
const hasPrevious = computed(() => currentIndex.value > 0);
const hasNext = computed(() => currentIndex.value < props.images.length - 1);

watch(
    () => props.show,
    (newValue) => {
        isOpen.value = newValue;
        if (newValue) {
            currentIndex.value = props.initialIndex;
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

watch(
    () => props.initialIndex,
    (newValue) => {
        currentIndex.value = newValue;
    }
);

watch(currentIndex, () => {
    imageLoaded.value = false;
    isZoomed.value = false;
    emit('change', currentIndex.value);
});

const closeModal = () => {
    isOpen.value = false;
    emit('update:show', false);
    emit('close');
    document.body.style.overflow = '';
};

const previousImage = () => {
    if (hasPrevious.value) {
        currentIndex.value--;
    }
};

const nextImage = () => {
    if (hasNext.value) {
        currentIndex.value++;
    }
};

const toggleZoom = () => {
    isZoomed.value = !isZoomed.value;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const handleKeydown = (event) => {
    if (!isOpen.value) return;

    switch (event.key) {
        case 'Escape':
            closeModal();
            break;
        case 'ArrowLeft':
            previousImage();
            break;
        case 'ArrowRight':
            nextImage();
            break;
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

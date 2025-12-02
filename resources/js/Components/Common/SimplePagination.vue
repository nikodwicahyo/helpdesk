<template>
    <div class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 border-t border-gray-200">
        <!-- Showing text -->
        <div class="flex-1 flex justify-between sm:hidden">
            <template v-if="pagination && pagination.total > 0">
                Menampilkan {{ pagination.from || 0 }}
                sampai {{ pagination.to || 0 }}
                dari {{ pagination.total }} {{ label }}
            </template>
            <template v-else>
                Menampilkan 0 sampai 0 dari 0 {{ label }}
            </template>
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-700">
                <template v-if="pagination && pagination.total > 0">
                    Menampilkan {{ pagination.from || 0 }}
                    sampai {{ pagination.to || 0 }}
                    dari {{ pagination.total }} {{ label }}
                </template>
                <template v-else>
                    Menampilkan 0 sampai 0 dari 0 {{ label }}
                </template>
            </div>

            <!-- Pagination controls -->
            <div class="flex space-x-2">
                <button
                    @click="prevPage"
                    :disabled="!pagination || pagination.current_page === 1"
                    class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 disabled:opacity-50 hover:bg-gray-300 transition-colors"
                >
                    Sebelumnya
                </button>
                <span class="px-3 py-1 bg-blue-500 text-white rounded-md">
                    {{ (pagination?.current_page || 1) }} dari {{ totalPages }}
                </span>
                <button
                    @click="nextPage"
                    :disabled="!pagination || pagination.current_page === totalPages"
                    class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 disabled:opacity-50 hover:bg-gray-300 transition-colors"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    label: {
        type: String,
        default: 'item',
    },
});

const emit = defineEmits(['page-changed']);

const pagination = computed(() => {
    if (!props.data) return null;
    // Handle Laravel Resource Collection (meta) vs Standard Paginator
    return props.data.meta ? { ...props.data.meta } : props.data;
});

const totalPages = computed(() => {
    if (!pagination.value || !pagination.value.total) return 1;
    return pagination.value.last_page || Math.ceil(pagination.value.total / (pagination.value.per_page || 15));
});

const nextPage = () => {
    if (pagination.value && pagination.value.current_page < totalPages.value) {
        emit('page-changed', pagination.value.current_page + 1);
    }
};

const prevPage = () => {
    if (pagination.value && pagination.value.current_page > 1) {
        emit('page-changed', pagination.value.current_page - 1);
    }
};
</script>